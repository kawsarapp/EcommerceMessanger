@extends('shop.themes.vegist.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

{{-- Breadcrumb --}}
<div class="bg-[#fcfdfa] py-6 mb-8 border-b border-gray-100">
    <div class="max-w-[800px] mx-auto px-4 xl:px-8 text-center text-[12px] text-gray-500 font-medium tracking-wide">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition">Home</a>
        <span class="mx-2">/</span>
        <span class="text-dark">Track Order</span>
    </div>
</div>

<div class="max-w-[800px] mx-auto px-4 xl:px-8 pb-16">
    <div class="bg-white p-8 md:p-12 border border-gray-100 rounded shadow-sm text-center">
        <i class="fas fa-search-location text-5xl text-primary opacity-20 mb-4"></i>
        <h1 class="text-2xl font-bold text-dark mb-2">Track Your Order</h1>
        <p class="text-sm text-gray-500 mb-8 max-w-sm mx-auto leading-relaxed">Enter your mobile number below to see the current status of your order.</p>
        
        <div class="max-w-md mx-auto">
            <form id="trackForm" class="flex flex-col gap-4">
                <input type="text" id="phone" name="order_id" placeholder="Mobile Number (e.g. 017XXXXXXX)" required class="w-full border border-gray-200 rounded px-5 py-3 text-sm focus:outline-none focus:border-primary transition font-mono">
                <button type="submit" class="w-full btn-primary !py-3 tracking-wide">Track Status</button>
            </form>
            <div id="trackResult" class="mt-8 text-left hidden"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('trackForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    let phone = document.getElementById('phone').value;
    let resDiv = document.getElementById('trackResult');
    resDiv.innerHTML = '<div class="text-center text-primary py-4"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
    resDiv.classList.remove('hidden');

    try {
        let response = await fetch(`{{ $baseUrl }}/api/track-order?phone=${phone}`);
        let data = await response.json();
        
        if(data.success && data.orders.length > 0) {
            let html = '<h3 class="font-bold text-lg text-dark mb-4 pb-2 border-b">Order History ('+phone+')</h3><div class="space-y-4">';
            data.orders.forEach(o => {
                let statusColor = 'bg-gray-100 text-gray-600';
                if(o.status == 'processing') statusColor = 'bg-blue-100 text-blue-600';
                if(o.status == 'shipped') statusColor = 'bg-purple-100 text-purple-600';
                if(o.status == 'delivered') statusColor = 'bg-green-100 text-green-600';
                if(o.status == 'cancelled') statusColor = 'bg-red-100 text-primary';
                
                html += `
                    <div class="border border-gray-100 rounded bg-[#fcfdfa] p-5 relative overflow-hidden transition hover:shadow-sm">
                        <div class="absolute right-0 top-0 w-1 h-full ${statusColor.split(' ')[0]}"></div>
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-bold text-dark text-sm">Order #${o.order_number}</h4>
                                <span class="text-[11px] text-gray-400 font-mono">${o.date}</span>
                            </div>
                            <span class="${statusColor} px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">${o.status}</span>
                        </div>
                        <div class="text-[13px] text-gray-600">
                            <strong>Total:</strong> ৳${parseFloat(o.total).toLocaleString()} 
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            resDiv.innerHTML = html;
        } else {
            resDiv.innerHTML = `<div class="bg-primary/5 text-primary p-4 rounded text-sm text-center border border-primary/20 flex items-center justify-center gap-2"><i class="fas fa-exclamation-circle"></i> No orders found for ${phone}</div>`;
        }
    } catch(e) {
        resDiv.innerHTML = `<div class="bg-primary/5 text-primary p-4 rounded text-sm text-center border border-primary/20">An error occurred. Please try again.</div>`;
    }
});
</script>
@endsection

