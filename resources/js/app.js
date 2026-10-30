import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AdminLayout from './Layouts/AdminLayout.vue';
import '../css/app.css';

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

createInertiaApp({
    title: (title) => (title ? `PILKASIS - ${title}` : 'PILKASIS'),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        }

        const standalone = name === 'Auth/Login' || name === 'PublicResults' || name.startsWith('Voter/');

        if (!standalone && !page.default.layout) {
            page.default.layout = AdminLayout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0056a7',
    },
});
