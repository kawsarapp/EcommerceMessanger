<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Customer Info Card --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
            <div class="flex flex-wrap gap-6 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">👤 Name:</span>
                    <span class="font-semibold ml-1">{{ $this->record->customer_info['name'] ?? 'Anonymous' }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">📞 Phone:</span>
                    <span class="font-semibold ml-1">{{ $this->record->customer_info['phone'] ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">📍 Address:</span>
                    <span class="font-semibold ml-1">{{ $this->record->customer_info['address'] ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">🌐 Session:</span>
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded ml-1">
                        {{ str_replace('widget_', '', $this->record->sender_id) }}
                    </code>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">🕐 Last active:</span>
                    <span class="font-semibold ml-1">{{ $this->record->last_interacted_at?->diffForHumans() ?? '—' }}</span>
                </div>
                @if($this->record->is_human_agent_active)
                    <span class="px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-bold">
                        👤 Human Agent Active
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-bold">
                        🤖 AI Handling
                    </span>
                @endif
            </div>
        </div>

        {{-- Chat History --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col" style="height:500px;">
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-history">
                @forelse($this->getHistory() as $msg)
                    @if(!empty($msg['user']))
                        {{-- Customer message --}}
                        <div class="flex justify-end">
                            <div class="max-w-xs lg:max-w-md">
                                <div class="bg-indigo-600 text-white px-4 py-2 rounded-2xl rounded-br-sm text-sm leading-relaxed">
                                    {{ $msg['user'] }}
                                </div>
                                <div class="text-right text-xs text-gray-400 mt-1">
                                    👤 Customer · {{ isset($msg['time']) ? date('h:i A', $msg['time']) : '' }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if(!empty($msg['ai']))
                        {{-- AI or Seller reply --}}
                        <div class="flex justify-start">
                            <div class="max-w-xs lg:max-w-md">
                                <div class="{{ ($msg['role'] ?? 'ai') === 'seller' ? 'bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700' }} text-gray-800 dark:text-gray-200 px-4 py-2 rounded-2xl rounded-bl-sm text-sm leading-relaxed shadow-sm">
                                    {{ $msg['ai'] }}
                                </div>
                                <div class="text-left text-xs text-gray-400 mt-1">
                                    {{ ($msg['role'] ?? 'ai') === 'seller' ? '👤 Seller' : '🤖 AI' }}
                                    · {{ isset($msg['time']) ? date('h:i A', $msg['time']) : '' }}
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-gray-400 py-16">
                        <div class="text-4xl mb-3">💬</div>
                        <div>No messages yet.</div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Reply Box (only when human agent is active) --}}
        @if($this->record->is_human_agent_active)
            <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4 shadow-sm">
                <div class="text-xs text-amber-700 dark:text-amber-400 mb-2 font-semibold">
                    👤 You are in Human Agent mode — customer will receive your reply
                </div>
                <div class="flex gap-3">
                    <textarea
                        wire:model="replyText"
                        rows="2"
                        placeholder="Type your reply..."
                        class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm bg-white dark:bg-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"
                    ></textarea>
                    <button
                        wire:click="sendReply"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-semibold transition"
                    >
                        Send ➤
                    </button>
                </div>
            </div>
        @else
            <div class="text-center text-sm text-gray-400 dark:text-gray-500 py-2">
                🤖 AI is handling this conversation. Click <strong>"Take Over"</strong> above to reply manually.
            </div>
        @endif

    </div>

    {{-- Auto-scroll to bottom --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('chat-history');
            if (el) el.scrollTop = el.scrollHeight;
        });
        document.addEventListener('livewire:navigated', function() {
            var el = document.getElementById('chat-history');
            if (el) el.scrollTop = el.scrollHeight;
        });
    </script>
</x-filament-panels::page>
