@extends('shop.themes.shwapno.layout')
@section('title', $page->title . ' - ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<div class="bg-[#f4f7f6] py-10 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Breadcrumb Navigation (Shwapno Style) --}}
        <nav class="flex items-center text-sm text-gray-500 mb-6 bg-white py-3 px-5 rounded-lg border-b border-gray-200 uppercase font-semibold">
            <a href="{{$baseUrl}}" class="hover:text-green-600 transition tracking-wider flex items-center gap-2"><i class="fas fa-home"></i> Home</a>
            <span class="mx-3 text-gray-300">/</span>
            <span class="text-green-700 tracking-wider">{{$page->title}}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- Content Area --}}
            <div class="lg:w-3/4">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden transform hover:-translate-y-1 transition duration-300">
                    <div class="border-b border-gray-100 px-8 py-6 bg-gradient-to-r from-green-50 to-white">
                        <h1 class="text-3xl font-bold text-gray-800 tracking-tight">{{$page->title}}</h1>
                    </div>
                    
                    <div class="p-8">
                        <div class="prose prose-green max-w-none text-gray-600 text-[15px] leading-relaxed">
                            @if($page->content)
                                {!! clean($page->content) !!}
                            @else
                                <div class="text-center py-10 opacity-50">
                                    <i class="far fa-file-alt text-5xl mb-4 text-gray-300"></i>
                                    <p class="font-medium">No details available yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar (Shwapno Colors) --}}
            <div class="lg:w-1/4 space-y-6">
                
                {{-- Quick Links Box --}}
                <div class="bg-white rounded-xl shadow-sm sticky top-24 border border-green-50 overflow-hidden">
                    <h3 class="bg-green-600 text-white font-bold py-3.5 px-5 flex justify-between items-center text-sm uppercase tracking-wider">
                        Quick Links <i class="fas fa-link text-white/50"></i>
                    </h3>
                    <ul class="divide-y divide-gray-100 font-medium text-[13px]">
                        @foreach($pages as $p)
                            @php
                                $pageUrl = $clean ? 'https://'.$clean.'/'.$p->slug : route('shop.page.slug', ['slug' => $client->slug, 'pageSlug' => $p->slug]);
                            @endphp
                            <li>
                                <a href="{{$pageUrl}}" class="block px-5 py-4 hover:bg-green-50 hover:text-green-700 transition {{ $page->id === $p->id ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'text-gray-600' }}">
                                    {{$p->title}}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
</div>

{{-- Inline CSS for Prose Elements explicitly (Fallback for missing typography plugin) --}}
<style>
    .prose h1, .prose h2, .prose h3 { color: #166534; font-weight: 800; margin-top: 2rem; margin-bottom: 1rem; }
    .prose p { margin-bottom: 1.25rem; }
    .prose a { color: #15803d; text-decoration: underline; transition: color 0.2s; }
    .prose a:hover { color: #166534; }
    .prose ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1.25rem; }
    .prose ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1.25rem; }
    .prose li { margin-bottom: 0.5rem; }
    .prose blockquote { border-left: 4px solid #bce3c6; padding-left: 1rem; font-style: italic; color: #4b5563; margin-bottom: 1.25rem; background: #f0fdf4; padding: 1rem; border-radius: 4px; }
    .prose strong { color: #1f2937; font-weight: 700; }
    .prose img { max-width: 100%; border-radius: 0.5rem; margin: 1.5rem 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
</style>
@endsection
