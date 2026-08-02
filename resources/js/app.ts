import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { bootstrapI18nFromInertia } from '@/composables/useI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import RoleSelectLayout from '@/layouts/auth/RoleSelectLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'HealthAgent';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'Dashboard':
                return DashboardLayout;
            case name === 'auth/RoleSelect':
                return RoleSelectLayout;
            case name === 'auth/Login':
            case name === 'auth/Register':
            case name === 'auth/ForgotPassword':
            case name === 'auth/ResetPassword':
                return AuthSplitLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    resolve: (name) =>
        resolvePageComponent<DefineComponent>(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup: ({ el, App, props, plugin }) => {
        const app = createSSRApp({
            render: () => h(App, props),
        });

        app.use(plugin);
        app.mount(el);

        // Wire the i18n composable to the Inertia page props. Must
        // run after `app.mount(el)` so `usePage()` resolves against
        // the mounted Vue app.
        bootstrapI18nFromInertia();
    },
}).then(() => {
    initializeTheme();
    initializeFlashToast();
});
