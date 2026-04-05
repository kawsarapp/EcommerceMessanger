@extends('shop.themes.bdpro.layout')
@section('title', $page->title . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<div class="bg-gray-50/50 py-10 min-h-screen">
    <div class="max-w-[1400px] mx-auto px-4">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center text-[11px] text-gray-500 mb-8 bg-white py-2.5 px-4 rounded-sm border border-gray-100 shadow-sm w-fit">
            <a href="{{$baseUrl}}" class="hover:text-primary transition"><i class="fas fa-home"></i></a>
            <i class="fas fa-chevron-right text-[8px] mx-3 text-gray-300"></i>
            <span class="text-primary font-medium">{{$page->title}}</span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-10">
            
            {{-- Main Content Space --}}
            <div class="md:col-span-8 lg:col-span-9 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-10">
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 mb-8 pb-4 border-b border-gray-100">
                        {{$page->title}}
                    </h1>
                    
                    {{-- Typography Container: Tailored for Filament Rich Editor outputs --}}
                    <div class="prose prose-sm sm:prose lg:prose-lg prose-blue max-w-none text-gray-600 font-medium leading-relaxed">
                        @if($page->content)
                            {!! clean($page->content) !!}
                        @else
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-file-alt text-4xl mb-3 opacity-20"></i>
                                <p>This page is currently undergoing updates.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contextual Sidebar Navigation --}}
            <div class="md:col-span-4 lg:col-span-3 space-y-6">
                
                {{-- Quick Links --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-5 py-4 border-b border-gray-100 font-bold text-slate-700 uppercase tracking-wider text-xs flex items-center gap-2">
                        <i class="fas fa-list text-primary/70"></i> Information
                    </div>
                    <ul class="flex flex-col text-sm font-medium">
                        @foreach($pages as $p)
                            @php
                                $pageUrl = $clean ? 'https://'.$clean.'/'.$p->slug : route('shop.page.slug', ['slug' => $client->slug, 'pageSlug' => $p->slug]);
                            @endphp
                            <li>
                                <a href="{{$pageUrl}}" class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0 {{ $page->id === $p->id ? 'text-primary bg-blue-50/50 font-bold' : 'text-slate-600 hover:text-primary hover:bg-gray-50' }} transition">
                                    <span>{{$p->title}}</span>
                                    <i class="fas fa-angle-right text-[10px] opacity-40"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Contact Support Card --}}
                <div class="bg-gradient-to-br from-bddeep to-primary rounded-xl p-6 text-white text-center shadow-lg relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 text-white/10 text-6xl"><i class="fas fa-headset"></i></div>
                    <div class="relative z-10">
                        <h3 class="font-bold text-lg mb-2">Need Assistance?</h3>
                        <p class="text-xs text-blue-100 mb-4 opacity-90 leading-relaxed">Our support team is available via direct messaging to help you with your inquiries.</p>
                        <a href="{{ $client->fb_page_url ?? '#' }}" target="_blank" class="inline-block bg-white text-primary font-bold px-6 py-2.5 rounded-full text-xs hover:bg-gray-100 transition shadow hover:shadow-md w-full">
                            <i class="fab fa-facebook-messenger mr-1"></i> MSG US
                        </a>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
</div>

{{-- Inline CSS for Prose Elements explicitly (Fallback for missing typography plugin) --}}
<style>
    .prose h1, .prose h2, .prose h3 { color: #1e293b; font-weight: 800; margin-top: 2rem; margin-bottom: 1rem; }
    .prose p { margin-bottom: 1.25rem; }
    .prose a { color: #1a85ff; text-decoration: underline; transition: color 0.2s; }
    .prose a:hover { color: #005bb5; }
    .prose ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1.25rem; }
    .prose ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1.25rem; }
    .prose li { margin-bottom: 0.5rem; }
    .prose blockquote { border-left: 4px solid #cbd5e1; padding-left: 1rem; font-style: italic; color: #64748b; margin-bottom: 1.25rem;}
    .prose strong { color: #334155; font-weight: 700; }
    .prose img { max-width: 100%; border-radius: 0.5rem; margin: 1.5rem 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>
@endsection
