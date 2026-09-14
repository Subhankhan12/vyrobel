<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Postmark is the PRODUCTION transactional mail provider (Phase 8 — production email). The server API
    // token comes from ENV ONLY (never committed); the MailSafetyGuard forbids this transport outside
    // production, so a dev/test run can never send through it. See docs/EMAIL-PRODUCTION.md.
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Channex (channel manager)
    |--------------------------------------------------------------------------
    |
    | `fake` gates which ChannelManagerClient implementation is bound (see
    | AppServiceProvider::register()): true -> FakeChannexClient (in-memory, no network,
    | what every test uses), false -> the real ChannexClient (REST, requires api_key).
    | Defaults to true so this is never accidentally live without an explicit opt-out AND
    | real credentials — "config-gated" per docs/DECISIONS.md.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Google sign-in (OAuth 2.0 / OpenID Connect)
    |--------------------------------------------------------------------------
    |
    | ⚠️⚠️ EMPTY IS THE SHIPPING STATE, AND IT IS A FEATURE. "Configured" and "enabled" are the same fact
    | here — there is deliberately NO separate on/off flag, because a flag can be true while the secret is
    | missing, and that produces the one thing this feature must never show: a sign-in button that leads to
    | an error page. With nothing to disagree with, the button, the routes and the callback are all absent
    | together.
    |
    | ⚠️ The values are the FOUNDER'S, entered from the Google Cloud console — never committed, never
    | invented. See docs/GOOGLE-SIGN-IN.md for the exact console fields and redirect URIs.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URI', ''),
    ],

    'channex' => [
        'base_url' => env('CHANNEX_BASE_URL', 'https://staging.channex.io/api/v1'),
        'api_key' => env('CHANNEX_API_KEY'),
        'fake' => env('CHANNEX_FAKE', true),
        // Shared secret for validating INBOUND Channex webhook signatures (HMAC-SHA256 over the
        // raw request body). Documented guess at Channex's scheme — validated only against
        // simulated payloads, not live Channex (the deferred integration checkpoint).
        'webhook_secret' => env('CHANNEX_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments — gateway selection
    |--------------------------------------------------------------------------
    |
    | Which PaymentGateway FolioService uses. `manual` = offline/pay-at-property (Phase 1);
    | `stripe` = the real gateway (Step 6). Per-tenant override is a Phase 3 hook — for now a
    | single config default drives the container binding (AppServiceProvider).
    |
    */
    'payments' => [
        'gateway' => env('PAYMENTS_GATEWAY', 'manual'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Unified Inbox — messaging channels
    |--------------------------------------------------------------------------
    |
    | Per-channel config for the guest-messaging inbox. `manual` is the always-on internal channel
    | (records outbound + accepts injected inbound — no external dependency). The real external
    | adapters (email/whatsapp/booking_com/airbnb) are DEFERRED, account-gated skeletons that THROW
    | "not configured" until deliberately implemented + credentialed (like StripeConnectPayoutRail).
    | The `enabled` flags default false so a deferred channel is never accidentally treated as live.
    |
    */
    'inbox' => [
        'channels' => [
            'email' => ['enabled' => env('INBOX_EMAIL_ENABLED', false)],
            'whatsapp' => ['enabled' => env('INBOX_WHATSAPP_ENABLED', false)],
            'booking_com' => ['enabled' => env('INBOX_BOOKING_COM_ENABLED', false)],
            'airbnb' => ['enabled' => env('INBOX_AIRBNB_ENABLED', false)],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Owner payouts — rail selection (Phase 3 · Module 1 Step 5)
    |--------------------------------------------------------------------------
    |
    | Which PayoutRail disburses owner payouts. `manual` = recorded as paid offline (bank transfer
    | done outside the system) — the only working rail. `stripe_connect` is a config-gated SKELETON
    | only, explicitly UNVERIFIED (real connected-account onboarding + KYC is a separate deferred live
    | checkpoint) — never bound in tests. Defaults to `manual`.
    |
    */
    'payouts' => [
        'rail' => env('PAYOUTS_RAIL', 'manual'),

        // The automated payout ENGINE (scheduled runs). OFF by default: the scheduler entry is inert until
        // deliberately enabled, so a fresh deploy never auto-pays. Enabling it runs in TEST MODE unless a live
        // Stripe key + production are also configured (see PayoutEngineMode / StripeSafetyGuard). Turning it on
        // for real money is the founder's documented go-live step (docs/STRIPE-PRODUCTION.md).
        'engine_enabled' => env('PAYOUT_ENGINE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public booking engine
    |--------------------------------------------------------------------------
    |
    | Per-IP-per-minute request cap for the public search/quote/book endpoints
    | (throttle:booking). Config-driven so it can be tightened per environment / lowered in tests.
    |
    */
    'booking' => [
        'rate_limit' => env('BOOKING_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing — the try-booking sandbox
    |--------------------------------------------------------------------------
    |
    | ⚠️ Applies to the PARAMETERISED path only (`?from=&to=&m=`). The plain page is unlimited; see the
    | `try-booking` limiter in AppServiceProvider for why. Bounds the per-request pricing work, not the
    | exposure — the sandbox has no property parameter and can only ever read the demo property.
    |
    */

    'marketing' => [
        'try_booking_rate_limit' => env('TRY_BOOKING_RATE_LIMIT', 40),

        // ⚠️ WHERE A CONTACT-FORM SUBMISSION IS ANNOUNCED. The founder's real mailbox, set in the
        // environment — deliberately NOT defaulted to an invented address. ⚠️ Unset is a supported state,
        // not a broken one: the message is written to `marketing_contact_messages` first and the email is
        // only a notification, so an unconfigured destination costs the notification and never the message.
        'contact_to' => env('MARKETING_CONTACT_TO'),

        // Per-IP cap on the public contact POST. Generous enough that a person who mistypes their email and
        // resubmits is never blocked; low enough that the endpoint is not a free mail relay.
        'contact_rate_limit' => env('MARKETING_CONTACT_RATE_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth throttle
    |--------------------------------------------------------------------------
    |
    | Per-IP login attempts per minute, applied to all four realms and both 2FA challenges (audit
    | F-2a-04). This is the CREDENTIAL-STUFFING net: the per-credential limit lives in the login
    | FormRequests and never trips when every attempt uses a different email address.
    |
    | Raise it only if a large office genuinely shares one NAT address.
    |
    */

    'auth' => [
        'login_ip_rate_limit' => env('LOGIN_IP_RATE_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    |
    | `fake` gates the StripeClient seam (AppServiceProvider): true -> FakeStripeClient
    | (in-memory, no network — every test), false -> the real SDK-backed StripeClient (requires
    | `secret`). Defaults true so it is never accidentally live without an explicit opt-out AND
    | real keys. `webhook_secret` validates inbound Stripe webhook signatures (Stripe's real,
    | documented `Stripe-Signature` HMAC scheme).
    |
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        // The ACCOUNT webhook endpoint's signing secret (/webhooks/stripe — payments + subscription billing).
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // The CONNECT webhook endpoint's signing secret — a DISTINCT endpoint (/webhooks/stripe/connect) with
        // its OWN secret (owner-payout transfer/account events). Separated per Stripe best practice; each
        // endpoint verifies against its own secret. Wired in SG3 (owner payout rail).
        'connect_webhook_secret' => env('STRIPE_CONNECT_WEBHOOK_SECRET'),
        'fake' => env('STRIPE_FAKE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM (Ask Vyrobel support assistant — Wave D)
    |--------------------------------------------------------------------------
    |
    | `fake` gates the LlmClient seam (AppServiceProvider): true -> FakeLlmClient
    | (deterministic, no network, no cost — every dev/CI/demo run), false -> the real
    | AnthropicLlmClient (requires `api_key`, production-only). Defaults true so a real
    | LLM is NEVER called without an explicit opt-out AND a key AND production
    | (LlmSafetyGuard). `daily_token_cap` caps per-owner/day spend (the cost ledger).
    | The live flip is the founder's documented go-live step.
    |
    */
    'llm' => [
        'provider' => env('LLM_PROVIDER', 'anthropic'),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('LLM_MODEL', 'claude-haiku-4-5-20251001'),
        'max_output_tokens' => (int) env('LLM_MAX_OUTPUT_TOKENS', 400),
        'daily_token_cap' => (int) env('LLM_DAILY_TOKEN_CAP', 20000),
        'fake' => env('LLM_FAKE', true),
    ],

];
