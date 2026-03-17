@extends('layouts.public', ['title' => 'Pricing Plans'])

@section('content')
@php
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order', 'asc')->get();
    
    $siteSettings = \App\Models\SiteSetting::first();
    if (!$siteSettings) {
        $siteSettings = (object)[
            'phone' => '01771545972',
            'cost_comparison' => [
                'manual_title' => 'Manual Human Team',
                'manual_scenario' => 'Scenario A: ১৫ জন মডারেটর (৩ শিফট)',
                'manual_salary' => '১,৫০,০০০ ৳',
                'manual_overhead' => '৮০,০০০ ৳',
                'manual_loss' => '২০,০০০ ৳',
                'manual_total' => '২,৫০,০০০ ৳',
                'ai_title' => 'NeuralCart AI',
                'ai_scenario' => 'Scenario B: Fully Automated (24/7)',
                'ai_salary' => '০ ৳ (Zero)',
                'ai_capacity' => 'UNLIMITED',
                'ai_accuracy' => '100% / <1 Sec Reply',
                'ai_total' => '৫,০০০ - ১০,০০০ ৳',
            ]
        ];
    }
    $cost = $siteSettings->cost_comparison ?: [];
@endphp

{{-- HERO FOR PRICING PAGE --}}
<section class="relative py-20 lg:py-28 overflow-hidden bg-white dark:bg-[#0a0a0a]">
    <div class="absolute inset-0 bg-[url('https://play.tailwindcss.com/img/grid.svg')] bg-center [mask-image:linear-gradient(180deg,white,rgba(255,255,255,0))] dark:opacity-10"></div>
    <div class="relative max-w-7xl mx-auto px-4 text-center">
        <span class="inline-block py-1.5 px-4 rounded-full bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-brand-400 font-bold text-sm mb-6 border border-brand-200 dark:border-brand-800 shadow-sm">
            💰 Simple, Transparent Pricing
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 dark:text-white mb-6 leading-tight bangla-font tracking-tight">
            আপনার বিজনেসের জন্য<br>
            <span class="gradient-text">সঠিক প্ল্যান বেছে নিন</span>
        </h1>
        <p class="text-xl text-gray-500 dark:text-gray-400 max-w-3xl mx-auto mb-10 bangla-font leading-relaxed">
            ছোট শুরু করুন, বড় হন। কোনো hidden charge নেই। যেকোনো সময় আপগ্রেড করুন।
        </p>
        <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-500 dark:text-gray-400 font-medium">
            <span class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> No Credit Card Required</span>
            <span class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Cancel Anytime</span>
            <span class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Instant Setup</span>
        </div>
    </div>
</section>

{{-- PLANS SECTION --}}
<section id="plans" class="py-16 px-4 sm:px-6 bg-gray-50 dark:bg-[#111] border-t border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @foreach($plans as $plan)
        <div class="plan-card relative bg-white dark:bg-[#161615] rounded-[2rem] border shadow-sm flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-2
            {{ $plan->is_featured ? 'border-brand-500 shadow-xl ring-4 ring-brand-500/10 z-10 lg:scale-[1.02]' : 'border-gray-200 dark:border-[#222] hover:shadow-lg' }}">

            {{-- Top Badge --}}
            @if($plan->badge_text || $plan->is_featured)
            <div class="text-center py-2.5 text-xs font-bold text-white tracking-wider uppercase"
                 style="background: linear-gradient(135deg, {{ $plan->color ?? '#F53003' }}, {{ $plan->color ?? '#F53003' }}cc)">
                @if($plan->badge_text)
                    {{ $plan->badge_text }}
                @else
                    ⭐ Most Popular
                @endif
            </div>
            @endif

            <div class="p-8 md:p-10 flex flex-col flex-1">

                {{-- Plan Name & Desc --}}
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shadow-sm"
                             style="background-color: {{ $plan->color ?? '#F53003' }}15">
                            <span>🚀</span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight" style="color: {{ $plan->color ?? '#F53003' }}">
                            {{ $plan->name }}
                        </h2>
                    </div>
                    @if($plan->description)
                    <p class="text-gray-500 dark:text-gray-400 text-sm md:text-base bangla-font leading-relaxed">{{ $plan->description }}</p>
                    @endif
                </div>

                {{-- Pricing --}}
                <div class="bg-gray-50 dark:bg-[#1a1a1a] rounded-[1.5rem] p-6 mb-8 border border-gray-100 dark:border-[#222] shadow-inner">
                    <div class="flex items-end gap-2 mb-2">
                        <span class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tighter">৳{{ number_format($plan->price) }}</span>
                        <span class="text-gray-500 font-medium mb-1">/month</span>
                    </div>
                    @if($plan->yearly_price)
                    @php
                        $savings = 0;
                        if ($plan->price > 0) {
                            $savings = round((($plan->price * 12 - $plan->yearly_price) / ($plan->price * 12)) * 100);
                        }
                    @endphp
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Yearly: <strong class="text-gray-900 dark:text-gray-200">৳{{ number_format($plan->yearly_price) }}</strong></span>
                        @if($savings > 0)
                        <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider">Save {{ $savings }}%</span>
                        @endif
                    </div>
                    @endif
                    @if($plan->trial_days > 0)
                    <div class="mt-3 flex items-center gap-1.5 text-sm text-brand-600 dark:text-brand-400 font-bold bg-brand-50 mx-auto dark:bg-brand-900/20 px-3 py-1.5 rounded-lg w-fit">
                        <i class="fas fa-gift text-brand-500 animate-pulse"></i>
                        {{ $plan->trial_days }}-day Free Trial
                    </div>
                    @endif
                </div>

                {{-- ─── Core Limits ─── --}}
                <div class="mb-6">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Core Features</p>
                    <div class="space-y-3.5 text-sm md:text-base">
                        {{-- Products --}}
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white bg-green-500 shadow-sm mt-0.5">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-bangla">
                                <span class="font-bold">{{ $plan->product_limit == 0 ? 'Unlimited' : number_format($plan->product_limit) }}</span> Products allowed
                            </span>
                        </div>

                        {{-- Orders --}}
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white bg-green-500 shadow-sm mt-0.5">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-bangla">
                                <span class="font-bold">{{ $plan->order_limit == 0 ? 'Unlimited' : number_format($plan->order_limit) }}</span> Orders per month
                            </span>
                        </div>

                        {{-- AI Messages --}}
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white bg-brand-500 shadow-sm mt-0.5">
                                <i class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-bangla">
                                <span class="font-bold">{{ $plan->ai_message_limit == 0 ? 'Unlimited' : number_format($plan->ai_message_limit) }}</span> AI Bot replies / month
                            </span>
                        </div>
                        
                        {{-- Add Staff --}}
                        @if(isset($plan->staff_account_limit))
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-blue-500 bg-blue-100 shadow-sm mt-0.5">
                                <i class="fas fa-user-plus text-[10px]"></i>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-bangla">
                                <span class="font-bold">{{ $plan->staff_account_limit == 0 ? 'Unlimited' : $plan->staff_account_limit }}</span> Staff account(s)
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Spacer --}}
                <div class="flex-1"></div>

                {{-- CTA --}}
                <div class="mt-8">
                    <a href="{{ route('filament.admin.auth.register') }}?plan={{ $plan->id }}"
                       class="w-full block text-center py-4 rounded-2xl font-bold text-lg transition-transform active:scale-95 shadow-md hover:shadow-xl"
                       style="background: linear-gradient(135deg, {{ $plan->color ?? '#F53003' }}, {{ $plan->color ?? '#F53003' }}dd); color: white;">
                        Select {{ $plan->name }} <i class="fas fa-arrow-right ml-1 opacity-80"></i>
                    </a>
                </div>

            </div>
        </div>
        @endforeach
        </div>

        {{-- Call to action below plans --}}
        <div class="text-center mt-20">
            <p class="text-gray-500 dark:text-gray-400 bangla-font text-lg mb-6">কোন প্ল্যান সম্পর্কে সন্দেহ আছে? নির্দ্বিধায় যোগাযোগ করুন!</p>
            <a href="tel:{{ $siteSettings->phone }}"
               class="inline-flex items-center gap-3 bg-white dark:bg-[#111] border-2 border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white px-8 py-4 rounded-full font-bold hover:border-brand-500 hover:text-brand-600 dark:hover:border-brand-500 dark:hover:text-brand-400 transition-all shadow-sm hover:shadow-md">
                <i class="fas fa-phone-alt text-brand-500"></i>
                আমাদের কল করুন: {{ $siteSettings->phone }}
            </a>
        </div>
    </div>
</section>

{{-- Cost Comparison Extracted via Included Widget --}}
@include('components.public.cost-comparison', ['siteSettings' => $siteSettings])

{{-- Final CTA --}}
<section class="py-20 bg-gray-50 dark:bg-[#0a0a0a] border-t border-gray-200 dark:border-[#222]">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white mb-6 bangla-font tracking-tight">🏆 সিদ্ধান্ত আপনার</h2>
        <div class="bg-brand-50 dark:bg-[#111] rounded-[2rem] p-8 md:p-12 border border-brand-100 dark:border-brand-900/30 shadow-lg">
            <p class="text-lg text-gray-700 dark:text-gray-300 mb-8 bangla-font leading-relaxed">
                একজন মডারেটরকে মাসে ১০,০০০ টাকা বেতন দিয়েও আপনি ২৪ ঘণ্টা সার্ভিস পাবেন না। ভুল হবে, সেল মিস হবে।
                আর আমাদের <strong class="text-brand-600 dark:text-brand-400 px-1">AI সিস্টেম</strong> আপনাকে দিচ্ছে নির্ভুল, দ্রুত এবং নন-স্টপ সার্ভিস।
            </p>
            <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center bg-gray-900 dark:bg-white text-white dark:text-black px-10 py-5 rounded-full font-bold text-lg hover:bg-brand-500 dark:hover:bg-brand-500 dark:hover:text-white transition-all shadow-xl hover:-translate-y-1">
                Start Your AI Journey Now <i class="fas fa-rocket ml-2 text-brand-200"></i>
            </a>
        </div>
    </div>
</section>

@endsection