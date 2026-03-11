@extends('shop.themes.fashion.layout')
@section('title', 'Checkout | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-5xl mx-auto px-4 py-12" x-data="{qty:1, price:{{$product->sale_price??$product->regular_price}}, delivery:'inside', in:{{$client->delivery_charge_inside}}, out:{{$client->delivery_charge_outside}}}">
<h1 class="text-3xl font-heading text-center mb-10">Checkout</h1>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-12">@csrf
<input type="hidden" name="product_id" value="{{$product->id}}"><input type="hidden" name="color" value="{{request('color')}}"><input type="hidden" name="size" value="{{request('size')}}"><input type="hidden" name="quantity" :value="qty">
<div class="space-y-6">
<div><label class="block text-xs uppercase tracking-widest mb-2">Full Name *</label><input type="text" name="customer_name" required class="w-full border-b border-gray-300 py-2 outline-none focus:border-black bg-transparent"></div>
<div><label class="block text-xs uppercase tracking-widest mb-2">Phone *</label><input type="tel" name="customer_phone" required class="w-full border-b border-gray-300 py-2 outline-none focus:border-black bg-transparent"></div>
<div><label class="block text-xs uppercase tracking-widest mb-2">Delivery Area *</label><select name="delivery_area" x-model="delivery" class="w-full border-b border-gray-300 py-2 outline-none focus:border-black bg-transparent uppercase text-xs tracking-widest"><option value="inside">Inside Dhaka (৳{{$client->delivery_charge_inside}})</option><option value="outside">Outside Dhaka (৳{{$client->delivery_charge_outside}})</option></select></div>
<div><label class="block text-xs uppercase tracking-widest mb-2">Address *</label><textarea name="shipping_address" required rows="2" class="w-full border-b border-gray-300 py-2 outline-none focus:border-black bg-transparent"></textarea></div></div>
<div class="bg-gray-50 p-8 border border-gray-100"><h3 class="font-heading text-xl mb-6">Order Summary</h3>
<div class="flex gap-4 mb-6"><img src="{{asset('storage/'.$product->thumbnail)}}" class="w-16 h-20 object-cover">
<div><p class="font-heading">{{$product->name}}</p><p class="text-xs text-gray-500 uppercase mt-1">{{request('color')}} {{request('size')}}</p></div></div>
<div class="flex justify-between text-sm mb-4"><span>Quantity</span><div class="flex items-center gap-4"><button type="button" @click="if(qty>1)qty--">-</button><span x-text="qty"></span><button type="button" @click="qty++">+</button></div></div>
<div class="flex justify-between text-sm mb-4"><span>Subtotal</span><span x-text="'৳'+(qty*price)"></span></div>
<div class="flex justify-between text-sm mb-4 border-b pb-4"><span>Delivery</span><span x-text="'৳'+(delivery=='inside'?in:out)"></span></div>
<div class="flex justify-between font-bold text-lg mb-8"><span class="uppercase tracking-widest text-sm">Total</span><span x-text="'৳'+((qty*price)+(delivery=='inside'?in:out))"></span></div>
<button type="submit" class="w-full bg-black text-white py-4 text-xs uppercase tracking-[0.2em] hover:bg-gray-800">Confirm Order</button></div>
</form></main>
@endsection