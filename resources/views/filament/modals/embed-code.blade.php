<div class="space-y-4" x-data="embedCodeConfig({
    embedUrl: {{ \Illuminate\Support\Js::from($embedUrl) }},
    publicUrl: {{ \Illuminate\Support\Js::from($publicUrl) }}
})">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('Max Height (px)') }}
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Leave empty for automatic height. Setting a value caps the iframe height so the embedded dashboard does not extend past it; longer content scrolls inside the iframe.') }}
        </p>
        <input type="number" min="0" step="50" x-model="maxHeight"
               class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2.5 px-4 focus:ring-primary-500 focus:border-primary-500"
               :placeholder="'{{ __('Automatic') }}'"/>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('Option 1: JS Injection Script (Recommended)') }}
        </label>
        <div class="relative">
            <textarea readonly rows="3" x-text="jsScript"
                      class="w-full font-mono text-xs p-2 bg-gray-100 dark:bg-gray-800 border rounded"></textarea>
            <button type="button" x-on:click="copy(jsScript)"
                    class="absolute top-2 right-2 px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                {{ __('Copy') }}
            </button>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('Option 2: Direct iFrame HTML') }}
        </label>
        <div class="relative">
            <textarea readonly rows="3" x-text="iframeSnippet"
                      class="w-full font-mono text-xs p-2 bg-gray-100 dark:bg-gray-800 border rounded"></textarea>
            <button type="button" x-on:click="copy(iframeSnippet)"
                    class="absolute top-2 right-2 px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                {{ __('Copy') }}
            </button>
        </div>
    </div>
</div>
