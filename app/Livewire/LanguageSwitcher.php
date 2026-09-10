<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageSwitcher extends Component
{
    public $currentLocale;
    public $availableLocales;

    public function mount()
    {
        $this->currentLocale = App::getLocale();
        $this->availableLocales = config('app.supported_locales');
    }

    public function switchLanguage($locale)
    {
        if (array_key_exists($locale, $this->availableLocales)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
            $this->currentLocale = $locale;

            // Flash a success message
            session()->flash('message', 'Language switched to ' . $this->availableLocales[$locale]);

            // SECURITY FIX: Validate redirect URL to prevent open redirect attacks
            $previousUrl = url()->previous();
            $appUrl = config('app.url');

            // Only redirect if the URL belongs to this domain.
            //
            // Compare hosts, not prefixes: str_starts_with() happily accepts
            // https://questerra-series.com.evil.com/ — and the value comes from the
            // Referer header, which the caller controls completely.
            if (parse_url($previousUrl, PHP_URL_HOST) === parse_url($appUrl, PHP_URL_HOST)) {
                return redirect()->to($previousUrl);
            }

            // Default fallback to homepage
            return redirect('/');
        } else {
            // Debug: show available locales
            session()->flash('error', 'Invalid locale. Available: ' . implode(', ', array_keys($this->availableLocales)));
        }
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}