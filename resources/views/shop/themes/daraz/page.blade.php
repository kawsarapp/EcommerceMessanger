@extends('shop.themes.daraz.layout')
@section('title', $page->title . ' - ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<!-- Main Container (Daraz Layout) -->
<div class="bg-[#eff0f5] py-6 min-h-[60vh]">
    <div class="w-full max-w-[1188px] mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <nav class="flex text-[12px] text-gray-500 mb-4 items-center">
            <a href="{{$baseUrl}}" class="hover:text-[#f36f21] transition">Home</a>
            <i class="fas fa-chevron-right text-[8px] mx-2 text-gray-400"></i>
            <span class="text-gray-700 font-semibold">{{$page->title}}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-4">
            
            <!-- Sidebar Navigation -->
            <div class="hidden lg:block w-[240px] shrink-0">
                <div class="bg-white p-4 shadow-sm">
                    <h3 class="text-[14px] font-bold text-gray-800 mb-3 border-b pb-2">Information Links</h3>
                    <ul class="space-y-1 text-[13px]">
                        @foreach($pages as $p)
                            @php
                                $pageUrl = $clean ? 'https://'.$clean.'/'.$p->slug : route('shop.page.slug', ['slug' => $client->slug, 'pageSlug' => $p->slug]);
                            @endphp
                            <li>
                                <a href="{{$pageUrl}}" class="block px-2 py-1.5 transition-colors {{ $page->id === $p->id ? 'text-[#f36f21] font-semibold bg-orange-50' : 'text-gray-600 hover:text-[#f36f21]' }}">
                                    {{$p->title}}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Page Details -->
            <div class="flex-1 bg-white shadow-sm p-6 lg:p-10">
                <h1 class="text-2xl font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-100">{{$page->title}}</h1>
                
                <div class="prose prose-orange max-w-none text-[#333] text-[14px] font-normal leading-[1.6]">
                    @if($page->content)
                        {!! clean($page->content) !!}
                    @else
                        <div class="flex items-center justify-center p-12 text-gray-400 flex-col">
                            <i class="far fa-window-restore text-4xl mb-2 opacity-30"></i>
                            <p class="text-sm">Content not available yet.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Inline CSS for Daraz Prose Typography --}}
<style>
    .prose h1, .prose h2, .prose h3 { color: #212121; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.25rem;}
    .prose p { margin-bottom: 1rem; color: #424242; }
    .prose a { color: #1a9cb7; text-decoration: none; transition: 0.2s; }
    .prose a:hover { text-decoration: underline; color: #f36f21; }
    .prose ul, .prose ol { margin-left: 1.5rem; margin-bottom: 1rem; }
    .prose ul { list-style-type: disc; }
    .prose li { margin-bottom: 0.5rem; }
    .prose blockquote { border-left: 3px solid #f36f21; padding-left: 1rem; color: #757575; background-color: #fdfaf7; padding: 10px;}
    .prose strong { color: #212121; font-weight: 600; }
    .prose img { max-width: 100%; height: auto; margin: 1rem 0; border: 1px solid #e0e0e0; }
</style>
@endsection
