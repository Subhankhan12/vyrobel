<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\EmailTemplate;

/**
 * The EFFECTIVE customization of one guest email (Operator Email Templates exact-match #86) — the stored
 * override ({@see EmailTemplate}) merged over the code default ({@see EmailTemplateCatalog}), with the real
 * merge variables (`{first_name}`/`{unit}`/`{nights}`/`{reference}`) substituted from the reservation/guest.
 * The Mailables consume this at send, so what an operator saves genuinely changes the email.
 */
final class ResolvedEmailTemplate
{
    /**
     * @param  array<string, mixed>|null  $catalog  the catalog default entry for this key
     * @param  array<string, scalar>  $vars  merge values keyed by variable name (first_name, unit, …)
     */
    public function __construct(
        private readonly ?array $catalog,
        private readonly ?EmailTemplate $stored,
        private readonly array $vars,
    ) {}

    public function subject(): string
    {
        return $this->merge($this->raw('subject', 'subject'));
    }

    public function heading(): string
    {
        return $this->merge($this->raw('heading', 'heading'));
    }

    public function message(): string
    {
        return $this->merge($this->raw('message', 'message'));
    }

    public function button(): string
    {
        return $this->merge($this->raw('button_label', 'button'));
    }

    /** Whether a content block shows — the stored toggle, else the catalog default (default true). */
    public function block(string $key): bool
    {
        $blocks = $this->stored?->blocks;
        if (is_array($blocks) && array_key_exists($key, $blocks)) {
            return (bool) $blocks[$key];
        }

        return (bool) ($this->catalog['blocks'][$key]['default'] ?? true);
    }

    public function enabled(): bool
    {
        return $this->stored === null ? true : (bool) $this->stored->enabled;
    }

    /** The stored value for a field (non-empty), else the catalog default. */
    private function raw(string $storedField, string $catalogField): string
    {
        $value = $this->stored?->{$storedField};
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return (string) ($this->catalog[$catalogField] ?? '');
    }

    /** Substitute the real `{var}` chips with their values; an unprovided chip is left literal (honest). */
    private function merge(string $text): string
    {
        foreach ($this->vars as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return $text;
    }
}
