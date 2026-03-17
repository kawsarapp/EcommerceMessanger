<?php
$user = auth()->user();
$client = $user->activeClient;
$roleName = match($user->role) {
    'super_admin' => 'Super Administrator',
    'seller' => 'Shop Owner',
    'staff' => 'Staff Member',
    default => 'User'
};
?>
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-4">
            <!-- Avatar -->
            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-xl">
                {{ substr($user->name, 0, 1) }}
            </div>
            
            <div class="flex-1">
                <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white pb-1">
                    Welcome back, {{ $user->name }}!
                </h2>
                
                <div class="flex flex-wrap gap-2 items-center text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::badge color="success">
                        {{ $roleName }}
                    </x-filament::badge>

                    @if($client)
                        <span class="text-gray-300 dark:text-gray-700">&bull;</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            Shop: {{ $client->name ?? 'N/A' }} 
                            @if($user->role === 'staff')
                                <span class="text-xs text-gray-400">(Owner: {{ $client->user->name ?? 'N/A' }})</span>
                            @endif
                        </span>
                        
                        <span class="text-gray-300 dark:text-gray-700">&bull;</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            Status: 
                            @if($client->status === 'active')
                                <span class="text-success-600 dark:text-success-400">Active</span>
                            @elseif($client->status === 'trial')
                                <span class="text-warning-600 dark:text-warning-400">Trial</span>
                            @else
                                <span class="text-danger-600 dark:text-danger-400">Inactive</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            <!-- Profile Action -->
            <form action="{{ filament()->getProfileUrl() }}" method="get">
                <x-filament::button size="sm" color="gray" type="submit" outlined>
                    Edit Profile
                </x-filament::button>
            </form>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
