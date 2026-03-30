{{-- 
    Homepage Offer Banner Partial
    Shown above category sections with countdown timer
    Required: $client
--}}
@if(!empty($client->homepage_banner_active) && (!$client->homepage_banner_timer || \Carbon\Carbon::parse($client->homepage_banner_timer)->isFuture()))
<section class="max-w-7xl mx-auto px-4 sm:px-6 mb-12">
    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-primary via-primary/90 to-primary/70 p-8 md:p-12 lg:p-16"
         x-data="{ 
            timer: null,
            days: 0, hours: 0, mins: 0, secs: 0,
            init() {
                @if($client->homepage_banner_timer)
                let end = new Date('{{ \Carbon\Carbon::parse($client->homepage_banner_timer)->toISOString() }}').getTime();
                this.timer = setInterval(() => {
                    let now = new Date().getTime();
                    let d = end - now;
                    if(d <= 0) { clearInterval(this.timer); return; }
                    this.days = Math.floor(d / 86400000);
                    this.hours = Math.floor((d % 86400000) / 3600000);
                    this.mins = Math.floor((d % 3600000) / 60000);
                    this.secs = Math.floor((d % 60000) / 1000);
                }, 1000);
                @endif
            }
         }">

        {{-- Decorative Blobs --}}
        <div class="absolute -top-20 -right-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center gap-8 lg:gap-16">
            {{-- Left: Content --}}
            <div class="flex-1 text-center lg:text-left">
                @if(!empty($client->homepage_banner_title))
                <h2 class="text-3xl md:text-5xl font-extrabold text-white leading-tight tracking-tight mb-4">
                    {{ $client->homepage_banner_title }}
                </h2>
                @endif
                @if(!empty($client->homepage_banner_subtitle))
                <p class="text-white/80 font-medium text-lg max-w-2xl mb-8">{{ $client->homepage_banner_subtitle }}</p>
                @endif

                {{-- Countdown Timer --}}
                @if($client->homepage_banner_timer && \Carbon\Carbon::parse($client->homepage_banner_timer)->isFuture())
                <div class="flex justify-center lg:justify-start gap-3 sm:gap-4 mb-8">
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl px-4 py-3 sm:px-6 sm:py-4 text-center min-w-[60px] sm:min-w-[75px] border border-white/20">
                        <span class="block text-2xl sm:text-3xl font-extrabold text-white" x-text="days">0</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-widest">Days</span>
                    </div>
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl px-4 py-3 sm:px-6 sm:py-4 text-center min-w-[60px] sm:min-w-[75px] border border-white/20">
                        <span class="block text-2xl sm:text-3xl font-extrabold text-white" x-text="hours">0</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-widest">Hours</span>
                    </div>
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl px-4 py-3 sm:px-6 sm:py-4 text-center min-w-[60px] sm:min-w-[75px] border border-white/20">
                        <span class="block text-2xl sm:text-3xl font-extrabold text-white" x-text="mins">0</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-widest">Mins</span>
                    </div>
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl px-4 py-3 sm:px-6 sm:py-4 text-center min-w-[60px] sm:min-w-[75px] border border-white/20">
                        <span class="block text-2xl sm:text-3xl font-extrabold text-white" x-text="secs">0</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-widest">Secs</span>
                    </div>
                </div>
                @endif

                @if(!empty($client->homepage_banner_link))
                <a href="{{ $client->homepage_banner_link }}" class="inline-flex items-center gap-3 bg-white text-primary font-bold text-sm uppercase tracking-widest px-8 py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                    Shop Now <i class="fas fa-arrow-right"></i>
                </a>
                @endif
            </div>

            {{-- Right: Image --}}
            @if(!empty($client->homepage_banner_image))
            <div class="w-full lg:w-2/5 flex-shrink-0">
                <img src="{{ asset('storage/' . $client->homepage_banner_image) }}" class="w-full rounded-2xl shadow-2xl object-cover max-h-72 lg:max-h-80" alt="Offer Banner">
            </div>
            @endif
        </div>
    </div>
</section>
@endif
