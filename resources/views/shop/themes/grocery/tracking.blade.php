@extends('shop.themes.grocery.layout')
@section('title', 'Track Order | ' . $client->shop_name)
@section('content')
<main class="max-w-2xl mx-auto px-4 py-12 text-center">
<div class="w-16 h-16 bg-primary/10 text-primary flex items-center justify-center rounded-full text-2xl mx-auto mb-4"><i class="fas fa-motorcycle"></i></div>
<h1 class="text-3xl font-extrabold text-gray-800 mb-2">Track Delivery</h1><p class="text-sm font-bold text-gray-500 mb-8">Enter mobile number to track grocery</p>
<form action="{{route('shop.track.submit', $client->slug)}}" method="POST" class="flex gap-2 max-w-sm mx-auto">@csrf
<input type="tel" name="phone" value="{{$phone??''}}" required class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-primary font-bold" placeholder="01XXXXXXXXX">
<button type="submit" class="bg-primary text-white px-6 rounded-xl font-extrabold hover:bg-green-600 shadow-md">Track</button></form>
@if(isset($phone)) <div class="mt-12 text-left space-y-4">
@forelse($orders??[] as $o)
<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"><div class="flex justify-between items-center mb-4">
<span class="font-extrabold text-lg text-gray-800">#{{$o->id}}</span><span class="text-xs font-bold uppercase bg-primary/10 text-primary px-3 py-1 rounded-lg">{{$o->order_status}}</span></div>
@foreach($o->items as $i)<div class="flex gap-3 mb-3 items-center"><img src="{{asset('storage/'.$i->product->thumbnail)}}" class="w-10 h-10 object-contain"><div class="flex-1"><p class="font-bold text-sm leading-tight">{{$i->product->name}}</p><p class="text-xs font-bold text-gray-400">Qty: {{$i->quantity}}</p></div><span class="font-bold text-sm">৳{{$i->price}}</span></div>@endforeach
<div class="text-right text-lg font-extrabold text-primary mt-4 pt-4 border-t border-gray-100">Total: ৳{{$o->total_amount}}</div></div>
@empty <div class="bg-white p-8 rounded-2xl text-center"><p class="font-bold text-gray-500">No recent orders found.</p></div> @endforelse
</div>@endif </main>
@endsection