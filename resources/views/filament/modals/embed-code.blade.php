<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('Option 1: JS Injection Script (Recommended)') }}
        </label>
        <div class="relative">
            <textarea readonly rows="3" class="w-full font-mono text-xs p-2 bg-gray-100 dark:bg-gray-800 border rounded">{{ $jsScript }}</textarea>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('Option 2: Direct iFrame HTML') }}
        </label>
        <div class="relative">
            <textarea readonly rows="3" class="w-full font-mono text-xs p-2 bg-gray-100 dark:bg-gray-800 border rounded">{{ $iframeSnippet }}</textarea>
        </div>
    </div>
</div>
