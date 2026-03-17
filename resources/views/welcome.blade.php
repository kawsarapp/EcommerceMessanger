@extends('layouts.public')

@section('content')
@php
    $siteSettings = \App\Models\SiteSetting::first();
    // Default fallback values
    if (!$siteSettings) {
        $siteSettings = (object)[
            'site_name' => 'NeuralCart',
            'phone' => '01771545972',
            'email' => 'info@asianhost.net',
            'hero_badge' => 'বাংলাদেশে এই প্রথম - Next Gen AI Sales',
            'hero_title_part1' => 'আপনার বিজনেসকে করুন',
            'hero_title_part2' => 'Automated Machine',
            'hero_subtitle' => '২৪/৭ কাস্টমার সাপোর্ট, অটো অর্ডার কনফার্মেশন এবং নির্ভুল ইনভেন্টরি ম্যানেজমেন্ট। মানুষ ঘুমালেও, আপনার NeuralCart AI ঘুমাবে না।',
            'pain_points' => [
                ['icon' => 'fas fa-clock', 'title' => 'স্লো রেসপন্স = সেল লস', 'desc' => 'আপনি যখন ঘুমাচ্ছেন বা ব্যস্ত, তখন কাস্টমার মেসেজ দিচ্ছে।'],
                ['icon' => 'fas fa-wallet', 'title' => 'অতিরিক্ত স্টাফ খরচ', 'desc' => '২৪ ঘণ্টা সাপোর্ট দিতে গেলে ৩ শিফটে মানুষ লাগে।'],
                ['icon' => 'fas fa-exclamation-triangle', 'title' => 'ভুল অর্ডার ও ফ্রড', 'desc' => 'মানুষের ভুলে ভুল প্রোডাক্ট ডেলিভারি হয়।']
            ],
            'features' => [
                ['icon' => 'fas fa-comments', 'color_class' => 'blue', 'title' => 'Instant Reply', 'desc' => 'কাস্টমার মেসেজ দেওয়া মাত্র রিপ্লাই।'],
                ['icon' => 'fas fa-boxes', 'color_class' => 'purple', 'title' => 'Smart Inventory', 'desc' => 'স্টকে পণ্য না থাকলে অর্ডার নিবে না।'],
                ['icon' => 'fas fa-user-shield', 'color_class' => 'green', 'title' => 'Fraud Detection', 'desc' => 'ফেইক অর্ডার প্রোফাইল চিনতে পারে।'],
                ['icon' => 'fas fa-camera', 'color_class' => 'orange', 'title' => 'Visual Search', 'desc' => 'ছবি দিলে প্রোডাক্ট খুঁজে দেয়।'],
                ['icon' => 'fas fa-brain', 'color_class' => 'pink', 'title' => 'AI Convincing', 'desc' => 'মানুষের মতো দামাদামি করে।'],
                ['icon' => 'fas fa-chart-line', 'color_class' => 'cyan', 'title' => 'Daily Report', 'desc' => 'দিন শেষে টেলিগ্রামে সেলস রিপোর্ট দেয়।'],
            ],
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
@endphp

    {{-- Hero Section Widget --}}
    @include('components.public.hero', ['siteSettings' => $siteSettings])

    {{-- Pain Points Widget --}}
    @include('components.public.pain-points', ['siteSettings' => $siteSettings])

    {{-- Pricing Plans Call To Action preview could go here if extracted, but skipping specific home page pricing view duplication for now --}}

    {{-- Cost Comparison Widget --}}
    @include('components.public.cost-comparison', ['siteSettings' => $siteSettings])

    {{-- Core AI Features Widget --}}
    @include('components.public.features', ['siteSettings' => $siteSettings])

    {{-- Final Call to Action Widget --}}
    @include('components.public.cta', ['siteSettings' => $siteSettings])

@endsection