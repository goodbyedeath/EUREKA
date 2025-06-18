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
            
            // Refresh the page to apply new language
            return redirect()->to(request()->header('Referer') ?: '/');
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