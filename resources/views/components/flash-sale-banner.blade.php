@if($flashSale)
<div
    id="flash-sale-banner"
    class="flash-sale-banner"
    style="
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: #fff;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        font-family: inherit;
        position: relative;
        z-index: 999;
        box-shadow: 0 2px 12px rgba(255, 65, 108, 0.4);
    "
>
    {{-- Fire icon + title --}}
    <span style="font-size: 1.4rem;">⚡</span>
    <div style="text-align: center; line-height: 1.3;">
        <strong style="font-size: 1.05rem; letter-spacing: .5px;">
            {{ $flashSale->title }}
        </strong>
        @if($flashSale->description)
            <div style="font-size: .82rem; opacity: .9;">{{ $flashSale->description }}</div>
        @endif
    </div>

    {{-- Discount badge --}}
    <span style="
        background: rgba(255,255,255,.2);
        border: 1px solid rgba(255,255,255,.4);
        border-radius: 50px;
        padding: 4px 14px;
        font-size: .95rem;
        font-weight: 700;
        white-space: nowrap;
    ">
        @if($flashSale->discount_type === 'percent')
            {{ number_format($flashSale->discount_percent, 0) }}% OFF
        @else
            ৳{{ number_format($flashSale->discount_amount, 0) }} OFF
        @endif
    </span>

    {{-- Countdown timer --}}
    <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:.85rem; opacity:.9;">শেষ হবে:</span>
        <div id="flash-countdown" style="display:flex; gap:6px;">
            <div class="cd-box">
                <span id="cd-hours">00</span>
                <small>ঘন্টা</small>
            </div>
            <span style="font-size:1.2rem; font-weight:bold; margin-top:-4px;">:</span>
            <div class="cd-box">
                <span id="cd-minutes">00</span>
                <small>মিনিট</small>
            </div>
            <span style="font-size:1.2rem; font-weight:bold; margin-top:-4px;">:</span>
            <div class="cd-box">
                <span id="cd-seconds">00</span>
                <small>সেকেন্ড</small>
            </div>
        </div>
    </div>

    {{-- Close button --}}
    <button
        onclick="document.getElementById('flash-sale-banner').style.display='none';"
        style="
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3rem;
            cursor: pointer;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: .8;
            line-height: 1;
        "
        aria-label="Close flash sale banner"
    >✕</button>
</div>

<style>
.cd-box {
    background: rgba(255,255,255,.15);
    border-radius: 6px;
    padding: 4px 8px;
    text-align: center;
    min-width: 44px;
}
.cd-box span {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
}
.cd-box small {
    font-size: .6rem;
    opacity: .85;
    display: block;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    #flash-sale-banner {
        box-shadow: 0 2px 16px rgba(255, 65, 108, 0.6);
    }
}
</style>

<script>
(function () {
    let remaining = {{ $secondsRemaining }};

    function updateCountdown() {
        if (remaining <= 0) {
            const banner = document.getElementById('flash-sale-banner');
            if (banner) banner.style.display = 'none';
            return;
        }

        const h = Math.floor(remaining / 3600);
        const m = Math.floor((remaining % 3600) / 60);
        const s = remaining % 60;

        const pad = n => String(n).padStart(2, '0');

        const elH = document.getElementById('cd-hours');
        const elM = document.getElementById('cd-minutes');
        const elS = document.getElementById('cd-seconds');

        if (elH) elH.textContent = pad(h);
        if (elM) elM.textContent = pad(m);
        if (elS) elS.textContent = pad(s);

        remaining--;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>
@endif
