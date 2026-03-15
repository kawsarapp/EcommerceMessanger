@if(!empty($client->footer_text))
    <p class="text-slate-500 font-medium text-sm leading-relaxed mt-4">{{ $client->footer_text }}</p>
@endif

@if(!empty($client->footer_links) && is_array($client->footer_links))
    <div class="mt-6">
        <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider">Useful Links</h4>
        <div class="flex flex-col space-y-3 font-medium text-sm text-slate-500">
            @foreach($client->footer_links as $link)
                <a href="{{ $link['url'] }}" target="_blank" class="hover:text-primary premium-transition">{{ $link['title'] }}</a>
            @endforeach
        </div>
    </div>
@endif
