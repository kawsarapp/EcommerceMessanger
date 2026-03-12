@extends('shop.themes.kids.layout')
@section('title', 'Track Magic Box | ' . $client->shop_name)
@section('content')
<main class="max-w-2xl mx-auto px-4 py-16 text-center">
<div class="w-24 h-24 bg-yellow-100 text-yellow-500 flex items-center justify-center rounded-full text-5xl mx-auto mb-6 fun-shadow animate-bounce"><i class="fas fa-box-open"></i></div>
<h1 class="text-4xl font-heading font-bold text-gray-800 mb-3">Where is my Toy?</h1><p class="text-base font-bold text-gray-500 mb-10">Enter parents phone number to find it!</p>
<form action="{{route('shop.track.submit', $client->slug)}}" method="POST" class="flex gap-3 max-w-sm mx-auto bg-white p-2 rounded-full border-2 border-gray-100 shadow-sm">@csrf
<input type="tel" name="phone" value="{{$phone??''}}" required class="flex-1 bg-transparent px-5 py-3 outline-none font-bold text-gray-800" placeholder="01XXXXXXXXX">
<button type="submit" class="bg-primary text-white px-8 rounded-full font-heading text-lg hover:bg-primaryDark transition fun-shadow">Find</button></form>
@if(isset($phone)) <div class="mt-16 text-left space-y-6">
@forelse($orders??[] as $o)
<div class="bg-white p-6 md:p-8 rounded-3xl border-2 border-gray-100 fun-shadow"><div class="flex justify-between items-center mb-6 border-b-2 border-dashed border-gray-100 pb-4">
<span class="font-heading text-xl text-gray-800">Order #{{$o->id}}</span><span class="text-sm font-bold uppercase bg-primary/10 text-primary px-4 py-1.5 rounded-full">{{$o->order_status}}</span></div>
@foreach($o->items as $i)<div class="flex gap-4 mb-4 items-center bg-gray-50 p-3 rounded-2xl"><img src="{{asset('storage/'.$i->product->thumbnail)}}" class="w-14 h-14 object-contain bg-white rounded-xl p-1"><div class="flex-1"><p class="font-bold text-sm text-gray-800">{{$i->product->name}}</p><p class="text-xs font-bold text-gray-500 mt-1">Qty: {{$i->quantity}}</p></div><span class="font-bold text-lg text-primary">৳{{$i->price}}</span></div>@endforeach
<div class="text-right text-2xl font-heading text-gray-900 mt-6">Total: <span class="text-primary">৳{{$o->total_amount}}</span></div></div>
@empty <div class="bg-white p-10 rounded-3xl text-center border-2 border-gray-100"><p class="font-bold text-xl text-gray-500">Oops! No toys found for this number.</p></div> @endforelse
</div>@endif </main>
@endsection