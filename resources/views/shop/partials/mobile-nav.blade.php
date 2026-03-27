{--
  📱 Reusable Mobile Bottom Navigation Bar
  Use: @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl])
  Works with all themes via CSS variable --mob-primary (defaults to primary color)
  Fully Responsive for Mobile, Tablet
--}}
<style>
  .mob-nav { display: none; }
  @media (max-width: 767px) {
    .mob-nav {
      display: flex;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 9999;
      background: #fff;
      border-top: 1px solid #e5e7eb;
      padding: 8px 0 env(safe-area-inset-bottom, 8px);
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    }
    .mob-nav a {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      font-size: 9px;
      font-weight: 700;
      color: #64748b;
      text-decoration: none;
      padding: 6px 2px;
      transition: color 0.2s;
    }
    .mob-nav a.active,
    .mob-nav a:hover {
      color: var(--mob-primary, #6366f1);
    }
    .mob-nav a i {
      font-size: 18px;
    }
    .mob-nav a span {
      font-family: 'Hind Siliguri', sans-serif;
    }
    .mob-search-bar {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 10000;
      background: #fff;
      padding: 12px 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid #e5e7eb;
    }
    .mob-search-bar.open {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .mob-search-bar input {
      flex: 1;
      border: 2px solid var(--mob-primary, #6366f1);
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      outline: none;
      font-family: 'Hind Siliguri', sans-serif;
    }
    .mob-search-bar input:focus {
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .mob-search-bar button {
      background: var(--mob-primary, #6366f1);
      color: #fff;
      border: none;
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      flex-shrink: 0;
      transition: opacity 0.2s;
    }
    .mob-search-bar button:hover {
      opacity: 0.9;
    }
    main, footer {
      padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)) !important;
    }
  }
</style>

<div class="mob-nav" x-data>
    {{-- Home --}}
    <a href="{{ $baseUrl }}" title="হোম" {{ request()->url() == $baseUrl ? 'class="active"' : '' }}>
        <i class="fas fa-home"></i>
        <span>হোম</span>
    </a>

    {{-- Search toggle --}}
    <a href="#" title="সার্চ" @click.prevent="$dispatch('mob-search-open')">
        <i class="fas fa-search"></i>
        <span>সার্চ</span>
    </a>

    {{-- Track Order --}}
    <a href="{{ $clean ?? false ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" title="ট্র্যাক">
        <i class="fas fa-truck-fast"></i>
        <span>ট্র্যাক</span>
    </a>

    {{-- Chat/Call --}}
    @if($client->fb_page_id)
    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" title="চ্যাট">
        <i class="fab fa-facebook-messenger"></i>
        <span>চ্যাট</span>
    </a>
    @else
        @if($client->phone)
        <a href="tel:{{ $client->phone }}" title="কল">
            <i class="fas fa-phone-alt"></i>
            <span>কল</span>
        </a>
        @endif
    @endif
</div>

{{-- Mobile Search Overlay --}}
<div class="mob-search-bar" x-data="{ open: false }"
    x-on:mob-search-open.window="open = true; $nextTick(() => $refs.minput.focus())"
    :class="open ? 'open' : ''"
    @keydown.escape.window="open = false">
    
    <button @click="open = false" style="background: #e5e7eb; color: #374151;">
        <i class="fas fa-arrow-left text-sm"></i>
    </button>
    
    <form action="{{ $baseUrl }}" method="GET" class="flex flex-1 gap-2">
        <input x-ref="minput" type="text" name="search" value="{{ request('search') }}"
            placeholder="পণ্য খুঁজুন..." autocomplete="off">
        <button type="submit">
            <i class="fas fa-search text-sm"></i>
        </button>
    </form>
</div>
