@php $mobPrimary = $client->primary_color ?? '#f85606'; @endphp
<style>
@media(max-width:767px){
.mob-nav{display:flex;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#fff;border-top:1px solid #e5e7eb;padding:10px 0 env(safe-area-inset-bottom,10px);box-shadow:0 -4px 20px rgba(0,0,0,.08)}
.mob-nav a{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;font-size:9px;font-weight:700;color:#64748b;text-decoration:none;padding:4px 2px;transition:color .2s}
.mob-nav a.active,.mob-nav a:hover{color:{{ $mobPrimary }}}
.mob-nav a i{font-size:20px}
.mob-search{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#fff;padding:12px 16px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.mob-search.open{display:flex;gap:10px;align-items:center}
.mob-search input{flex:1;border:2px solid {{ $mobPrimary }};border-radius:12px;padding:14px 16px;font-size:15px;outline:none;font-family:'Hind Siliguri',sans-serif}
.mob-search button{background:{{ $mobPrimary }};color:#fff;border:none;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content;cursor:pointer}
main,footer{padding-bottom:calc(75px + env(safe-area-inset-bottom,0px))!important}
}
</style>

<nav class="mob-nav" x-data>
    <a href="{{ $baseUrl }}" title="হোম" class="{{ !request('category') && request()->route()->getName() !== 'shop.checkout' ? 'active' : '' }}">
        <i class="fas fa-home"></i><span>হোম</span>
    </a>

    <a href="{{$baseUrl}}?category=all" title="ক্যাটাগরি" class="{{ request('category') ? 'active' : '' }}">
        <i class="fas fa-th-large"></i><span>ক্যাটাগরি</span>
    </a>
    
    @php $mobCartCount = session()->has('cart') ? count(session()->get('cart')) : 0; @endphp
    <a href="{{$clean??false ? $baseUrl.'/cart' : route('shop.cart', $client->slug)}}" title="কার্ট" class="relative {{ request()->route()->getName() === 'shop.cart' || request()->is('*/cart') ? 'active' : '' }}">
        <i class="fas fa-shopping-cart"></i><span>কার্ট</span>
        @if($mobCartCount > 0)
            <span class="absolute top-0 right-2 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center transform translate-x-2 -translate-y-1">{{ $mobCartCount }}</span>
        @endif
    </a>

    <a href="{{ $clean ?? false ? $baseUrl.'/track' : route('shop.track',$client->slug) }}" title="ট্র্যাক">
        <i class="fas fa-truck-fast"></i><span>ট্র্যাক</span>
    </a>

    @if($client->fb_page_id ?? false)
        <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" title="চ্যাট"><i class="fab fa-facebook-messenger"></i><span>চ্যাট</span></a>
    @elseif($client->phone ?? false)
        <a href="tel:{{ $client->phone }}" title="কল"><i class="fas fa-phone-alt"></i><span>কল</span></a>
    @else
        <a href="#" title="সার্চ" @click.prevent="$dispatch('mob-search')"><i class="fas fa-search"></i><span>সার্চ</span></a>
    @endif
</nav>

<div class="mob-search" x-data="{open:false}" x-on:mob-search.window="open=true;$nextTick(()=>{$refs.in.focus()})" :class="open?'open':''" @keydown.escape.window="open=false">
    <button @click="open=false" style="background:#e5e7eb;color:#374151"><i class="fas fa-arrow-left text-sm"></i></button>
    <form action="{{ $baseUrl }}" method="GET" class="flex flex-1 gap-2">
        <input x-ref="in" type="text" name="search" value="{{ request('search') }}" placeholder="পণ্য খুঁজুন..." autocomplete="off">
        <button type="submit"><i class="fas fa-search text-sm"></i></button>
    </form>
</div>
