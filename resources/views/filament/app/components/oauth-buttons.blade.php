<div class="flex flex-col gap-4">
    @if($isGlobal)
        <div class="relative flex items-center justify-center my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-white/10"></div></div>
            <span class="relative px-3 text-xs font-bold uppercase tracking-widest text-gray-400 bg-white dark:bg-gray-900">Or continue with</span>
        </div>
    @endif

    <div class="flex items-center justify-between p-4 border rounded-xl bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10">
        <div class="flex items-center gap-x-3 text-sm">
             <span class="font-bold uppercase tracking-widest text-gray-400">
                 {{ !$isGlobal ? 'Status:' : 'Social Auth:' }}
             </span>
             <x-filament::badge :color="$color">
                 {{ $isConnected ? 'VALID / ACTIVE' : 'VIA '.strtoupper($provider) }}
             </x-filament::badge>
        </div>

        <x-filament::button 
            tag="a" 
            href="{{ $url }}"
            icon="{{ $icon }}"
            :color="$color"
            size="sm"
        >
            {{ $label }}
        </x-filament::button>
    </div>
</div>
