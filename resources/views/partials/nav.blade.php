<nav class="fixed top-0 w-full z-50 bg-inverse-surface shadow-md h-nav flex items-center">
    <div class="flex justify-between items-center w-full px-lg max-w-container-max mx-auto">
        <!-- Brand Logo -->
        <div class="flex items-center gap-sm">
            <span class="text-headline-md font-headline-md font-bold text-on-primary">Notas IA</span>
        </div>
        <!-- Navigation Links (Desktop) -->
        <div class="hidden md:flex items-center gap-lg">
            <!-- Analyze Link -->
            <a class="{{ request()->routeIs('home') ? 'text-on-primary border-b-2 border-primary-fixed-dim pb-1 opacity-80' : 'text-surface-variant hover:text-on-primary hover:bg-on-secondary-fixed-variant' }} font-label-md text-label-md transition-colors duration-200 px-sm py-xs rounded" href="{{ route('home') }}">
                Analyze
            </a>
            <!-- History Link -->
            <a class="{{ request()->routeIs('history') ? 'text-on-primary border-b-2 border-primary-fixed-dim pb-1 opacity-80' : 'text-surface-variant hover:text-on-primary hover:bg-on-secondary-fixed-variant' }} font-label-md text-label-md transition-colors duration-200 px-sm py-xs rounded" href="{{ route('history') }}">
                History
            </a>
            <!-- Debug Link -->
            <a class="{{ request()->routeIs('debug') ? 'text-on-primary border-b-2 border-primary-fixed-dim pb-1 opacity-80' : 'text-surface-variant hover:text-on-primary hover:bg-on-secondary-fixed-variant' }} font-label-md text-label-md transition-colors duration-200 px-sm py-xs rounded" href="{{ route('debug') }}">
                Debug
            </a>
        </div>
        <!-- Trailing Icon Action -->
        <div class="flex items-center">
            <button class="text-primary hover:bg-on-secondary-fixed-variant transition-colors duration-200 p-sm rounded-full flex items-center justify-center" aria-label="Account">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">account_circle</span>
            </button>
        </div>
    </div>
</nav>