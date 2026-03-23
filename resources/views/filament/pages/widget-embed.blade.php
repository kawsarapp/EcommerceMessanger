<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Settings Form --}}
        <x-filament::section>
            <x-slot name="heading">⚙️ Widget Configuration</x-slot>
            <form wire:submit="save">
                {{ $this->form }}
                <div class="mt-4 flex justify-end">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                        Save Settings
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Embed Snippets --}}
        <x-filament::section>
            <x-slot name="heading">📋 Embed Code</x-slot>
            <x-slot name="description">এই code আপনার website এ paste করুন। Save করার পরে code auto-update হবে।</x-slot>

            <div class="space-y-6">

                {{-- Option 1: Head --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                                📌 Option A — Paste inside <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&lt;head&gt;</code>
                            </span>
                            <p class="text-xs text-gray-500 mt-0.5">Widget loads faster. Recommended.</p>
                        </div>
                        <button
                            onclick="copyCode('head-snippet')"
                            class="flex items-center gap-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition"
                        >
                            📋 Copy
                        </button>
                    </div>
                    <pre id="head-snippet" class="bg-gray-900 text-green-400 text-xs p-4 rounded-xl overflow-x-auto leading-relaxed whitespace-pre-wrap break-all">{{ $this->getSnippetHead() }}</pre>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Option 2: Body --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                                📌 Option B — Paste before <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&lt;/body&gt;</code>
                            </span>
                            <p class="text-xs text-gray-500 mt-0.5">Standard placement. Works on all sites.</p>
                        </div>
                        <button
                            onclick="copyCode('body-snippet')"
                            class="flex items-center gap-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition"
                        >
                            📋 Copy
                        </button>
                    </div>
                    <pre id="body-snippet" class="bg-gray-900 text-green-400 text-xs p-4 rounded-xl overflow-x-auto leading-relaxed whitespace-pre-wrap break-all">{{ $this->getSnippetBody() }}</pre>
                </div>

            </div>
        </x-filament::section>

        {{-- How to use guide --}}
        <x-filament::section>
            <x-slot name="heading">📖 How to Install</x-slot>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700 dark:text-gray-300">

                <div class="space-y-3">
                    <h4 class="font-bold">🌐 Any HTML Website</h4>
                    <ol class="list-decimal list-inside space-y-1 text-xs leading-relaxed">
                        <li>উপরের যেকোনো code copy করুন</li>
                        <li>আপনার website এর HTML এ paste করুন</li>
                        <li>Page refresh করুন → নিচে chat bubble দেখবেন ✅</li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold">🔷 WordPress</h4>
                    <ol class="list-decimal list-inside space-y-1 text-xs leading-relaxed">
                        <li>WordPress Admin → Appearance → Theme Editor</li>
                        <li><code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">footer.php</code> খুলুন</li>
                        <li><code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&lt;/body&gt;</code> এর আগে Option B paste করুন</li>
                        <li>Save → Site visit করুন ✅</li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold">🔒 Security Tips</h4>
                    <ul class="list-disc list-inside space-y-1 text-xs leading-relaxed text-amber-700 dark:text-amber-400">
                        <li>Production এ <strong>Allowed Domains</strong> সেট করুন</li>
                        <li>শুধু আপনার domain দিন, তাহলে অন্যরা key চুরি করলেও ব্যবহার করতে পারবে না</li>
                        <li>API Key কারো সাথে share করবেন না</li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold">🟡 Shopify</h4>
                    <ol class="list-decimal list-inside space-y-1 text-xs leading-relaxed">
                        <li>Online Store → Themes → Edit Code</li>
                        <li><code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">theme.liquid</code> খুলুন</li>
                        <li><code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">&lt;/body&gt;</code> এর আগে Option B paste করুন</li>
                        <li>Save → Preview ✅</li>
                    </ol>
                </div>

            </div>
        </x-filament::section>

    </div>

    <script>
    function copyCode(id) {
        var el = document.getElementById(id);
        if (!el) return;
        navigator.clipboard.writeText(el.textContent).then(function() {
            // Brief visual feedback
            var btn = event.target.closest('button');
            var orig = btn.innerHTML;
            btn.innerHTML = '✅ Copied!';
            btn.classList.replace('bg-indigo-600', 'bg-green-600');
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.classList.replace('bg-green-600', 'bg-indigo-600');
            }, 2000);
        });
    }
    </script>
</x-filament-panels::page>
