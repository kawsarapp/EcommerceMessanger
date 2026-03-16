{{--
  📱 Reusable Mobile Bottom Navigation Bar
  Use: @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl])
  Works with all themes via CSS variable --mob-primary (defaults to primary color)
--}}
<style>
  .mob-nav{display:none}
  @media(max-width:767px){
    .mob-nav{display:flex;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#fff;border-top:1px solid #e5e7eb;padding:6px 0 env(safe-area-inset-bottom,6px);box-shadow:0 -4px 20px rgba(0,0,0,.08)}
    .mob-nav a{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;font-size:10px;font-weight:700;color:#64748b;text-decoration:none;padding:4px 2px;transition:color .2s}
    .mob-nav a.active,.mob-nav a:hover{color:var(--mob-primary,#6366f1)}
    .mob-nav a i{font-size:18px}
    .mob-search-bar{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#fff;padding:12px 16px;box-shadow:0 4px 20px rgba(0,0,0,.1);border-bottom:1px solid #e5e7eb}
    .mob-search-bar.open{display:flex;gap:10px;align-items:center}
    .mob-search-bar input{flex:1;border:2px solid var(--mob-primary,#6366f1);border-radius:999px;padding:10px 16px;font-size:14px;outline:none}
    .mob-search-bar button{background:var(--mob-primary,#6366f1);color:#fff;border:none;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
    main,footer{padding-bottom:calc(60px + env(safe-area-inset-bottom,0px))!important}
  }
</style>

<div class="mob-nav" x-data>
    <a href="{{$baseUrl}}" title="Home">
        <i class="fas fa-home"></i>Home
    </a>

    {{-- Search toggle --}}
    <a href="#" title="Search" @click.prevent="$dispatch('mob-search-open')">
        <i class="fas fa-search"></i>Search
    </a>

    {{-- Track Order --}}
    <a href="{{$clean??false ? $baseUrl.'/track-order' : route('shop.track', $client->slug)}}" title="Track">
        <i class="fas fa-truck-fast"></i>Track
    </a>

    {{-- Messenger --}}
    @if($client->fb_page_id)
    <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" title="Chat">
        <i class="fab fa-facebook-messenger"></i>Chat
    </a>
    @else
    @if($client->phone)
    <a href="tel:{{$client->phone}}" title="Call">
        <i class="fas fa-phone-alt"></i>Call
    </a>
    @endif
    @endif
</div>

{{-- Mobile Search Overlay --}}
<div class="mob-search-bar" x-data="{ open: false }"
    x-on:mob-search-open.window="open = true; $nextTick(() => $refs.minput.focus())"
    :class="open ? 'open' : ''"
    @keydown.escape.window="open = false">
    <button @click="open = false" style="background:#e5e7eb;color:#374151">
        <i class="fas fa-arrow-left text-sm"></i>
    </button>
    <form action="{{$baseUrl}}" method="GET" class="flex flex-1 gap-2">
        <input x-ref="minput" type="text" name="search" value="{{ request('search') }}"
            placeholder="Search products..." autocomplete="off">
        <button type="submit"><i class="fas fa-search text-sm"></i></button>
    </form>
</div>
