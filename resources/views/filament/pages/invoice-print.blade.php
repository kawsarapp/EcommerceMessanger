@php
    $client = $order->client;
    // ক্লায়েন্টের প্রাইমারি কালার আনছি, না থাকলে ডিফল্ট ইন্ডিগো কালার
    $themeColor = $client->primary_color ?? '#4f46e5'; 
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - #{{ $order->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        :root {
            --brand-color: {{ $themeColor }};
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f3f4f6; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }
        
        .theme-text { color: var(--brand-color) !important; }
        .theme-bg { background-color: var(--brand-color) !important; color: #ffffff !important;}
        .theme-border { border-color: var(--brand-color) !important; }
        
        .invoice-box { 
            max-width: 850px; 
            margin: 40px auto; 
            background: white; 
            padding: 50px; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); 
            border-radius: 12px; 
        }

        @media print {
            body { background-color: white; }
            .invoice-box { margin: 0; padding: 20px; box-shadow: none; width: 100%; border: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="text-gray-800 text-sm">

    <div class="max-w-[850px] mx-auto mt-6 mb-2 flex justify-between items-center no-print">
        <button onclick="window.close()" class="text-gray-500 hover:text-gray-800 font-semibold flex items-center gap-1 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Close
        </button>
        <button onclick="window.print()" class="theme-bg hover:opacity-90 text-white font-bold py-2.5 px-6 rounded-lg shadow-md inline-flex items-center gap-2 transition">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Print Invoice
        </button>
    </div>

    <div class="invoice-box border-t-8 theme-border relative overflow-hidden">
        
        <div class="flex justify-between items-start pb-8 border-b border-gray-100">
            <div class="w-1/2">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" alt="Shop Logo" class="h-14 mb-3 object-contain">
                @endif
                <h1 class="text-2xl font-extrabold text-gray-900">{{ $client->shop_name }}</h1>
                @if($client->address)
                    <p class="text-gray-500 mt-1 whitespace-pre-wrap">{{ $client->address }}</p>
                @endif
                <p class="text-gray-500 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    {{ $client->phone ?? 'Phone not provided' }}
                </p>
                <p class="text-gray-500 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    {{ $client->custom_domain ?? route('shop.show', $client->slug) }}
                </p>
            </div>
            <div class="w-1/2 text-right">
                <h2 class="text-4xl font-black theme-text tracking-widest uppercase mb-2">INVOICE</h2>
                <div class="inline-block text-left bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-gray-600 mb-1"><strong>Invoice No:</strong> #INV-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</p>
                    <p class="text-gray-600 mb-1"><strong>Date:</strong> {{ $order->created_at->format('d M, Y h:i A') }}</p>
                    <p class="text-gray-600"><strong>Payment:</strong> <span class="px-2 py-0.5 bg-white border border-gray-200 rounded font-bold uppercase text-xs">{{ $order->payment_method }}</span></p>
                </div>
            </div>
        </div>

        <div class="flex justify-between my-8">
            <div class="w-1/2 pr-4">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-200 pb-1">Billed / Shipped To:</h3>
                <p class="text-lg font-bold text-gray-800">{{ $order->customer_name }}</p>
                <p class="text-gray-600 font-medium">{{ $order->customer_phone }}</p>
                <p class="text-gray-600 mt-1">{{ $order->shipping_address }}</p>
                @if($order->district || $order->division)
                    <p class="text-gray-600">{{ $order->district }}{{ $order->division ? ', ' . $order->division : '' }}</p>
                @endif
            </div>
            <div class="w-1/2 pl-4">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-3 border-b border-gray-200 pb-1">Order Status:</h3>
                <p class="text-gray-600 mb-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                        {{ $order->order_status === 'delivered' ? 'bg-green-100 text-green-800' : ($order->order_status === 'shipped' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $order->order_status === 'delivered' ? 'bg-green-500' : ($order->order_status === 'shipped' ? 'bg-blue-500' : 'bg-yellow-500') }}"></span>
                        {{ $order->order_status }}
                    </span>
                </p>
                @if($order->courier_name)
                    <p class="text-gray-600 text-sm mt-3"><strong>Courier:</strong> {{ ucfirst($order->courier_name) }}</p>
                    <p class="text-gray-600 text-sm"><strong>Tracking Code:</strong> <span class="font-mono bg-gray-100 px-1 rounded">{{ $order->tracking_code }}</span></p>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="theme-bg">
                        <th class="p-3 text-xs font-bold uppercase tracking-wider">Item Description</th>
                        <th class="p-3 text-xs font-bold uppercase tracking-wider text-center w-20">Qty</th>
                        <th class="p-3 text-xs font-bold uppercase tracking-wider text-right w-32">Unit Price</th>
                        <th class="p-3 text-xs font-bold uppercase tracking-wider text-right w-32">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @php $subtotal = 0; @endphp
                    @foreach($order->orderItems as $item)
                        @php 
                            $itemTotal = floatval($item->price) * floatval($item->quantity);
                            $subtotal += $itemTotal; 
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4">
                                <p class="font-bold text-gray-800">{{ $item->product->name ?? 'Unknown Product' }}</p>
                                @if($order->admin_note)
                                    <p class="text-xs text-gray-500 mt-1"><span class="font-semibold">Note/Variant:</span> {{ $order->admin_note }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-center text-gray-800 font-semibold">{{ $item->quantity }}</td>
                            <td class="p-4 text-right text-gray-600">৳{{ number_format($item->price, 2) }}</td>
                            <td class="p-4 text-right font-bold text-gray-800">৳{{ number_format($itemTotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-10">
            <div class="w-full md:w-1/2 lg:w-1/3">
                <div class="flex justify-between py-2 text-sm">
                    <span class="text-gray-600 font-semibold">Subtotal:</span>
                    <span class="text-gray-800 font-medium">৳{{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm border-b border-gray-200">
                    <span class="text-gray-600 font-semibold">Delivery Charge:</span>
                    <span class="text-gray-800 font-medium">৳{{ number_format((float)$order->total_amount - $subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-4 mt-1">
                    <span class="text-xl font-black text-gray-800">Grand Total:</span>
                    <span class="text-2xl font-black theme-text">৳{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-8 flex justify-between items-end">
            <div>
                <p class="text-gray-800 font-bold text-lg">Thank you for your order!</p>
                <p class="text-xs text-gray-500 mt-1">If you have any questions, please reach out to our support.</p>
            </div>
            
            <div class="text-right">
                <p class="text-[10px] text-gray-400 font-medium">Powered by</p>
                <p class="text-xs text-gray-600 font-bold tracking-wide">Automated SAAS Platform</p>
            </div>
        </div>
    </div>
    
    <script>
        // আপনি চাইলে পেজ লোড হওয়ার সাথে সাথে প্রিন্ট পপআপ ওপেন করতে নিচের কোডটি আনকমেন্ট করতে পারেন
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>