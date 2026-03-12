<!DOCTYPE html>
@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title')</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>tailwind.config={theme:{extend:{colors:{primary:'{{$client->primary_color??'#000'}}'},fontFamily:{sans:['Outfit','sans-serif']}}}}</script>
<style>[x-cloak]{display:none!important} .hide-scroll::-webkit-scrollbar{display:none}</style></head>
<body class="bg-white text-black antialiased flex flex-col min-h-screen">
@if($client->announcement_text)<div class="bg-black text-white text-center py-2 text-xs font-semibold tracking-widest uppercase">{{$client->announcement_text}}</div>@endif
<header class="bg-white sticky top-0 z-40 border-b border-gray-200"><div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
<a href="{{$baseUrl}}" class="text-2xl font-extrabold tracking-tighter">{{$client->shop_name}}</a>
<div class="flex gap-6 items-center"><a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-xs font-bold uppercase tracking-widest hover:text-gray-500">Track</a>
<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 border border-gray-200 rounded-full flex items-center justify-center hover:bg-black hover:text-white transition"><i class="fab fa-facebook-messenger"></i></a></div></div></header>
<main class="flex-1 w-full pb-16">@yield('content')</main>
<footer class="bg-white border-t border-gray-200 py-16 text-center mt-auto"><h3 class="font-extrabold text-2xl mb-4">{{$client->shop_name}}</h3>
<p class="text-xs text-gray-500 font-medium tracking-widest uppercase">&copy; {{date('Y')}} All Rights Reserved.</p></footer>
</body></html>