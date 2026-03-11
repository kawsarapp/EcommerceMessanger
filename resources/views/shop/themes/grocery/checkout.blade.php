@extends('shop.themes.grocery.layout')
@section('title', 'Checkout | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-4xl mx-auto px-4 py-8" x-data="{qty:{{request('qty',1)}}, price:{{$product->sale_price??$product->regular_price}}, delivery:'inside', in:{{$client->delivery_charge_inside}}, out:{{$client->delivery_charge_outside}}}">
<h1 class="text-2xl font-extrabold mb-6 text-gray-800 flex items-center gap-2"><i class="fas fa-check-circle text-primary"></i> Fast Checkout</h1>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">@csrf
<input type="hidden" name="product_id" value="{{$product->id}}"><input type="hidden" name="color" value="{{request('color')}}"><input type="hidden" name="size" value="{{request('size')}}"><input type="hidden" name="quantity" :value="qty">
<div class="space-y-4 bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
<div><label class="block text-sm font-bold text-gray-700 mb-1">Name *</label><input type="text" name="customer_name" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-primary font-bold"></div>
<div><label class="block text-sm font-bold text-gray-700 mb-1">Mobile *</label><input type="tel" name="customer_phone" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-primary font-bold"></div>
<div><label class="block text-sm font-bold text-gray-700 mb-1">Area *</label><select name="delivery_area" x-model="delivery" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-primary font-bold"><option value="inside">Inside Dhaka (৳{{$client->delivery_charge_inside}})</option><option value="outside">Outside Dhaka (৳{{$client->delivery_charge_outside}})</option></select></div>
<div><label class="block text-sm font-bold text-gray-700 mb-1">Address *</label><textarea name="shipping_address" required rows="2" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-primary font-bold"></textarea></div></div>
<div class="bg-primary/5 p-6 rounded-3xl border border-primary/20 flex flex-col h-fit">
<div class="flex gap-4 mb-6 bg-white p-3 rounded-2xl"><img src="{{asset('storage/'.$product->thumbnail)}}" class="w-16 h-16 object-contain mix-blend-multiply">
<div><p class="font-extrabold text-gray-800 leading-tight">{{$product->name}}</p><p class="text-xs font-bold text-primary mt-1">{{request('color')}} {{request('size')}}</p></div></div>
<div class="space-y-3 font-bold text-gray-600 text-sm">
<div class="flex justify-between"><span>Subtotal (x<span x-text="qty"></span>)</span><span x-text="'৳'+(qty*price)"></span></div>
<div class="flex justify-between"><span>Delivery</span><span x-text="'৳'+(delivery=='inside'?in:out)"></span></div></div>
<div class="flex justify-between font-extrabold text-2xl text-gray-900 mt-4 pt-4 border-t border-gray-200 mb-6"><span>Total</span><span x-text="'৳'+((qty*price)+(delivery=='inside'?in:out))"></span></div>
<button type="submit" class="w-full bg-primary text-white py-4 rounded-xl font-extrabold text-lg shadow-lg hover:bg-green-600 transition mt-auto">Place Order</button></div>
</form></main>
@endsection