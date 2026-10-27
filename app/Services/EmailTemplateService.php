<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\Email\EmailTemplateCatalog;
use App\Support\Events\AuditEventRecorder;
use App\Support\I18n\Locales;
use Illuminate\Support\Traits\Localizable;

/**
 * The audited writer for the per-org email-template customization (Operator Email Templates exact-match #86).
 * Mirrors {@see SettingsService}: whitelist the editable columns, upsert per (org, key), record WHICH fields
 * changed (never their values), stamp `updated_by`. The #83 Mailables read the result at send.
 */
class EmailTemplateService
{
    use Localizable;

    public function __construct(private readonly AuditEventRecorder $audit) {}

    /**
     * Save a template's content + block toggles. Only known keys/blocks are accepted (a foreign key is
     * ignored, never stored). An empty subject/heading/message/button falls back to the code default.
     *
     * @param  array{subject?: ?string, heading?: ?string, message?: ?string, button_label?: ?string, blocks?: array<string, bool>}  $data
     */
    public function save(string $organizationId, string $key, array $data, ?User $actor): EmailTemplate
    {
        $catalog = EmailTemplateCatalog::get($key);
        abort_if($catalog === null, 404);

        // Only the blocks this template actually defines can be toggled — a foreign block key is dropped.
        $validBlocks = array_keys($catalog['blocks']);
        $blocks = [];
        foreach (($data['blocks'] ?? []) as $blockKey => $on) {
            if (in_array($blockKey, $validBlocks, true)) {
                $blocks[$blockKey] = (bool) $on;
            }
        }

        $template = EmailTemplate::query()->firstOrNew([
            'organization_id' => $organizationId,
            'template_key' => $key,
        ]);

        // I18N-01 — a field left at its default is stored as NULL, never as the default's words: the editor shows the default
        // in the operator's language, and saving it verbatim would freeze that email in that language for every guest.
        $english = $this->withLocale(Locales::SOURCE, fn (): ?array => EmailTemplateCatalog::get($key)) ?? $catalog;
        $template->fill([
            'subject' => $this->clean($data['subject'] ?? null, [$catalog['subject'], $english['subject']]),
            'heading' => $this->clean($data['heading'] ?? null, [$catalog['heading'], $english['heading']]),
            'message' => $this->clean($data['message'] ?? null, [$catalog['message'], $english['message']]),
            'button_label' => $this->clean($data['button_label'] ?? null, [$catalog['button'], $english['button']]),
            'blocks' => $blocks,
            'updated_by' => $actor?->getKey(),
        ]);
        $template->enabled = $template->enabled ?? true;
        $template->save();

        $this->audit->record('email_template.updated', $template, null, [
            'template' => $key,
            'fields' => array_keys(array_filter([
                'subject' => $template->subject, 'heading' => $template->heading,
                'message' => $template->message, 'button_label' => $template->button_label,
            ])),
        ]);

        return $template;
    }

    /** Enable/disable the email — OFF genuinely stops the send. Audited. */
    public function setEnabled(string $organizationId, string $key, bool $enabled, ?User $actor): EmailTemplate
    {
        abort_if(EmailTemplateCatalog::get($key) === null, 404);

        $template = EmailTemplate::query()->firstOrNew([
            'organization_id' => $organizationId,
            'template_key' => $key,
        ]);
        $template->enabled = $enabled;
        $template->updated_by = $actor?->getKey();
        $template->save();

        $this->audit->record($enabled ? 'email_template.enabled' : 'email_template.disabled', $template, null, ['template' => $key]);

        return $template;
    }

    /** @param  list<string>  $defaults  the field's default, in the editor's language and in English */
    private function clean(?string $value, array $defaults = []): ?string
    {
        $trimmed = is_string($value) ? trim($value) : '';
        $isDefault = in_array($trimmed, array_map(fn (string $d): string => trim($d), $defaults), true);

        return $trimmed !== '' && ! $isDefault ? $trimmed : null;
    }
}
