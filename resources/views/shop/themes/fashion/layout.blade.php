<!DOCTYPE html>
@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title')</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>tailwind.config={theme:{extend:{colors:{primary:'{{$client->primary_color??'#000'}}'},fontFamily:{sans:['Montserrat','sans-serif'],heading:['Playfair Display','serif']}}}}</script>
<style>[x-cloak]{display:none!important} .hide-scroll::-webkit-scrollbar{display:none}</style></head>
<body class="bg-[#FCFCFC] text-gray-900 antialiased flex flex-col min-h-screen">
@if($client->announcement_text)<div class="bg-primary text-white text-center py-1.5 text-[10px] uppercase tracking-[0.2em] font-medium">{{$client->announcement_text}}</div>@endif
<header class="bg-white sticky top-0 z-40 border-b border-gray-100 shadow-sm"><div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
<a href="{{$baseUrl}}" class="font-heading text-2xl font-bold tracking-widest uppercase">{{$client->shop_name}}</a>
<div class="flex gap-4 items-center"><a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-xs uppercase tracking-widest hover:text-primary"><i class="fas fa-box-open"></i> Track</a>
<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-xl hover:text-primary"><i class="fab fa-facebook-messenger"></i></a></div></div></header>
<main class="flex-1 w-full pb-10">@yield('content')</main>
<footer class="bg-white border-t border-gray-100 py-12 text-center mt-auto"><h3 class="font-heading text-2xl mb-4">{{$client->shop_name}}</h3>
<p class="text-[10px] text-gray-400 tracking-[0.2em] uppercase">&copy; {{date('Y')}} All Rights Reserved.</p></footer>
</body></html>