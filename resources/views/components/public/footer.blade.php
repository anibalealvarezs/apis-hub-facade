<!-- Semantic Footer / Micro Branding -->
<footer class="relative w-full z-10 py-8 px-6 sm:px-8 mt-auto flex flex-col items-center gap-4 bd-text-2xs uppercase tracking-[0.3em] font-bold text-slate-400 dark:text-slate-500 select-none border-t border-slate-200/50 dark:border-slate-800/50">
    <nav aria-label="{{ __('Navigation Links') }}" class="flex items-center justify-center opacity-70 flex-wrap gap-y-2">
        <a href="{{ app()->getLocale() === 'es' ? route('landing.plans.es') : route('landing.plans') }}" class="px-4 py-2 mx-1 sm:mx-4 hover:text-brand-blue {{ request()->routeIs('landing.plans*') ? 'text-brand-blue' : '' }} transition-colors">{{ __('Plans & Features') }}</a>
        <span class="w-1 h-1 bg-brand-blue/30 dark:bg-brand-blue/20 rounded-full" aria-hidden="true"></span>
        <a href="{{ app()->getLocale() === 'es' ? route('docs.api.es') : route('docs.api') }}" class="px-4 py-2 mx-1 sm:mx-4 hover:text-brand-blue {{ request()->routeIs('docs.api*') ? 'text-brand-blue' : '' }} transition-colors">{{ __('API Docs') }}</a>
        <span class="w-1 h-1 bg-brand-blue/30 dark:bg-brand-blue/20 rounded-full" aria-hidden="true"></span>
        <a href="{{ app()->getLocale() === 'es' ? route('legal.privacy.es') : route('legal.privacy') }}" class="px-4 py-2 mx-1 sm:mx-4 hover:text-brand-blue {{ request()->routeIs('legal.privacy*') ? 'text-brand-blue' : '' }} transition-colors">{{ __('Privacy') }}</a>
        <span class="w-1 h-1 bg-brand-blue/30 dark:bg-brand-blue/20 rounded-full" aria-hidden="true"></span>
        <a href="{{ app()->getLocale() === 'es' ? route('legal.tos.es') : route('legal.tos') }}" class="px-4 py-2 mx-1 sm:mx-4 hover:text-brand-blue {{ request()->routeIs('legal.tos*') ? 'text-brand-blue' : '' }} transition-colors">{{ __('Terms') }}</a>
        <span class="w-1 h-1 bg-brand-teal/30 dark:bg-brand-teal/20 rounded-full" aria-hidden="true"></span>
        <a href="{{ app()->getLocale() === 'es' ? route('legal.data-deletion.es') : route('legal.data-deletion') }}" class="px-4 py-2 mx-1 sm:mx-4 hover:text-brand-blue {{ request()->routeIs('legal.data-deletion*') ? 'text-brand-blue' : '' }} transition-colors">{{ __('Data Deletion') }}</a>
    </nav>
    <div class="opacity-80 flex items-center justify-center gap-2 flex-wrap text-center">
        <span>{{ __('Engineered by') }} <a href="https://anibalalvarez.com" target="_blank" rel="noopener noreferrer" class="hover:text-brand-blue transition-colors underline-offset-4 hover:underline">Aníbal Álvarez</a>. &copy; {{ date('Y') }} APIs Hub</span>
        <span class="px-1.5 py-0.5 bd-text-4xs font-black text-brand-blue bg-brand-blue/10 border border-brand-blue/20 rounded uppercase tracking-widest">Beta</span>
    </div>
</footer>

<!-- Portal Link Resolver Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.js-portal-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const encodedPath = link.getAttribute('data-portal');
                if (encodedPath) {
                    try {
                        const decodedPath = atob(encodedPath);
                        window.location.href = decodedPath;
                    } catch (err) {
                        console.error('Failed to resolve portal link', err);
                    }
                }
            });
        });
    });
</script>
