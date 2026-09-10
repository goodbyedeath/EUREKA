{{--
    Modal repair layer.

    The app has 52 hand-rolled modals across 20 views. They share one structure — a
    `.fixed.inset-0` overlay with a panel inside — but each was written separately, so the
    same three faults repeat: viewport units that include the browser's address bar, panels
    taller than the screen with no way to scroll them, and a single z-index for everything
    so stacked modals have no order.

    Rewriting all 52 is a large, risky change. This normalises them where they stand.
    Panels are told apart from backdrops by their shadow: every panel carries shadow-xl or
    shadow-2xl, no backdrop does.
--}}
<style>
    /* 1. Real viewport height.
       100vh counts the address bar that mobile browsers hide as you scroll, so a modal
       sized to it is always taller than what the user can actually see — the bottom, and
       usually the save button, sits under the chrome. */
    @supports (height: 100dvh) {
        .fixed.inset-0 .min-h-screen { min-height: 100dvh; }
        .fixed.inset-0 .h-screen    { height: 100dvh; }
    }

    /* Removed: a rule that gave every modal panel a max-height and overflow.
       It was meant to stop tall panels running off a phone, but many panels here are
       `inline-block`, and a non-visible overflow changes how an inline-block aligns —
       so it moved every modal it touched. Two attempts to scope it were not enough to
       make it safe, and the sizing problem it solved is milder than the breakage it
       caused. Tall forms are being turned into pages instead, which removes the need. */

    /* 3. Stacking. Every overlay was written as z-50, so a modal opened on top of another
       lands on the same layer and which one wins is down to document order. The script
       below hands out increasing values from this base. */
    body.modal-open { overflow: hidden; }

    /* Alpine's x-cloak, in case a view forgot it — without this a modal flashes on screen
       for one frame during page load, which reads as flicker. */
    [x-cloak] { display: none !important; }
</style>

<script>
(function () {
    'use strict';

    // A modal is "showing" when it is in the DOM and actually painted. Alpine toggles
    // display, Livewire adds and removes nodes, so neither event alone is reliable —
    // observing the DOM catches both.
    var BASE_Z = 50;

    function visibleOverlays() {
        var out = [];
        var nodes = document.querySelectorAll('.fixed.inset-0');

        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];

            // Some full-screen layers are not modals and must keep the z-index their own
            // layout gave them. The mobile navigation scrim is one: it is .fixed.inset-0,
            // it is outermost, and handing it z-50 put it ON TOP of the z-40 sidebar it
            // sits behind — the rail was visible but every tap landed on the scrim, which
            // closed it. Anything marked data-overlay-ignore is left alone entirely.
            if (el.hasAttribute('data-overlay-ignore')) continue;

            // A backdrop is ALSO .fixed.inset-0 and sits inside its own overlay. Treating
            // it as an overlay handed it a z-index above its parent, and since it is a
            // sibling of the panel it then covered the very dialog it was meant to dim —
            // every still-modal screen went grey and stopped responding. Only outermost
            // overlays get a layer.
            if (el.parentElement && el.parentElement.closest('.fixed.inset-0')) continue;

            // offsetParent is null for display:none; a zero-height overlay is not showing.
            if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                var s = window.getComputedStyle(el);
                if (s.display !== 'none' && s.visibility !== 'hidden' && s.opacity !== '0') {
                    out.push(el);
                }
            }
        }
        return out;
    }

    function sync() {
        var open = visibleOverlays();

        // Lock the page behind the modal. Without this a phone scrolls the list under the
        // dialog while the dialog stays put, which is the single most confusing thing a
        // modal can do on a touch screen.
        document.body.classList.toggle('modal-open', open.length > 0);

        // Give each open overlay its own layer, in the order they appear. Same-z-index
        // stacking is why two open modals look tangled.
        //
        // Only written when it actually differs. Writing the same value still rewrites the
        // style attribute, which the observer below sees as a mutation, which schedules
        // another pass — a loop that never settles and quietly burns the phone's battery.
        for (var i = 0; i < open.length; i++) {
            var z = String(BASE_Z + i * 10);
            if (open[i].style.zIndex !== z) open[i].style.zIndex = z;
        }
    }

    // Coalesce bursts: Livewire can touch the DOM many times in one update, and running
    // this per mutation is what makes a re-render look like a flicker.
    var queued = false;
    function schedule() {
        if (queued) return;
        queued = true;
        requestAnimationFrame(function () {
            queued = false;
            sync();
        });
    }

    function start() {
        sync();
        new MutationObserver(schedule).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'hidden'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    // Livewire replaces nodes wholesale on update; re-checking afterwards keeps the lock
    // honest when a modal is closed by a server round-trip rather than by Alpine.
    document.addEventListener('livewire:navigated', schedule);
    document.addEventListener('livewire:update', schedule);
})();
</script>
