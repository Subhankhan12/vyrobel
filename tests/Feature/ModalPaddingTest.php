<?php

declare(strict_types=1);

// D-MODAL-PAD (found 2026-09-29; fixed 2026-11-11, his go). The older `.vy-modal { padding: 26px }` stacked with the head / body / foot sections' own
// 24px, so a sectioned dialog held 233px of fields in a 350px phone dialog (🖥 measured on Guests, 390px). A dialog that HAS those sections lets them
// carry the padding; one with no head keeps its 26px. The layout is CSS, so the proof is a browser's (Guests 233 → 285px, Portfolio 300px, no sideways
// overflow); this pins that the rule exists, is not scoped to one page, and cannot be undone by the old rule coming later.

it('⚠️ a dialog with sections gives its own padding back to them, in the shared operator layout', function () {
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    expect($css)->toContain('.vy-modal:has(.vy-modal-head) { padding: 0; }');

    // the rule outranks both definitions of `.vy-modal` whatever their order: `:has()` takes its argument's specificity, so (0,2,0) beats (0,1,0)
    preg_match_all('/\.vy-modal\s*\{[^}]*padding:\s*26px/', $css, $old);
    expect($old[0])->toHaveCount(1);   // the older rule is the only one that sets 26px (a second would need this test thought through again)
});

it('every dialog that scoped the same fix to itself still does (they are the proof the shape was right)', function () {
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    expect($css)->toContain('.vy-modal.vyr-dlg { padding: 0; }')->and($css)->toContain('.vy-modal.vyo-dlg { padding: 0; }');
});
