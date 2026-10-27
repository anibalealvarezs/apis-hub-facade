<!-- Semantic Footer / Micro Branding -->
<footer class="relative w-full z-10 py-10 px-6 sm:px-8 mt-auto flex flex-col items-center gap-8 text-sm font-medium text-slate-500 dark:text-slate-400 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
        {{-- Column 1: Core Platform / Technology --}}
        <div class="flex flex-col gap-2.5 items-center md:items-start">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                {{ app()->getLocale() === 'es' ? 'Plataforma' : 'Platform' }}
            </span>
            <a href="{{ app()->getLocale() === 'es' ? route('landing.architecture.es') : route('landing.architecture') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('landing.architecture*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Architecture') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('landing.solutions.normalization.es') : route('landing.solutions.normalization') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('landing.solutions.normalization*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Normalization') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('docs.api.es') : route('docs.api') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('docs.api*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('API Docs') }}
            </a>
        </div>

        {{-- Column 2: Solutions & Product --}}
        <div class="flex flex-col gap-2.5 items-center md:items-start">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                {{ app()->getLocale() === 'es' ? 'Soluciones' : 'Solutions' }}
            </span>
            <a href="{{ app()->getLocale() === 'es' ? route('landing.plans.es') : route('landing.plans') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('landing.plans*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Plans & Features') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('landing.solutions.agency.es') : route('landing.solutions.agency') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('landing.solutions.agency*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('For Agencies') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('demo.data-sources.es') : route('demo.data-sources', ['lang' => 'en']) }}" 
               class="hover:text-brand-blue {{ request()->routeIs('demo.*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Demo') }}
            </a>
        </div>

        {{-- Column 3: Legal & Privacy --}}
        <div class="flex flex-col gap-2.5 items-center md:items-start">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                {{ app()->getLocale() === 'es' ? 'Legal' : 'Legal' }}
            </span>
            <a href="{{ app()->getLocale() === 'es' ? route('legal.privacy.es') : route('legal.privacy') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('legal.privacy*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Privacy') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('legal.tos.es') : route('legal.tos') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('legal.tos*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Terms') }}
            </a>
            <a href="{{ app()->getLocale() === 'es' ? route('legal.data-deletion.es') : route('legal.data-deletion') }}" 
               class="hover:text-brand-blue {{ request()->routeIs('legal.data-deletion*') ? 'text-brand-blue font-semibold' : '' }} transition-colors">
                {{ __('Data Deletion') }}
            </a>
        </div>
    </div>

    <div class="flex items-center justify-center gap-2 flex-wrap text-center pt-4 border-t border-slate-200/40 dark:border-slate-800/40 w-full max-w-5xl text-xs sm:text-sm">
        <span>{{ __('Engineered by') }} <a href="https://anibalalvarez.com" target="_blank" rel="noopener noreferrer" class="hover:text-brand-blue transition-colors underline-offset-4 hover:underline">Aníbal Álvarez</a>. &copy; {{ date('Y') }} APIs Hub</span>
        <span class="px-2 py-0.5 text-xs font-bold text-brand-blue bg-brand-blue/10 border border-brand-blue/20 rounded uppercase tracking-wider">Beta</span>
    </div>
</footer>

