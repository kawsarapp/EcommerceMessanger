<!DOCTYPE html>
@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title')</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>tailwind.config={theme:{extend:{colors:{primary:'{{$client->primary_color??'#10b981'}}'},fontFamily:{sans:['Nunito','sans-serif']}}}}</script>
<style>[x-cloak]{display:none!important} .hide-scroll::-webkit-scrollbar{display:none}</style></head>
<body class="bg-[#F3F4F6] text-gray-800 antialiased flex flex-col min-h-screen">
@if($client->announcement_text)<div class="bg-primary text-white text-center py-2 text-sm font-bold">{{$client->announcement_text}}</div>@endif
<header class="bg-white sticky top-0 z-40 border-b border-gray-200 shadow-sm"><div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
<a href="{{$baseUrl}}" class="text-2xl font-extrabold text-primary flex items-center gap-2"><i class="fas fa-shopping-basket"></i> {{$client->shop_name}}</a>
<div class="flex gap-4 items-center"><a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-sm font-bold text-gray-600 hover:text-primary"><i class="fas fa-truck"></i> Track Order</a>
<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-2xl text-blue-500 hover:text-blue-600"><i class="fab fa-facebook-messenger"></i></a></div></div></header>
<main class="flex-1 w-full pb-10">@yield('content')</main>
<footer class="bg-white border-t border-gray-200 py-8 text-center mt-auto"><h3 class="font-extrabold text-xl mb-2 text-gray-700">{{$client->shop_name}}</h3>
<p class="text-sm text-gray-400 font-bold">&copy; {{date('Y')}} Fresh & Fast. All Rights Reserved.</p></footer>
</body></html>