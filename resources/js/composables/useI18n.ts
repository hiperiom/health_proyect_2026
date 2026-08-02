import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Frontend internationalisation (i18n) composable.
 *
 * The backend (`App\Http\Middleware\HandleInertiaRequests`) injects
 * two things on every Inertia response:
 *
 *   1. `props.locale`      — the active locale code (e.g. "es", "en").
 *   2. `props.translations` — a `{ [locale]: { ... } }` map of every
 *      `lang/<locale>/ui.php` file, so the client can switch
 *      languages without an extra HTTP round-trip.
 *
 * On the client we keep a single `activeLocale` ref that mirrors the
 * server's locale and lets the user change it dynamically. Switching
 * languages fires an Inertia visit to a tiny route
 * (`PATCH /locale`) that calls `App\Http\Controllers\LocaleController`
 * which updates the session and re-renders the current page.
 *
 * The `t()` function accepts a dotted key (`'ui.auth.login.email'`)
 * and an optional replacements object (e.g. `{ name: 'Superusuario' }`).
 * Missing keys are logged once in development and return the key
 * itself in production so the UI never breaks.
 */

export type Locale = 'en' | 'es';

export const LOCALES: ReadonlyArray<{ code: Locale; label: string; flag: string }> = [
    { code: 'es', label: 'Español', flag: '🇪🇸' },
    { code: 'en', label: 'English', flag: '🇬🇧' },
];

/* ------------------------------------------------------------------ */
/* Internal helpers                                                    */
/* ------------------------------------------------------------------ */

type Translations = Record<string, unknown>;

const FALLBACK_LOCALE: Locale = 'en';

function getByPath(obj: unknown, path: string): unknown {
    if (obj === null || obj === undefined) {
        return undefined;
    }

    const segments = path.split('.');
    let cursor: unknown = obj;

    for (const segment of segments) {
        if (cursor === null || cursor === undefined || typeof cursor !== 'object') {
            return undefined;
        }
        cursor = (cursor as Record<string, unknown>)[segment];
    }

    return cursor;
}

function interpolate(template: string, replacements?: Record<string, string | number>): string {
    if (! replacements) {
        return template;
    }

    return template.replace(/:(\w+)/g, (match, key: string) => {
        const value = replacements[key];
        return value === undefined || value === null ? match : String(value);
    });
}

function isLocale(value: unknown): value is Locale {
    return value === 'en' || value === 'es';
}

/* ------------------------------------------------------------------ */
/* Singleton reactive state (shared across every component)           */
/* ------------------------------------------------------------------ */

const activeLocale = ref<Locale>('es');
const warnedMissingKeys = new Set<string>();

/**
 * Read the initial locale from the page props and keep it in sync
 * with every Inertia navigation. Must be called from the app root
 * (resources/js/app.ts) before any component is mounted.
 */
export function bootstrapI18nFromInertia(): void {
    const page = usePage<{ locale?: string; translations?: Record<string, Translations> }>();

    const detected = page.props.locale;
    if (isLocale(detected)) {
        activeLocale.value = detected;
    }

    // Keep the ref in sync with every server-side locale change.
    watch(
        () => page.props.locale,
        (next) => {
            if (isLocale(next)) {
                activeLocale.value = next;
            }
        },
    );
}

/* ------------------------------------------------------------------ */
/* Public composable                                                   */
/* ------------------------------------------------------------------ */

export function useI18n() {
    const page = usePage<{
        locale?: string;
        translations?: Record<string, Translations>;
    }>();

    const translations = computed<Record<string, Translations>>(
        () => page.props.translations ?? {},
    );

    const current = computed<Translations>(
        () => translations.value[activeLocale.value] ?? {},
    );

    const fallback = computed<Translations>(
        () => translations.value[FALLBACK_LOCALE] ?? {},
    );

    /**
     * Resolve a dotted key. Falls back to the English translation if
     * the active locale does not have the key, and finally to the key
     * itself so the UI never breaks on a missing entry.
     */
    function t(key: string, replacements?: Record<string, string | number>): string {
        let value: unknown = getByPath(current.value, key);

        if (value === undefined && activeLocale.value !== FALLBACK_LOCALE) {
            value = getByPath(fallback.value, key);
        }

        if (typeof value !== 'string') {
            if (import.meta.env.DEV && ! warnedMissingKeys.has(key)) {
                warnedMissingKeys.add(key);
                // eslint-disable-next-line no-console
                console.warn(`[i18n] missing translation for "${key}" in "${activeLocale.value}"`);
            }
            return key;
        }

        return interpolate(value, replacements);
    }

    /**
     * Change the active locale. Triggers an Inertia visit to
     * `PATCH /locale` which updates the session and reloads the
     * current page with the new locale + translations.
     */
    function setLocale(locale: Locale): void {
        if (locale === activeLocale.value) {
            return;
        }

        // Update the client immediately for snappy UI, then sync
        // with the server on the next page render.
        activeLocale.value = locale;
        document.documentElement.lang = locale;

        router.patch(
            '/locale',
            { locale },
            { preserveScroll: true, preserveState: false },
        );
    }

    return {
        locale: activeLocale,
        locales: LOCALES,
        translations,
        t,
        setLocale,
    };
}
