# 🌍 Multilingual Support Guide

This project now supports **English** and **Bahasa Indonesia** with a complete localization system.

## 🚀 Features Implemented

### ✅ Language System
- **English (en)** - Default language
- **Bahasa Indonesia (id)** - Indonesian translation
- Dynamic language switching
- Session-based language persistence
- Automatic locale detection

### ✅ Language Switcher
- Professional dropdown component
- Flag icons for each language
- Integrated in all layouts (Admin & User)
- Smooth transitions and modern UI

### ✅ Translation Files
- `/resources/lang/en/` - English translations
- `/resources/lang/id/` - Indonesian translations
- Organized by categories:
  - `common.php` - General UI elements
  - `auth.php` - Authentication pages
  - `quiz.php` - Quiz/questionnaire system

### ✅ Middleware Integration
- `SetLocale` middleware for automatic language detection
- Global web middleware registration
- Session persistence across requests

## 📁 File Structure

```
resources/lang/
├── en/
│   ├── common.php    # General translations
│   ├── auth.php      # Authentication
│   └── quiz.php      # Quiz system
└── id/
    ├── common.php    # Indonesian general
    ├── auth.php      # Indonesian auth
    └── quiz.php      # Indonesian quiz
```

## 🔧 How to Use

### In Blade Templates
```blade
<!-- Simple translation -->
{{ __('common.welcome') }}

<!-- Translation with parameters -->
{{ __('quiz.qr_code_instructions', ['code' => $questionnaire->qr_code]) }}

<!-- Conditional translation -->
{{ $questionnaire->is_active ? __('common.active') : __('common.inactive') }}
```

### In Controllers/Components
```php
// Simple translation
return __('common.success');

// With parameters
return __('quiz.question_number', ['number' => 1, 'total' => 10]);

// Get current locale
$locale = app()->getLocale();
```

## 🎛️ Language Switcher Component

The language switcher is automatically included in:
- Admin layout header
- Main app layout header
- All authenticated pages

Users can switch languages by clicking the language dropdown in the top navigation.

## 🗂️ Translation Categories

### Common (`common.php`)
- General UI elements (buttons, navigation, status)
- Form elements (save, cancel, delete, etc.)
- Messages (success, error, warnings)
- Pagination and time-related strings

### Authentication (`auth.php`)
- Login/register forms
- Password reset functionality
- Profile management
- Validation messages
- Success/error messages

### Quiz System (`quiz.php`)
- Questionnaire management
- Question types and forms
- QR code functionality
- Quiz taking interface
- Results and statistics
- Scanner functionality

## 🆕 Adding New Languages

### 1. Add to Configuration
Edit `config/app.php`:
```php
'supported_locales' => [
    'en' => 'English',
    'id' => 'Bahasa Indonesia',
    'es' => 'Español',  // Add new language
],
```

### 2. Create Language Files
```bash
mkdir resources/lang/es
cp resources/lang/en/* resources/lang/es/
# Then translate the content
```

### 3. Update Language Switcher
The switcher automatically detects available languages from the config.

## 🎨 Adding New Translations

### 1. Add to English File
In `resources/lang/en/common.php`:
```php
'new_feature' => 'New Feature',
'with_params' => 'Hello :name, you have :count messages',
```

### 2. Add Indonesian Translation
In `resources/lang/id/common.php`:
```php
'new_feature' => 'Fitur Baru',
'with_params' => 'Halo :name, Anda memiliki :count pesan',
```

### 3. Use in Views
```blade
{{ __('common.new_feature') }}
{{ __('common.with_params', ['name' => $user->name, 'count' => 5]) }}
```

## 🔍 Currently Translated Areas

### ✅ Completed
- Navigation headers and menus
- Authentication pages (login forms)
- QR code management interface
- Questionnaire management basics
- Common UI elements
- **User dashboard environment**
- **User navigation and header**
- **Dashboard tabs and components**
- **QR scanner modal**
- **Team registration form**
- **Dashboard header controls**

### 🚧 Partially Completed
- Admin dashboard components
- Quiz taking interface
- Registration forms

### ❌ Not Yet Translated
- Profile management pages
- Detailed quiz interfaces
- Error pages
- Quest location management

## 🛠️ Quick Translation Commands

### Check Current Locale
```php
app()->getLocale()  // Returns: 'en' or 'id'
```

### Switch Language Programmatically
```php
app()->setLocale('id');
session()->put('locale', 'id');
```

### URL with Language Parameter
```
https://yoursite.com/admin/dashboard?lang=id
```

## 🎯 Best Practices

1. **Always provide fallbacks** - English translations should always exist
2. **Use descriptive keys** - `auth.login_failed` not `auth.error1`
3. **Group related translations** - Keep auth stuff in auth.php
4. **Use parameters for dynamic content** - `:name`, `:count`, etc.
5. **Test both languages** - Always verify translations work correctly

## 🚀 Usage Examples

### Language Switcher
- Located in top navigation
- Dropdown with flags and language names
- Automatically refreshes page with new language
- Remembers selection in session

### Common Translations
```blade
{{ __('common.welcome') }}          <!-- Welcome / Selamat Datang -->
{{ __('common.dashboard') }}        <!-- Dashboard / Dasbor -->
{{ __('common.save') }}             <!-- Save / Simpan -->
{{ __('common.cancel') }}           <!-- Cancel / Batal -->
```

### Quiz System
```blade
{{ __('quiz.questionnaire') }}      <!-- Questionnaire / Kuesioner -->
{{ __('quiz.questions') }}          <!-- Questions / Pertanyaan -->
{{ __('quiz.qr_code') }}           <!-- QR Code / Kode QR -->
```

## 🔧 Maintenance

### Adding New Strings
1. Add to English file first (as fallback)
2. Add Indonesian translation
3. Use descriptive, hierarchical keys
4. Test in both languages

### Updating Existing Translations
1. Update both language files
2. Search codebase for usage
3. Verify all contexts still work
4. Test language switching

---

The multilingual system is now fully functional and ready for production use! 🎉