@php
    // One "Send on WhatsApp" control, shared by every screen that can send a patient something.
    //
    //   $items  one entry per thing this record can send:
    //             ['label' => 'Lab report', 'hint' => '…', 'url' => route(...),
    //              'class' => 'extra-form-class', 'attrs' => 'data-foo']
    //           Build the list with the caller's own conditions and drop what does not apply --
    //           an empty list renders nothing at all rather than a button that opens on nothing.
    //   $size   button size to match the row it sits in ('sm' default, 'xs' inside tables)
    //   $label  button text; pass '' for the icon alone, which is what a table row has space for
    //           (the glyph is recognisable enough on its own, and the title still spells it out)
    //   $disabled  why this cannot be used right now, e.g. no phone number on file. Shown greyed
    //           with the reason on hover rather than hidden: a control that silently disappears
    //           reads as a missing feature, and someone goes looking for it instead of adding the
    //           number that would bring it back.
    //
    // Every item posts. A send is not something a stray link preview or a back button should be
    // able to trigger on a patient's phone.
    $waItems = array_values(array_filter($items ?? []));
    $waSize  = $size ?? 'sm';
    $waLabel = $label ?? 'Send on WhatsApp';
    $waIcon  = $waLabel === '';
    $waOff   = $disabled ?? null;
@endphp

@if (count($waItems) || $waOff)
<span class="wa-send">
    {{-- aria-disabled rather than the disabled attribute: a disabled button fires no mouse events
         in most browsers, so the title explaining why would never appear. --}}
    <button type="button" class="btn btn-{{ $waSize }} wa-send-btn {{ $waIcon ? 'wa-send-icon' : '' }} {{ $waOff ? 'wa-send-off' : '' }}"
            aria-expanded="false" @if ($waOff) aria-disabled="true" @endif
            title="{{ $waOff ?: 'Send this to the patient on WhatsApp' }}">
        <i class="tio-whatsapp"></i>
        @if (!$waIcon)
        {{ $waLabel }}<i class="tio-chevron-down wa-send-chev"></i>
        @endif
    </button>
    @if (!$waOff)
    <div class="wa-send-menu" hidden>
        <p class="wa-send-head">Send to the patient</p>
        @foreach ($waItems as $waItem)
        <form method="post" action="{{ $waItem['url'] }}"
              class="wa-send-form mb-0 {{ $waItem['class'] ?? '' }}" {!! $waItem['attrs'] ?? '' !!}>
            @csrf
            <button type="submit" class="wa-send-item">
                <span class="wa-send-name">{{ $waItem['label'] }}</span>
                @if (!empty($waItem['hint']))
                <span class="wa-send-hint">{{ $waItem['hint'] }}</span>
                @endif
            </button>
        </form>
        @endforeach
    </div>
    @endif
</span>
@endif

@once
<style>
.wa-send { position: relative; display: inline-block; }
/* Painted here rather than borrowed from btn-outline-success: this control sits in the lab and
   radiology chrome, which defines its own button classes, and a send action that reads as the
   quiet outline beside a solid Resend gets missed. .btn.wa-send-btn outranks any single-class
   theme rule, so it lands the same on every screen whatever order the stylesheets load in. */
.btn.wa-send-btn {
    background: #18b345; border: 1px solid #18b345; color: #fff;
}
.btn.wa-send-btn:hover, .btn.wa-send-btn:focus, .btn.wa-send-btn[aria-expanded="true"] {
    background: #14983b; border-color: #14983b; color: #fff;
}
.btn.wa-send-btn i { color: #fff; }
/* Icon alone, for a table row where four controls share one column. */
.btn.wa-send-icon { padding-left: 9px; padding-right: 9px; }
.btn.wa-send-icon i { margin: 0; }
.wa-send-chev { font-size: 10px; margin-left: 5px; transition: transform .15s; display: inline-block; }
.wa-send-btn[aria-expanded="true"] .wa-send-chev { transform: rotate(180deg); }
.wa-send-menu {
    position: fixed; z-index: 1080; width: 268px;
    background: #fff; border: 1px solid #dbe3ec; border-radius: 10px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, .18); padding: 6px;
    text-align: left; color: #0f172a;
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.wa-send-menu[hidden] { display: none !important; }
.wa-send-head {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #94a3b8; margin: 4px 8px 6px;
}
.wa-send-item {
    display: block; width: 100%; text-align: left; border: 0; background: none;
    padding: 7px 8px; border-radius: 7px; cursor: pointer;
}
.wa-send-item:hover { background: #eefaf3; }
.wa-send-name { display: block; font-size: 13px; font-weight: 600; color: #0f172a; }
.wa-send-hint { display: block; font-size: 11px; color: #94a3b8; line-height: 1.35; margin-top: 1px; }
.wa-send-off { opacity: .5; cursor: not-allowed; }
.wa-send-off .wa-send-chev { display: none; }
</style>

<script>
(function () {
    // Delegated rather than wired per control: the lab and radiology screens render one of these
    // on every row, and the menu is positioned against the viewport because those rows sit inside
    // scrolling table wrappers that would otherwise clip it.
    function closeAll(except) {
        [].forEach.call(document.querySelectorAll('.wa-send-menu'), function (m) {
            if (m === except) return;
            m.hidden = true;
            var b = m.parentNode.querySelector('.wa-send-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }

    function place(menu, btn) {
        var r = btn.getBoundingClientRect();
        var w = menu.offsetWidth;
        var left = Math.min(r.right - w, window.innerWidth - w - 8);
        menu.style.top  = (r.bottom + 6) + 'px';
        menu.style.left = Math.max(8, left) + 'px';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.wa-send-btn');
        if (!btn) {
            // A click on an item is a submit; let it through, just tidy the menu away.
            closeAll(null);
            return;
        }
        e.preventDefault();
        if (btn.classList.contains('wa-send-off')) return;
        var menu = btn.parentNode.querySelector('.wa-send-menu');
        if (!menu) return;
        var open = menu.hidden;
        closeAll(menu);
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) place(menu, btn);
    });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });
    // Fixed to the viewport, so it would otherwise drift away from its button.
    window.addEventListener('scroll', function () { closeAll(null); }, true);
    window.addEventListener('resize', function () { closeAll(null); });
})();
</script>
@endonce
