{{-- Polish Batch 4 · GATE 1 — the shared, framework-free double-submit guard. One delegated `submit` listener
     guards every <form data-guard-submit> on the page: the FIRST submit disables the submit button (+ swaps its
     label to data-busy-label) so a rapid SECOND submit is blocked → the action fires ONCE. Runs in the bubble
     phase AFTER any inline onsubmit="return confirm(…)", so a cancelled confirm (defaultPrevented) never locks the
     form. Self-guards against double-inclusion. Works with OR without Alpine (guest/public pages have no Alpine).
     🔒 Front-line UX guard ON TOP of the untouched server-side idempotency/atomicity — never a replacement. --}}
@once
<script>
    (function () {
        if (window.__vySubmitGuard) return;
        window.__vySubmitGuard = true;
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.nodeName !== 'FORM' || !form.hasAttribute('data-guard-submit')) return;
            if (e.defaultPrevented) return;                       // a cancelled confirm() — leave the form usable
            if (form.dataset.vySubmitting) { e.preventDefault(); return; }   // a second submit — block it
            form.dataset.vySubmitting = '1';
            var btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                var busy = btn.getAttribute('data-busy-label');
                if (busy) { btn.textContent = busy; }
            }
        });
    })();
</script>
@endonce
