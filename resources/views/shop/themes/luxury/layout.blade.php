<!DOCTYPE html>
@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title')</title>
<script src="https://cdn.tailwindcss.com"></script><script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script>tailwind.config={theme:{extend:{colors:{primary:'{{$client->primary_color??'#D4AF37'}}'},fontFamily:{sans:['Lato','sans-serif'],heading:['Cinzel','serif']}}}}</script>
<style>[x-cloak]{display:none!important} .hide-scroll::-webkit-scrollbar{display:none}</style></head>
<body class="bg-black text-gray-200 antialiased flex flex-col min-h-screen">
<header class="bg-[#0a0a0a] sticky top-0 z-40 border-b border-[#222]"><div class="max-w-7xl mx-auto px-4 h-20 flex justify-between items-center">
<a href="{{$baseUrl}}" class="font-heading text-2xl md:text-3xl font-bold text-primary tracking-[0.2em] uppercase">{{$client->shop_name}}</a>
<div class="flex gap-5 items-center"><a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-xs tracking-widest uppercase hover:text-primary transition"><i class="fas fa-gem mr-1"></i> Track</a>
<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-xl hover:text-primary transition"><i class="fab fa-facebook-messenger"></i></a></div></div></header>
<main class="flex-1 w-full pb-10">@yield('content')</main>
<footer class="bg-[#0a0a0a] border-t border-[#222] py-16 text-center mt-auto"><h3 class="font-heading text-2xl text-primary mb-4 tracking-[0.2em] uppercase">{{$client->shop_name}}</h3>
<p class="text-[10px] text-gray-500 tracking-[0.3em] uppercase">&copy; {{date('Y')}} Exclusive Collection.</p></footer>
</body></html>