@props(['message' => null])
<div data-analytics-warnings
     x-data="{
         show: false,
         ts: {},
         width: 320,
         messages: @js($message ? [$message] : []),
         toggle($el) {
             if (!this.show) {
                 this.computePosition($el);
             }
             this.show = !this.show;
         },
         computePosition($el) {
             const r = $el.getBoundingClientRect();
             const gap = 8;
             const tw = Math.min(this.width, window.innerWidth - 16);
             const v = (r.bottom + 180 > window.innerHeight && r.top > 180) ? 'bottom' : 'top';
             const lt = Math.max(8, Math.min(r.left + r.width / 2 - tw / 2, window.innerWidth - tw - 8));

             if (v === 'top') {
                 this.ts = {
                     position: 'fixed',
                     top: (r.bottom + gap) + 'px',
                     left: lt + 'px',
                     width: tw + 'px',
                     zIndex: 9999
                 };
             } else {
                 this.ts = {
                     position: 'fixed',
                     bottom: (window.innerHeight - r.top + gap) + 'px',
                     left: lt + 'px',
                     width: tw + 'px',
                     zIndex: 9999
                 };
             }
         }
     }"
     x-id="['analytics-warning']"
     x-show="messages.length > 0" x-cloak
     @analytics-warnings="messages = $event.detail; show = false"
     @click.outside="show = false" @keydown.escape.window="show = false"
     @scroll.window="if (show) show = false" @resize.window="if (show) show = false"
     class="inline-flex shrink-0 items-center">
    <button type="button" x-ref="warningTrigger" @click.stop="toggle($el)"
            :aria-expanded="show.toString()" :aria-controls="$id('analytics-warning')" aria-label="{{ __('GA4 user count information') }}"
            class="rounded-lg p-1 text-amber-600 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-500 dark:text-amber-300 dark:hover:bg-amber-950/40">
        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
    </button>
    <div x-show="show" x-cloak x-transition.opacity :style="ts"
         role="note" :id="$id('analytics-warning')" @click.stop @keydown.escape="show = false"
         class="rounded-lg border border-amber-300 bg-white p-3 text-left text-sm text-gray-900 shadow-2xl dark:border-amber-700 dark:bg-gray-900 dark:text-gray-100 whitespace-normal">
        <template x-for="message in messages" :key="message"><p class="leading-relaxed" x-text="message"></p></template>
    </div>
</div>
