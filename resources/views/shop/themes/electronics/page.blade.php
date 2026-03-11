<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} - {{ $client->shop_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <header class="bg-white shadow-sm py-4">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-xl font-bold">{{ $client->shop_name }}</h1>
        </div>
    </header>

    <main class="container mx-auto px-4 py-12 max-w-4xl">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 border-b pb-4">{{ $page->title }}</h1>
            
            <div class="prose prose-lg max-w-none text-gray-700">
                {!! $page->content !!}
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ $client->custom_domain ? 'https://'.$client->custom_domain : route('shop.show', $client->slug) }}" 
               class="text-blue-600 hover:underline">
                &larr; Back to Shop
            </a>
        </div>
    </main>

</body>
</html>