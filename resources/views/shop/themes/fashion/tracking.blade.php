@extends('shop.themes.fashion.layout')
@section('title', 'Track Order | ' . $client->shop_name)
@section('content')
<main class="max-w-3xl mx-auto px-4 py-16 text-center">
<h1 class="text-3xl font-heading mb-4">Track Your Order</h1><p class="text-xs text-gray-500 uppercase tracking-widest mb-10">Enter your phone number below</p>
<form action="{{route('shop.track.submit', $client->slug)}}" method="POST" class="max-w-md mx-auto flex border-b border-black">@csrf
<input type="tel" name="phone" value="{{$phone??''}}" required class="w-full py-3 bg-transparent outline-none text-center tracking-widest" placeholder="01XXXXXXXXX">
<button type="submit" class="px-6 text-xs uppercase font-bold tracking-widest hover:text-primary">Track</button></form>
@if(isset($phone)) <div class="mt-16 text-left space-y-6">
@forelse($orders??[] as $o)
<div class="border p-6 bg-white"><div class="flex justify-between items-center border-b pb-4 mb-4">
<span class="font-heading text-lg">Order #{{$o->id}}</span><span class="text-[10px] uppercase tracking-widest bg-gray-100 px-3 py-1">{{$o->order_status}}</span></div>
@foreach($o->items as $i)<div class="flex gap-4 mb-4"><img src="{{asset('storage/'.$i->product->thumbnail)}}" class="w-12 h-16 object-cover"><div class="flex-1 text-sm"><p class="font-heading">{{$i->product->name}}</p><p class="text-[10px] text-gray-500 uppercase">Qty: {{$i->quantity}} | ৳{{$i->price}}</p></div></div>@endforeach
<div class="text-right text-sm font-bold mt-4">Total: ৳{{$o->total_amount}}</div></div>
@empty <p class="text-gray-500">No orders found for this number.</p> @endforelse
</div>@endif </main>
@endsection