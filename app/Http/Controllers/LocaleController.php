<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Tiny controller used by the frontend i18n composable to switch
 * the active application locale at runtime.
 *
 * The frontend calls `router.patch('/locale', { locale })` whenever
 * the user picks a different language in the `LanguageSwitcher`.
 * This controller validates the value, persists it in the session,
 * and (for Inertia visits) returns the JSON shape Inertia expects so
 * the page re-renders with the new translations.
 */
class LocaleController extends Controller
{
    /**
     * Allowed locales. Keep in sync with the `LOCALES` array in
     * `resources/js/composables/useI18n.ts`.
     *
     * @var list<string>
     */
    public const ALLOWED = ['en', 'es'];

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', self::ALLOWED)],
        ]);

        $locale = $validated['locale'];

        // Persist for the next request via the session so a full
        // page reload keeps the user's choice.
        $request->session()->put('locale', $locale);

        // Apply immediately for the current request.
        App::setLocale($locale);

        return response()->json([
            'locale' => $locale,
        ]);
    }
}
