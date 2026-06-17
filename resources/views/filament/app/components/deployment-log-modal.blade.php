<div>
    @if($log)
        <div class="mb-4">
            <span class="font-semibold">{{ __('Status:') }}</span> 
            <x-filament::badge :color="$log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning')">
                {{ strtoupper($log->status) }}
            </x-filament::badge>
            <span class="text-sm text-gray-500 ml-2">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        
        <div class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-4 rounded-lg text-sm border border-gray-200 dark:border-gray-700">
            <div class="font-semibold mb-2">{{ __('Deployment Result:') }}</div>
            <p>{{ $log->getSummaryMessage() }}</p>
        </div>
    @else
        <div class="text-gray-500 italic">
            {{ __('No deployment logs found for this project.') }}
        </div>
    @endif
</div>
