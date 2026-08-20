<div x-data="thresholdCalibrator({
    upper: @entangle('data.upper_limit'),
    lower: @entangle('data.lower_limit'),
    unit: @entangle('data.unit'),
    calcLines: @entangle('data.calculationLines'),
    sourceType: @entangle('data.source_type'),
    sourceConfig: @entangle('data.source_config')
})" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">

    <!-- Header & Historical Baseline Badges -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
        <div>
            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                {{ __('Threshold Calibration & Dry-Run Preview') }}
            </h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Simulated against historical baseline data across selected calculation line(s).') }}
            </p>
        </div>

        <!-- Baseline Stat Chips -->
        <template x-if="currentVal !== null">
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-gray-600 dark:text-gray-300 shadow-sm">
                    <span>{{ __('Current:') }}</span>
                    <strong class="font-bold text-gray-900 dark:text-white" x-text="formatVal(currentVal)"></strong>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-gray-600 dark:text-gray-300 shadow-sm">
                    <span>{{ __('30d Avg:') }}</span>
                    <strong class="font-bold text-gray-900 dark:text-white" x-text="formatVal(avgVal)"></strong>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-gray-600 dark:text-gray-300 shadow-sm">
                    <span>{{ __('Range:') }}</span>
                    <strong class="font-bold text-gray-900 dark:text-white" x-text="formatVal(minVal) + ' - ' + formatVal(maxVal)"></strong>
                </span>
            </div>
        </template>
        <template x-if="currentVal === null">
            <div class="text-xs text-gray-400 dark:text-gray-500 italic">
                {{ __('Evaluation against live metric data will execute on schedule or via manual test.') }}
            </div>
        </template>
    </div>

    <!-- Quick Preset Buttons -->
    <div class="flex flex-wrap items-center gap-2 pt-1">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Quick Calibrate:') }}</span>
        <button type="button" @click="applyPreset('plus_minus_10')" class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
            ±10% {{ __('Range') }}
        </button>
        <button type="button" @click="applyPreset('plus_minus_20')" class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
            ±20% {{ __('Range') }}
        </button>
        <button type="button" @click="applyPreset('std_dev_2')" class="rounded-lg border border-primary-300 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-900/40 dark:text-primary-300 transition">
            ⚖️ {{ __('Balanced (±2σ Deviation)') }}
        </button>
        <template x-if="sourceConfig?.target_attribute === 'r_squared'">
            <button type="button" @click="applyPreset('r2_fit')" class="rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 transition">
                🎯 {{ __('Model Fit (R² < 0.60)') }}
            </button>
        </template>
    </div>

    <!-- Live Dry-Run Trigger Simulation Card -->
    <div class="rounded-lg p-3 text-xs transition-colors"
         :class="{
             'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800': triggerSimulation.isBalanced || triggerSimulation.isConservative,
             'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800': !triggerSimulation.isBalanced && !triggerSimulation.isTooTight && !triggerSimulation.isConservative,
             'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800': triggerSimulation.isTooTight
         }">
        <div class="flex items-center justify-between font-semibold">
            <span class="flex items-center gap-1.5">
                <template x-if="triggerSimulation.isBalanced || triggerSimulation.isConservative">
                    <span>🟢 {{ __('Optimal Threshold Sensitivity') }}</span>
                </template>
                <template x-if="!triggerSimulation.isBalanced && !triggerSimulation.isTooTight && !triggerSimulation.isConservative">
                    <span>🟡 {{ __('Moderate Alert Frequency') }}</span>
                </template>
                <template x-if="triggerSimulation.isTooTight">
                    <span>⚠️ {{ __('Thresholds May Be Too Sensitive') }}</span>
                </template>
            </span>
            <span>
                <strong x-text="triggerSimulation.triggers"></strong> {{ __('simulated triggers in past 30 days') }} (<span x-text="triggerSimulation.ratePercent + '%'"></span>)
            </span>
        </div>
        <p class="mt-1 opacity-90">
            <template x-if="triggerSimulation.isTooTight">
                <span>{{ __('This limit triggers frequently on standard fluctuations. Consider widening the limits to reduce noise.') }}</span>
            </template>
            <template x-if="!triggerSimulation.isTooTight && triggerSimulation.triggers > 0">
                <span>{{ __('This alert captures notable deviations without generating excessive trigger noise.') }}</span>
            </template>
            <template x-if="triggerSimulation.triggers === 0">
                <span>{{ __('No breaches detected in the past 30 days. This alert will only notify on critical outliers.') }}</span>
            </template>
        </p>
    </div>
</div>

<script src="{{ asset('js/threshold-calibrator.js') }}?v={{ file_exists(public_path('js/threshold-calibrator.js')) ? filemtime(public_path('js/threshold-calibrator.js')) : '1' }}"></script>
