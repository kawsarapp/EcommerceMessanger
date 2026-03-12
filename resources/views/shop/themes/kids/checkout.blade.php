@extends('shop.themes.kids.layout')
@section('title', 'Checkout | ' . $client->shop_name)
@section('content')
@php $baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); @endphp
<main class="max-w-4xl mx-auto px-4 py-10" x-data="{qty:{{request('qty',1)}}, price:{{$product->sale_price??$product->regular_price}}, delivery:'inside', in:{{$client->delivery_charge_inside}}, out:{{$client->delivery_charge_outside}}}">
<h1 class="text-3xl font-heading font-bold mb-8 text-center text-primary flex justify-center items-center gap-2"><i class="fas fa-star text-yellow-400"></i> Almost Yours!</h1>
<form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">@csrf
<input type="hidden" name="product_id" value="{{$product->id}}"><input type="hidden" name="color" value="{{request('color')}}"><input type="hidden" name="size" value="{{request('size')}}"><input type="hidden" name="quantity" :value="qty">
<div class="space-y-5 bg-white p-8 rounded-3xl border-2 border-gray-100 fun-shadow">
<div><label class="block text-sm font-bold text-gray-700 mb-2">Parents Name *</label><input type="text" name="customer_name" required class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl px-5 py-3.5 outline-none focus:border-primary focus:bg-white font-bold text-gray-800 transition"></div>
<div><label class="block text-sm font-bold text-gray-700 mb-2">Phone Number *</label><input type="tel" name="customer_phone" required class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl px-5 py-3.5 outline-none focus:border-primary focus:bg-white font-bold text-gray-800 transition"></div>
<div><label class="block text-sm font-bold text-gray-700 mb-2">Where to deliver? *</label><select name="delivery_area" x-model="delivery" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl px-5 py-3.5 outline-none focus:border-primary focus:bg-white font-bold text-gray-800 transition appearance-none"><option value="inside">Inside Dhaka (+৳{{$client->delivery_charge_inside}})</option><option value="outside">Outside Dhaka (+৳{{$client->delivery_charge_outside}})</option></select></div>
<div><label class="block text-sm font-bold text-gray-700 mb-2">Home Address *</label><textarea name="shipping_address" required rows="3" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl px-5 py-3.5 outline-none focus:border-primary focus:bg-white font-bold text-gray-800 transition"></textarea></div></div>
<div class="bg-blue-50 p-8 rounded-3xl border-2 border-blue-100 flex flex-col h-fit">
<div class="flex gap-4 mb-6 bg-white p-4 rounded-2xl shadow-sm"><img src="{{asset('storage/'.$product->thumbnail)}}" class="w-16 h-16 object-contain mix-blend-multiply">
<div><p class="font-bold text-gray-800 leading-tight">{{$product->name}}</p><p class="text-xs font-bold text-primary mt-1">{{request('color')}} {{request('size')}}</p></div></div>
<div class="space-y-4 font-bold text-gray-600 text-sm bg-white p-6 rounded-2xl shadow-sm">
<div class="flex justify-between"><span>Toys (x<span x-text="qty"></span>)</span><span x-text="'৳'+(qty*price)"></span></div>
<div class="flex justify-between"><span>Delivery</span><span x-text="'৳'+(delivery=='inside'?in:out)"></span></div>
<div class="flex justify-between font-heading text-2xl text-primary mt-4 pt-4 border-t-2 border-dashed border-gray-100"><span>Total</span><span x-text="'৳'+((qty*price)+(delivery=='inside'?in:out))"></span></div></div>
<button type="submit" class="w-full bg-primary text-white py-4 rounded-2xl font-heading text-xl fun-shadow hover:bg-primaryDark transition mt-8 flex justify-center items-center gap-2"><i class="fas fa-check-circle"></i> Confirm Order</button></div>
</form></main>
@endsection