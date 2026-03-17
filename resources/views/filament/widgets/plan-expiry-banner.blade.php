<div class="w-full">
@php
    $isExpired = $isExpired ?? true;
    $daysLeft  = $daysLeft ?? 0;
    $planName  = $planName ?? 'N/A';
    $expiresAt = $expiresAt ?? '—';
    $bgClass   = $isExpired ? 'from-red-600 to-red-700' : 'from-amber-500 to-orange-500';
    $icon      = $isExpired ? '🚨' : '⚠️';
@endphp

<div class="rounded-2xl bg-gradient-to-r {{ $bgClass }} text-white p-5 shadow-lg flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
    <div class="flex items-start gap-4">
        <div class="text-3xl leading-none mt-0.5">{{ $icon }}</div>
        <div>
            @if($isExpired)
                <h3 class="font-bold text-lg leading-tight">Your plan has expired!</h3>
                <p class="text-sm text-red-100 mt-1">
                    Your <strong>{{ $planName }}</strong> plan expired on <strong>{{ $expiresAt }}</strong>.
                    All features are now <strong>disabled</strong>. Staff members <strong>cannot login</strong>.
                    Your data will be deleted after <strong>2 months</strong> if not renewed.
                </p>
            @else
                <h3 class="font-bold text-lg leading-tight">Your plan expires in {{ $daysLeft }} day(s)!</h3>
                <p class="text-sm text-amber-100 mt-1">
                    Your <strong>{{ $planName }}</strong> plan will expire on <strong>{{ $expiresAt }}</strong>.
                    Please renew or upgrade your plan to avoid service interruption.
                </p>
            @endif
        </div>
    </div>
    <div class="flex flex-col sm:flex-row gap-2 shrink-0">
        <a href="{{ route('filament.admin.resources.plan-upgrade-requests.create') }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white text-gray-900 font-bold text-sm hover:bg-gray-100 transition shadow-md whitespace-nowrap">
            ⬆️ Request Upgrade
        </a>
        <a href="tel:{{ \App\Models\SiteSetting::first()?->phone }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-black/30 hover:bg-black/50 text-white font-bold text-sm transition whitespace-nowrap">
            📞 Contact Support
        </a>
    </div>
</div>
</div>
