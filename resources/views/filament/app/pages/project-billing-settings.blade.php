<x-filament-panels::page>
    <div class="mb-4">
        <p class="text-gray-600 dark:text-gray-400">
            Manage the billing profiles assigned to pay for this project's subscriptions and usage.
            If no primary profile is assigned, the project may be suspended upon the next billing cycle.
        </p>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
