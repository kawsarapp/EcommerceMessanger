{{--
    Flash Sale Bar partial - include this in all theme layouts right after <body>
    Usage: @include('shop.partials.flash-sale-bar', ['client' => $client])
--}}
@php
    $activeFlashSale = null;
    $flashCountdown = 0;
    if ($client && $client->canAccessFeature('allow_flash_sale') && $client->sellerEnabled('flash_sale')) {
        $activeFlashSale = \App\Models\FlashSale::where('client_id', $client->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at', 'asc')
            ->first();
        if ($activeFlashSale) {
            $flashCountdown = max(0, (int) now()->diffInSeconds($activeFlashSale->ends_at, false));
        }
    }
@endphp

@if($activeFlashSale)
<div id="fs-banner" style="background:linear-gradient(135deg,#ff416c,#ff4b2b);color:#fff;padding:10px 16px;display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;position:relative;z-index:9999;box-shadow:0 2px 12px rgba(255,65,108,.45);">
    <span style="font-size:1.3rem;">⚡</span>
    <div style="text-align:center;line-height:1.25;">
        <strong style="font-size:1rem;letter-spacing:.3px;">{{ $activeFlashSale->title }}</strong>
        @if($activeFlashSale->description)
            <div style="font-size:.78rem;opacity:.9;">{{ $activeFlashSale->description }}</div>
        @endif
    </div>
    <span style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);border-radius:50px;padding:3px 13px;font-size:.9rem;font-weight:700;white-space:nowrap;">
        @if($activeFlashSale->discount_type==='percent')
            {{ number_format($activeFlashSale->discount_percent,0) }}% OFF
        @else
            ৳{{ number_format($activeFlashSale->discount_amount,0) }} OFF
        @endif
    </span>
    <div style="display:flex;align-items:center;gap:6px;">
        <span style="font-size:.78rem;opacity:.85;">শেষ হবে:</span>
        <div style="display:flex;gap:4px;">
            @foreach(['cd-h'=>'ঘন্টা','cd-m'=>'মি','cd-s'=>'সে'] as $id=>$lbl)
            <div style="background:rgba(255,255,255,.18);border-radius:5px;padding:3px 7px;text-align:center;min-width:38px;">
                <span id="{{$id}}" style="display:block;font-size:1rem;font-weight:700;line-height:1.2;">00</span>
                <small style="font-size:.55rem;opacity:.85;">{{$lbl}}</small>
            </div>
            @if($lbl!=='সে')<span style="font-size:1rem;font-weight:bold;margin-top:-2px;">:</span>@endif
            @endforeach
        </div>
    </div>
    <button onclick="document.getElementById('fs-banner').style.display='none';" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;position:absolute;right:10px;top:50%;transform:translateY(-50%);opacity:.8;" aria-label="Close">✕</button>
</div>

<script>
(function(){
    let s={{$flashCountdown}};
    function tick(){
        if(s<=0){var b=document.getElementById('fs-banner');if(b)b.style.display='none';return;}
        var h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sc=s%60,p=function(n){return String(n).padStart(2,'0');};
        var eh=document.getElementById('cd-h'),em=document.getElementById('cd-m'),es=document.getElementById('cd-s');
        if(eh)eh.textContent=p(h);if(em)em.textContent=p(m);if(es)es.textContent=p(sc);
        s--;
    }
    tick();setInterval(tick,1000);
})();
</script>
@endif
