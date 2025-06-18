<!-- Simple Language Switcher with Forms -->
<div class="flex items-center space-x-1 bg-gray-100 border border-gray-300 rounded-lg p-1" title="Language Switcher">
    @php
        $currentLocale = app()->getLocale();
        $supportedLocales = config('app.supported_locales', []);
    @endphp
    
    @foreach($supportedLocales as $locale => $name)
        @if($locale !== $currentLocale)
            <form method="GET" action="{{ request()->url() }}" class="inline">
                @foreach(request()->query() as $key => $value)
                    @if($key !== 'lang')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="lang" value="{{ $locale }}">
                
                <button type="submit" 
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium transition-colors duration-200 {{ $locale === 'en' ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-red-500 text-white hover:bg-red-600' }}">
                    @if($locale === 'en')
                        <span class="mr-1">🇺🇸</span> EN
                    @elseif($locale === 'id')
                        <span class="mr-1">🇮🇩</span> ID
                    @endif
                </button>
            </form>
        @else
            <div class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $locale === 'en' ? 'bg-blue-600 text-white' : 'bg-red-600 text-white' }}">
                @if($locale === 'en')
                    <span class="mr-1">🇺🇸</span> EN
                @elseif($locale === 'id')
                    <span class="mr-1">🇮🇩</span> ID
                @endif
                <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    @endforeach
</div>