@extends('shop.themes.modern.layout')
@section('title', 'Checkout | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-5xl mx-auto px-6 py-12 md:py-20" x-data="{qty:{{request('qty',1)}}, price:{{$product->sale_price??$product->regular_price}}, delivery:'inside', in:{{$client->delivery_charge_inside}}, out:{{$client->delivery_charge_outside}}}">
<div class="mb-12"><h1 class="text-4xl font-extrabold tracking-tighter mb-2">Checkout.</h1><p class="text-sm text-gray-500 font-medium">Please provide your shipping details.</p></div>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">@csrf
<input type="hidden" name="product_id" value="{{$product->id}}"><input type="hidden" name="color" value="{{request('color')}}"><input type="hidden" name="size" value="{{request('size')}}"><input type="hidden" name="quantity" :value="qty">
<div class="space-y-8">
<div><label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Full Name</label><input type="text" name="customer_name" required class="w-full bg-transparent border-b-2 border-gray-200 py-2 outline-none focus:border-black text-lg font-semibold transition"></div>
<div><label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Phone Number</label><input type="tel" name="customer_phone" required class="w-full bg-transparent border-b-2 border-gray-200 py-2 outline-none focus:border-black text-lg font-semibold transition"></div>
<div><label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Location</label><select name="delivery_area" x-model="delivery" class="w-full bg-transparent border-b-2 border-gray-200 py-2 outline-none focus:border-black text-base font-semibold transition appearance-none"><option value="inside">Inside Dhaka (৳{{$client->delivery_charge_inside}})</option><option value="outside">Outside Dhaka (৳{{$client->delivery_charge_outside}})</option></select></div>
<div><label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Full Address</label><textarea name="shipping_address" required rows="2" class="w-full bg-transparent border-b-2 border-gray-200 py-2 outline-none focus:border-black text-lg font-semibold transition"></textarea></div></div>
<div class="bg-gray-50 p-8 md:p-10 border border-gray-200 flex flex-col h-fit">
<div class="flex gap-6 mb-10"><div class="w-20 h-24 bg-white border border-gray-200 p-2"><img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply"></div>
<div><p class="font-bold text-lg leading-tight mb-2">{{$product->name}}</p><p class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{request('color')}} {{request('size')}}</p></div></div>
<div class="space-y-5 font-semibold text-gray-600 border-b border-gray-200 pb-8 mb-8">
<div class="flex justify-between"><span>Item Subtotal (x<span x-text="qty"></span>)</span><span x-text="'৳'+(qty*price)"></span></div>
<div class="flex justify-between"><span>Shipping</span><span x-text="'৳'+(delivery=='inside'?in:out)"></span></div></div>
<div class="flex justify-between font-extrabold text-2xl text-black mb-10"><span class="uppercase tracking-tighter">Total</span><span x-text="'৳'+((qty*price)+(delivery=='inside'?in:out))"></span></div>
<button type="submit" class="w-full bg-black text-white py-5 font-bold text-sm uppercase tracking-widest hover:bg-gray-800 transition">Place Order</button></div>
</form></main>
@endsection