{{-- Stock Notify Button - Shows when product is out of stock --}}
@if($client->widget('stock_notify') ?? false)
<div class="mt-4">
    <p class="text-sm text-gray-600 mb-2">Get notified when back in stock:</p>
    <div class="flex gap-2">
        <input type="email" placeholder="Your email address"
               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
        <button type="button"
                class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition">
            Notify Me
        </button>
    </div>
</div>
@endif
