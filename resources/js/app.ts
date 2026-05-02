import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { MotionPlugin } from '@vueuse/motion';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { HSStaticMethods } from 'preline';
import type { DefineComponent } from 'vue';
import { createApp, h, nextTick } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(MotionPlugin)
            .mount(el);

        nextTick(() => {
            HSStaticMethods.autoInit();
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Initialize Preline.js
router.on('navigate', () => {
    nextTick(() => {
        HSStaticMethods.autoInit();
    });
});
