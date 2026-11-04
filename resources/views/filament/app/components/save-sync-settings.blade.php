<x-filament::button type="submit"
    :disabled="!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended' || !auth()->user()->can('edit_preferences')">
    {{ __('Save & Push Changes') }}
</x-filament::button>
