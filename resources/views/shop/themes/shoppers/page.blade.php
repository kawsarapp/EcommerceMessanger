@extends('shop.themes.shoppers.layout')
@section('title', $page->title . ' • ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<!-- Page Title Section -->
<div class="bg-gray-100 py-12 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-extrabold text-[#2a2a2a] text-center uppercase tracking-wider mb-3">{{$page->title}}</h1>
        <div class="flex justify-center items-center text-sm font-medium text-gray-500 space-x-2">
            <a href="{{$baseUrl}}" class="hover:text-red-500 transition-colors">Home</a>
            <i class="fas fa-circle text-[5px] text-gray-400"></i>
            <span class="text-red-600">{{$page->title}}</span>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="py-16 bg-white min-h-[50vh]">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex flex-col md:flex-row gap-12">
            
            <!-- Sidebar Navigation (Shoppers Style) -->
            <div class="w-full md:w-1/4">
                <div class="bg-gray-50 border border-gray-100 rounded p-6 sticky top-20">
                    <h3 class="text-lg font-bold text-[#2a2a2a] mb-6 uppercase border-b-2 border-red-500 inline-block pb-1">Shop Policies</h3>
                    <ul class="space-y-3">
                        @foreach($pages as $p)
                            @php
                                $pageUrl = $clean ? 'https://'.$clean.'/'.$p->slug : route('shop.page.slug', ['slug' => $client->slug, 'pageSlug' => $p->slug]);
                            @endphp
                            <li>
                                <a href="{{$pageUrl}}" class="block text-sm font-semibold transition-all duration-200 {{ $page->id === $p->id ? 'text-red-500 pl-2 border-l-2 border-red-500' : 'text-gray-600 hover:text-red-500' }}">
                                    {{$p->title}}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Page Details -->
            <div class="w-full md:w-3/4">
                <div class="prose prose-red max-w-none text-gray-700 font-normal leading-loose">
                    @if($page->content)
                        {!! clean($page->content) !!}
                    @else
                        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <i class="fas fa-tools text-6xl mb-4 text-gray-200"></i>
                            <h4 class="text-xl font-bold text-gray-500">Page Under Construction</h4>
                            <p class="mt-2 text-sm">Please check back later for updates.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Inline CSS for Shoppers Prose Typography --}}
<style>
    .prose h1, .prose h2, .prose h3 { color: #1a1a1a; font-weight: 800; text-transform: uppercase; margin-top: 2.5rem; margin-bottom: 1.5rem; letter-spacing: -0.02em; }
    .prose h1 { border-bottom: 3px solid #f8f9fa; padding-bottom: 0.5rem; }
    .prose p { margin-bottom: 1.5rem; font-size: 15px; }
    .prose a { color: #ef4444; font-weight: 600; text-decoration: none; border-bottom: 1px dotted #ef4444; transition: all 0.2s; }
    .prose a:hover { color: #dc2626; border-bottom-style: solid; }
    .prose ul, .prose ol { margin-left: 2rem; margin-bottom: 1.5rem; }
    .prose ul { list-style-type: square; color: #ef4444; }
    .prose ul span, .prose ol span { color: #374151; }
    .prose li { margin-bottom: 0.75rem; font-size: 15px; }
    .prose blockquote { border-left: 5px solid #ef4444; background-color: #fef2f2; padding: 1.5rem; margin-bottom: 1.5rem; font-style: italic; color: #7f1d1d;}
    .prose strong { color: #000; font-weight: 700; }
    .prose img { width: 100%; height: auto; border: 1px solid #eee; padding: 4px; background: #fff; margin: 2rem 0; }
</style>
@endsection
