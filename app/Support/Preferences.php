<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/** The user's language and light / dark mode: saved on the user, or in the session before login. */
class Preferences
{
    public const LOCALES = ['en' => 'English', 'ms' => 'Bahasa Melayu', 'zh_CN' => '中文'];
    public const THEMES = ['light', 'dark'];

    public static function locale(): string
    {
        $locale = Auth::user()?->locale ?: session('locale');
        return array_key_exists((string) $locale, self::LOCALES) ? $locale : config('app.locale', 'en');
    }

    public static function theme(): string
    {
        $theme = Auth::user()?->theme ?: session('theme');
        return in_array($theme, self::THEMES, true) ? $theme : 'light';
    }

    public static function dark(): bool
    {
        return self::theme() === 'dark';
    }

    /** <html lang="..."> value. */
    public static function htmlLang(): string
    {
        return str_replace('_', '-', self::locale());
    }
}
