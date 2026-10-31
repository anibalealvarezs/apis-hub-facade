@props(['message' => null])
<div data-analytics-warnings
     x-data="{ ...floatingTooltip({ width: 320 }), messages: @js($message ? [$message] : []) }"
     x-id="['analytics-warning']"
     x-show="messages.length > 0" x-cloak
     @analytics-warnings="messages = $event.detail; show = false"
     @click.outside="show = false" @keydown.escape.window="show = false"
     @scroll.window.capture="show = false" @resize.window="show = false"
     class="inline-flex shrink-0 items-center">
    <button type="button" x-ref="warningTrigger" @click.stop="toggle($el)"
            :aria-expanded="show.toString()" :aria-controls="$id('analytics-warning')" aria-label="{{ __('GA4 user count information') }}"
            class="rounded-lg p-1.5 text-amber-600 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500 dark:text-amber-300 dark:hover:bg-amber-950/40">
        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
    </button>
    <template x-teleport="body">
        <div x-show="show" x-cloak x-transition.opacity :style="ts"
             role="note" :id="$id('analytics-warning')" @click.stop @keydown.escape="show = false"
             class="w-80 max-w-[calc(100vw-1rem)] rounded-lg border border-amber-300 bg-white p-3 text-sm text-gray-900 shadow-xl dark:border-amber-700 dark:bg-gray-900 dark:text-gray-100">
            <template x-for="message in messages" :key="message"><p x-text="message"></p></template>
        </div>
    </template>
</div>
