<?php

namespace App\Http\Controllers;

use App\Support\Preferences;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Switch language or light / dark mode. Works before login too (kept in the session). */
class PreferenceController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['nullable', Rule::in(array_keys(Preferences::LOCALES))],
            'theme'  => ['nullable', Rule::in(Preferences::THEMES)],
        ]);
        $data = array_filter($data);

        foreach ($data as $key => $value) {
            session([$key => $value]);
        }
        if ($request->user() && $data) {
            $request->user()->forceFill($data)->save();
        }

        return $request->expectsJson() ? response()->json(['ok' => true] + $data) : back();
    }
}
