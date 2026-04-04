@if(auth('customer')->check())
    <a href="{{ $clean ? 'https://'.$clean.'/customer/dashboard' : route('shop.customer.dashboard', $client->slug) }}" class="flex items-center gap-2 px-4 py-2 rounded-md hover:bg-black/5 dark:hover:bg-white/10 transition text-sm font-semibold">
        <i class="fas fa-user-circle"></i> <span class="hidden md:inline">{{ auth('customer')->user()->name }}</span>
    </a>
@else
    <a href="{{ $clean ? 'https://'.$clean.'/customer/login' : route('shop.customer.login', $client->slug) }}" class="flex items-center gap-2 px-4 py-2 rounded-md hover:bg-black/5 dark:hover:bg-white/10 transition text-sm font-semibold">
        <i class="fas fa-sign-in-alt"></i> <span class="hidden md:inline">Login</span>
    </a>
@endif
