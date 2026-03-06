<x-filament-panels::page>
    <form wire:submit="sendBroadcast">
        {{ $this->form }}

        <div class="mt-6 text-right">
            <x-filament::button type="submit" color="success" size="lg" icon="heroicon-o-paper-airplane">
                Send Broadcast Now
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>