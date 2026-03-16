import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { InertiaProgress } from '@inertiajs/progress';

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return await page();
    },

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin);
        vueApp.component('Link', Link);
        vueApp.component('Head', Head);

        vueApp.mount(el);
    },
});

InertiaProgress.init({
    color: '#2563eb',
    showSpinner: false,
});