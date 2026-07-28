<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * PASSKEYS / WebAuthn (Wave B · GATE 4) — a thin, honest wrapper over the mature {@link web-auth/webauthn-lib}. It
 * generates the registration/authentication ceremony options, verifies the browser's authenticator response with
 * the library's REAL cryptographic validators, and persists a {@see Passkey} (the credential id + the serialized
 * {@see PublicKeyCredentialSource}). NOTHING here is faked — a passkey genuinely authenticates or the validator
 * throws.
 *
 * The relying-party id + origin are derived from the request host, so it works on 127.0.0.1 in dev and the real
 * domain in production. The challenge is random per ceremony and MUST be stored server-side (session) between the
 * options request and the verification — the caller holds it.
 */
class PasskeyService
{
    private SerializerInterface $serializer;

    private AttestationStatementSupportManager $attestationManager;

    public function __construct()
    {
        // "none" attestation is what platform passkeys send — no attestation CA chain to validate.
        $this->attestationManager = AttestationStatementSupportManager::create();
        $this->attestationManager->add(NoneAttestationStatementSupport::create());

        $this->serializer = (new WebauthnSerializerFactory($this->attestationManager))->create();
    }

    /** Serialize any WebAuthn options/object to the browser-facing JSON the WebAuthn JSON API understands. */
    public function toJson(object $options): string
    {
        return $this->serializer->serialize($options, 'json');
    }

    /** Rehydrate the creation options stashed in session (the challenge must survive to the verify step). */
    public function creationOptionsFromJson(string $json): PublicKeyCredentialCreationOptions
    {
        return $this->serializer->deserialize($json, PublicKeyCredentialCreationOptions::class, 'json');
    }

    /** Rehydrate the request options stashed in session. */
    public function requestOptionsFromJson(string $json): PublicKeyCredentialRequestOptions
    {
        return $this->serializer->deserialize($json, PublicKeyCredentialRequestOptions::class, 'json');
    }

    /**
     * REGISTRATION options — a fresh creation ceremony for this user, excluding the credentials they already have
     * so the same authenticator isn't enrolled twice. The caller serialises this to JSON + stashes it in session.
     */
    public function creationOptions(User $user, Request $request): PublicKeyCredentialCreationOptions
    {
        $exclude = Passkey::query()
            ->where('user_id', (string) $user->getKey())
            ->get()
            ->map(fn (Passkey $p): PublicKeyCredentialDescriptor => $this->sourceFor($p)->getPublicKeyCredentialDescriptor())
            ->all();

        return PublicKeyCredentialCreationOptions::create(
            rp: $this->rpEntity($request),
            user: PublicKeyCredentialUserEntity::create(
                (string) $user->email,
                (string) $user->getKey(),
                (string) $user->name,
            ),
            challenge: random_bytes(32),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::create('public-key', -7),    // ES256
                PublicKeyCredentialParameters::create('public-key', -257),  // RS256
            ],
            // A DISCOVERABLE (resident) credential with user verification — so it can answer a usernameless
            // sign-in (an empty allowCredentials assertion) with Face/Touch ID / device PIN.
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            excludeCredentials: $exclude,
        );
    }

    /**
     * Verify the browser's REGISTRATION response against the stored options with the library's real attestation
     * validator, then persist the credential. Throws (library exception) if the response does not verify.
     */
    public function verifyRegistration(User $user, string $responseJson, PublicKeyCredentialCreationOptions $options, Request $request, ?string $label = null): Passkey
    {
        $credential = $this->serializer->deserialize($responseJson, PublicKeyCredential::class, 'json');
        $response = $credential->response;
        abort_unless($response instanceof AuthenticatorAttestationResponse, 422, 'Not a registration response.');

        $factory = new CeremonyStepManagerFactory;
        $factory->setAllowedOrigins([$request->getSchemeAndHttpHost()]);
        $record = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony())
            ->check($response, $options, $this->rpId($request));

        // $record IS a PublicKeyCredentialSource — store it (serialized) + a base64url credential id for lookup.
        return Passkey::create([
            'user_id' => (string) $user->getKey(),
            'credential_id' => $this->b64url($record->publicKeyCredentialId),
            'public_key' => $this->serializer->serialize($record, 'json'),
            'label' => $label !== null && $label !== '' ? $label : $this->deviceLabel($request),
            'last_used_at' => Carbon::now(),
        ]);
    }

    /** AUTHENTICATION options — a usernameless assertion ceremony (any of the user's platform passkeys may answer). */
    public function requestOptions(Request $request): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: $this->rpId($request),
            userVerification: 'required',
        );
    }

    /**
     * Verify the browser's ASSERTION response against the stored options with the library's real assertion
     * validator. Resolves the stored credential by its id, checks the signature/counter, updates last-used, and
     * returns the matching {@see Passkey} (whose `user` is the authenticated user). Null when nothing matches or
     * the response does not verify.
     */
    public function verifyAssertion(string $responseJson, PublicKeyCredentialRequestOptions $options, Request $request): ?Passkey
    {
        // ANY malformed / forged / non-matching / signature-invalid input → null (never a 500, never a login).
        // The whole ceremony is wrapped: a garbage body can throw as early as the deserialize step.
        try {
            $credential = $this->serializer->deserialize($responseJson, PublicKeyCredential::class, 'json');
            $response = $credential->response;
            if (! $response instanceof AuthenticatorAssertionResponse) {
                return null;
            }

            $passkey = Passkey::query()->where('credential_id', $this->b64url($credential->rawId))->first();
            if ($passkey === null) {
                return null;
            }

            $source = $this->sourceFor($passkey);

            $factory = new CeremonyStepManagerFactory;
            $factory->setAllowedOrigins([$request->getSchemeAndHttpHost()]);

            $updated = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony())
                ->check($source, $response, $options, $this->rpId($request), $source->userHandle);
        } catch (\Throwable) {
            return null;   // malformed / signature / counter / origin mismatch — never authenticate
        }

        // Persist the advanced signature counter (replay protection) + touch last-used.
        $passkey->forceFill([
            'public_key' => $this->serializer->serialize($updated, 'json'),
            'last_used_at' => Carbon::now(),
        ])->save();

        return $passkey;
    }

    /**
     * Deserialize a stored passkey back into a library {@see CredentialRecord} (the serializer round-trips the
     * stored {@see PublicKeyCredentialSource} to its {@see CredentialRecord} base — which is exactly what the
     * assertion validator + the descriptor/userHandle accessors need).
     */
    private function sourceFor(Passkey $passkey): CredentialRecord
    {
        return $this->serializer->deserialize((string) $passkey->public_key, CredentialRecord::class, 'json');
    }

    private function rpEntity(Request $request): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(config('app.name', 'Vyrobel'), $this->rpId($request));
    }

    /** The relying-party id = the request host WITHOUT a port (a registrable domain / localhost / 127.0.0.1). */
    private function rpId(Request $request): string
    {
        return $request->getHost();
    }

    private function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** A short device label from the user-agent (browser + OS), for the passkey list. */
    private function deviceLabel(Request $request): string
    {
        $ua = (string) $request->userAgent();
        $browser = Str::contains($ua, 'Edg') ? 'Edge' : (Str::contains($ua, 'Chrome') ? 'Chrome' : (Str::contains($ua, 'Firefox') ? 'Firefox' : (Str::contains($ua, 'Safari') ? 'Safari' : 'Passkey')));
        $os = Str::contains($ua, ['Mac OS', 'Macintosh']) ? 'macOS' : (Str::contains($ua, 'Windows') ? 'Windows' : (Str::contains($ua, ['iPhone', 'iPad']) ? 'iOS' : (Str::contains($ua, 'Android') ? 'Android' : (Str::contains($ua, 'Linux') ? 'Linux' : 'device'))));

        return $browser.' on '.$os;
    }
}
