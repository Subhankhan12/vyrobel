<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\EmailTemplateActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\EmailTemplate;
use App\Models\Scopes\OrganizationScope;
use App\Support\Email\EmailPreviewSample;
use App\Support\Email\EmailTemplateCatalog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Operator Email EDITOR (#86) — the single-template customization surface. Holds the DRAFT (subject / heading /
 * message / button / block toggles) as live props; {@see render()} renders the LIVE PREVIEW by rendering the
 * REAL `emails.{key}` blade + layout ({@see EmailPreviewSample}) with the draft overlaid on real sample data —
 * so what you see IS the send (same Mailable, same blade — cannot drift).
 *
 * Read-only component: the DRAFT lives here for the preview, but SAVING is a real guarded controller POST
 * ({@see EmailTemplateActionController}, `can:properties.manage`, audited) — the
 * editable inputs carry BOTH `wire:model.live` (preview) and `name=` (the native form submit). No mutating
 * Livewire action. Tenant-scoped via {@see TenantContext} (never a request id).
 */
#[Layout('components.layouts.operator')]
class OperatorEmailEditor extends Component
{
    use FormatsOperatorData;

    #[Locked]
    public string $templateKey = '';

    public string $subject = '';

    public string $heading = '';

    public string $message = '';

    public string $button = '';

    /** @var array<string, bool> */
    public array $blocks = [];

    public function mount(string $key): void
    {
        abort_if(EmailTemplateCatalog::get($key) === null, 404);
        $this->templateKey = $key;

        $catalog = EmailTemplateCatalog::get($key);
        $stored = $this->stored();

        // The draft opens at the EFFECTIVE values (stored override, else the code default) — editing from
        // exactly what the guest receives today.
        $this->subject = $this->effective($stored?->subject, $catalog['subject']);
        $this->heading = $this->effective($stored?->heading, $catalog['heading']);
        $this->message = $this->effective($stored?->message, $catalog['message']);
        $this->button = $this->effective($stored?->button_label, $catalog['button']);

        foreach ($catalog['blocks'] as $blockKey => $def) {
            $storedBlocks = is_array($stored?->blocks) ? $stored->blocks : [];
            $this->blocks[$blockKey] = array_key_exists($blockKey, $storedBlocks)
                ? (bool) $storedBlocks[$blockKey]
                : (bool) $def['default'];
        }
    }

    public function render(): View
    {
        $catalog = EmailTemplateCatalog::get($this->templateKey);
        $stored = $this->stored();

        return view('livewire.operator.operator-email-editor', [
            'viewer' => $this->viewer(),
            'templateKey' => $this->templateKey,
            'catalog' => $catalog,
            'blockDefs' => $catalog['blocks'],
            'vars' => $catalog['vars'],
            'previewHtml' => $this->previewHtml($catalog),
            'previewSubject' => $this->mergeSample($this->subject, $catalog),
            'enabled' => $stored === null ? true : (bool) $stored->enabled,
            'lastEdited' => $this->lastEdited($stored),
        ]);
    }

    /**
     * The live preview — the REAL `emails.{key}` blade rendered with real sample data, the DRAFT overlaid.
     *
     * @param  array<string, mixed>  $catalog
     */
    private function previewHtml(array $catalog): string
    {
        $base = EmailPreviewSample::viewData($this->templateKey);
        if ($base === null) {
            return '<div style="padding:40px;font-family:Inter,Arial,sans-serif;color:#8A8079;font-size:13px;text-align:center;">Create a booking to preview this email with real sample data.</div>';
        }

        // Overlay the draft (merged with the sample merge-vars) onto the real blade data — the same keys the
        // Mailable passes, so the preview renders identically to the send.
        $base['heading'] = $this->mergeSample($this->heading, $catalog);
        $base['intro'] = $this->mergeSample($this->message, $catalog);
        $base['button'] = $this->mergeSample($this->button, $catalog);
        $base['blocks'] = $this->blocks;

        return view('emails.'.$catalog['preview'], $base)->render();
    }

    /**
     * Substitute the real merge chips with the SAMPLE values (so the preview reads naturally). The same chips
     * resolve from the real reservation/guest at send.
     *
     * @param  array<string, mixed>  $catalog
     */
    private function mergeSample(string $text, array $catalog): string
    {
        $samples = [
            'first_name' => 'Alex',
            'unit' => 'Apartment 2B',
            'nights' => '3',
            'reference' => 'VY-2043',
            'balance' => '€120.00',
        ];
        foreach ($catalog['vars'] as $var) {
            $text = str_replace('{'.$var.'}', (string) ($samples[$var] ?? '{'.$var.'}'), $text);
        }

        return $text;
    }

    private function stored(): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', (string) app(TenantContext::class)->id())
            ->where('template_key', $this->templateKey)
            ->first();
    }

    private function effective(?string $stored, string $default): string
    {
        return is_string($stored) && trim($stored) !== '' ? $stored : $default;
    }

    private function lastEdited(?EmailTemplate $stored): ?string
    {
        if ($stored === null || $stored->updated_at === null) {
            return null;
        }

        $who = $stored->updatedBy?->name;
        $when = Carbon::parse($stored->updated_at)->format('j M Y');

        return $who !== null ? "Last edited by {$who} · {$when}" : "Last edited {$when}";
    }
}
