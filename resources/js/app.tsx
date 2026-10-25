import '../css/app.css';
import './bootstrap';

import Toaster from '@/Components/Toaster';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        /**
         * Toaster dilampirkan di dalam children renderer Inertia agar
         * terhubung ke konteks Page (usePage), bukan di luar <App>.
         */
        root.render(
            <App
                {...props}
                children={({ Component, key, props: pageProps }) => (
                    <>
                        <Component {...pageProps} key={key} />
                        <Toaster />
                    </>
                )}
            />,
        );
    },
    progress: {
        color: '#4B5563',
    },
});