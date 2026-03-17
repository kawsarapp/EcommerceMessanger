@php
    $siteSettings = \App\Models\SiteSetting::first() ?? (object)[
        'site_name' => 'NeuralCart',
        'phone' => '01771545972',
        'email' => 'info@asianhost.net',
        'footer_text' => 'বাংলাদেশের ক্ষুদ্র ও মাঝারি উদ্যোক্তাদের জন্য তৈরি #১ AI সেলস অ্যাসিস্ট্যান্ট। আমরা প্রযুক্তির মাধ্যমে আপনার ব্যবসাকে সহজ করি।',
        'developer_name' => 'Kawsar Ahmed',
        'facebook_link' => '#',
        'youtube_link' => '#',
    ];
@endphp

<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $siteSettings->site_name }} {{ isset($title) ? '· ' . $title : '' }}</title>
    <meta name="description" content="{{ $siteSettings->footer_text }}">
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Instrument Sans', 'Plus Jakarta Sans', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff2f2',
                            100: '#ffe1e1',
                            400: '#f97316',
                            500: '#F53003',
                            600: '#d92902',
                            700: '#b52202',
                            900: '#1a0500',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Base optimizations */
        .gradient-text {
            background: linear-gradient(135deg, #F53003 0%, #FF750F 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .dark .glass-card {
            background: rgba(22, 22, 21, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
        }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #F53003; }
        .dark ::-webkit-scrollbar-track { background: #111; }
        .dark ::-webkit-scrollbar-thumb { background: #333; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #F53003; }
        
        @yield('custom_styles')
    </style>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] font-sans antialiased selection:bg-brand-500 selection:text-white flex flex-col min-h-screen">

    {{-- HEADER --}}
    @include('components.public.header', ['siteSettings' => $siteSettings])

    <main class="flex-grow pt-20">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include('components.public.footer', ['siteSettings' => $siteSettings])

    <script>
        // Efficient Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileBtn && mobileMenu) {
                mobileBtn.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                    const isHidden = mobileMenu.classList.contains('hidden');
                    mobileBtn.innerHTML = isHidden ? '<i class="fas fa-bars"></i>' : '<i class="fas fa-times"></i>';
                });
            }

            // Debounced scroll listener for header
            let scrollTimeout;
            const header = document.querySelector('header');
            window.addEventListener('scroll', () => {
                if (!scrollTimeout) {
                    scrollTimeout = setTimeout(() => {
                        if (window.scrollY > 20) {
                            header.classList.add('shadow-lg');
                        } else {
                            header.classList.remove('shadow-lg');
                        }
                        scrollTimeout = null;
                    }, 50); // 50ms throttle
                }
            }, { passive: true });
        });
    </script>

    @yield('scripts')
</body>
</html>
