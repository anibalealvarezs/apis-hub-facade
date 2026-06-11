<div>
    @if($log)
        <div class="mb-4">
            <span class="font-semibold">{{ __('Status:') }}</span> 
            <x-filament::badge :color="$log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning')">
                {{ strtoupper($log->status) }}
            </x-filament::badge>
            <span class="text-sm text-gray-500 ml-2">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        
        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto max-h-96 font-mono text-xs whitespace-pre-wrap">
            {{ $log->output ?: __('No output recorded.') }}
        </div>
    @else
        <div class="text-gray-500 italic">
            {{ __('No deployment logs found for this project.') }}
        </div>
    @endif
</div>
