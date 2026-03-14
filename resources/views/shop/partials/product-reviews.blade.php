{{-- 
    Product Reviews Partial - Dynamic reviews section
    Required variables: $product, $client
--}}
@php
    $reviews = $product->reviews()->where('is_visible', true)->latest()->take(10)->get();
    $avgRating = $reviews->avg('rating') ?? 0;
    $totalReviews = $product->reviews()->where('is_visible', true)->count();
@endphp

@if($totalReviews > 0)
<section class="mt-12 bg-white rounded-[2rem] border border-slate-100 p-8 md:p-12 shadow-soft">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-10">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-3">
                <i class="fas fa-star text-amber-400"></i> Customer Reviews
            </h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Based on {{ $totalReviews }} verified buyer{{ $totalReviews > 1 ? 's' : '' }}</p>
        </div>
        
        {{-- Average Rating Badge --}}
        <div class="flex items-center gap-3 bg-amber-50 px-5 py-3 rounded-2xl border border-amber-100">
            <span class="text-3xl font-extrabold text-amber-500">{{ number_format($avgRating, 1) }}</span>
            <div>
                <div class="flex text-amber-400 text-sm">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($avgRating))
                            <i class="fas fa-star"></i>
                        @elseif($i - $avgRating < 1)
                            <i class="fas fa-star-half-alt"></i>
                        @else
                            <i class="far fa-star"></i>
                        @endif
                    @endfor
                </div>
                <span class="text-[11px] text-amber-600 font-bold">{{ $totalReviews }} Reviews</span>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @foreach($reviews as $review)
        <div class="bg-slate-50/50 rounded-2xl p-6 border border-slate-100 hover:border-slate-200 transition">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 text-primary rounded-full flex items-center justify-center font-bold text-sm uppercase">
                        {{ substr($review->customer_name ?? 'C', 0, 1) }}
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 text-sm block">{{ $review->customer_name ?? 'Verified Buyer' }}</span>
                        <span class="text-[11px] text-slate-400 font-medium">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex text-amber-400 text-xs shrink-0">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $review->rating)
                            <i class="fas fa-star"></i>
                        @else
                            <i class="far fa-star text-slate-200"></i>
                        @endif
                    @endfor
                </div>
            </div>
            @if($review->comment)
                <p class="text-sm text-slate-600 font-medium leading-relaxed pl-[52px]">{{ $review->comment }}</p>
            @endif
        </div>
        @endforeach
    </div>
</section>
@endif
