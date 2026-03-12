<!DOCTYPE html>
@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title')</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>tailwind.config={theme:{extend:{colors:{primary:'{{$client->primary_color??'#F43F5E'}}'},fontFamily:{sans:['Quicksand','sans-serif'],heading:['Fredoka','sans-serif']}}}}</script>
<style>[x-cloak]{display:none!important} .hide-scroll::-webkit-scrollbar{display:none} .fun-shadow{box-shadow: 0 8px 0 rgba(0,0,0,0.05);}</style></head>
<body class="bg-[#FFFDF7] text-gray-800 antialiased flex flex-col min-h-screen">
@if($client->announcement_text)<div class="bg-primary text-white text-center py-2 text-sm font-bold tracking-wide">{{$client->announcement_text}} 🎈</div>@endif
<header class="bg-white sticky top-0 z-40 border-b-4 border-primary/10"><div class="max-w-7xl mx-auto px-4 h-20 flex justify-between items-center">
<a href="{{$baseUrl}}" class="text-3xl font-heading text-primary font-bold flex items-center gap-2 transform hover:scale-105 transition"><i class="fas fa-shapes text-yellow-400"></i> {{$client->shop_name}}</a>
<div class="flex gap-4 items-center"><a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-sm font-bold text-gray-600 hover:text-primary bg-gray-100 px-4 py-2 rounded-full"><i class="fas fa-map-marker-alt text-red-400"></i> Track</a>
<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center text-xl hover:bg-blue-500 hover:text-white transition"><i class="fab fa-facebook-messenger"></i></a></div></div></header>
<main class="flex-1 w-full pb-10">@yield('content')</main>
<footer class="bg-white border-t-4 border-gray-100 py-10 text-center mt-auto"><h3 class="font-heading text-2xl mb-2 text-gray-700">{{$client->shop_name}} 🧸</h3>
<p class="text-sm font-bold text-gray-400">&copy; {{date('Y')}} Fun & Play. All Rights Reserved.</p></footer>
</body></html>