@extends('shop.themes.luxury.layout')
@section('title', 'Secure Checkout | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-5xl mx-auto px-4 py-16" x-data="{qty:1, price:{{$product->sale_price??$product->regular_price}}, delivery:'inside', in:{{$client->delivery_charge_inside}}, out:{{$client->delivery_charge_outside}}}">
<div class="text-center mb-12"><h1 class="text-3xl font-heading text-primary tracking-[0.2em] uppercase mb-2">Checkout</h1><p class="text-xs text-gray-500 tracking-widest uppercase">Secure your piece</p></div>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-16">@csrf
<input type="hidden" name="product_id" value="{{$product->id}}"><input type="hidden" name="color" value="{{request('color')}}"><input type="hidden" name="size" value="{{request('size')}}"><input type="hidden" name="quantity" :value="qty">
<div class="space-y-8">
<div><label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-2">Full Name</label><input type="text" name="customer_name" required class="w-full border-b border-[#333] py-2 outline-none focus:border-primary bg-transparent text-white"></div>
<div><label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-2">Phone</label><input type="tel" name="customer_phone" required class="w-full border-b border-[#333] py-2 outline-none focus:border-primary bg-transparent text-white"></div>
<div><label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-2">Region</label><select name="delivery_area" x-model="delivery" class="w-full border-b border-[#333] py-2 outline-none focus:border-primary bg-transparent text-gray-300 uppercase text-xs tracking-widest"><option value="inside" class="bg-black">Inside Dhaka (৳{{$client->delivery_charge_inside}})</option><option value="outside" class="bg-black">Outside Dhaka (৳{{$client->delivery_charge_outside}})</option></select></div>
<div><label class="block text-[10px] text-gray-500 uppercase tracking-widest mb-2">Address</label><textarea name="shipping_address" required rows="2" class="w-full border-b border-[#333] py-2 outline-none focus:border-primary bg-transparent text-white"></textarea></div></div>
<div class="bg-[#0a0a0a] p-10 border border-[#222]">
<div class="flex gap-6 mb-10"><img src="{{asset('storage/'.$product->thumbnail)}}" class="w-20 h-24 object-cover border border-[#333]">
<div><p class="font-heading text-lg tracking-widest uppercase text-white">{{$product->name}}</p><p class="text-[10px] text-primary uppercase tracking-widest mt-2">{{request('color')}} {{request('size')}}</p></div></div>
<div class="flex justify-between text-xs tracking-widest uppercase text-gray-400 mb-6"><span>Quantity</span><div class="flex items-center gap-4"><button type="button" @click="if(qty>1)qty--" class="hover:text-primary">-</button><span x-text="qty" class="text-white"></span><button type="button" @click="qty++" class="hover:text-primary">+</button></div></div>
<div class="flex justify-between text-xs tracking-widest uppercase text-gray-400 mb-6 border-b border-[#222] pb-6"><span>Subtotal</span><span x-text="'৳'+(qty*price)" class="text-white"></span></div>
<div class="flex justify-between text-xs tracking-widest uppercase text-gray-400 mb-10"><span>Shipping</span><span x-text="'৳'+(delivery=='inside'?in:out)" class="text-white"></span></div>
<div class="flex justify-between font-heading text-xl text-primary mb-10 tracking-widest"><span class="uppercase">Total</span><span x-text="'৳'+((qty*price)+(delivery=='inside'?in:out))"></span></div>
<button type="submit" class="w-full bg-primary text-black py-4 text-xs font-bold uppercase tracking-[0.2em] hover:bg-white transition">Confirm Purchase</button></div>
</form></main>
@endsection