import './bootstrap'
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import ReactDOM from 'react-dom/client'
import Layout from './Layouts/Layout'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.{jsx,tsx}')

        // Find the exact file path (Generator/Index.jsx or Generator/Index.tsx)
        const match = Object.entries(pages).find(([path]) =>
            path.endsWith(`${name}.jsx`) || path.endsWith(`${name}.tsx`)
        )

        if (!match) {
            throw new Error(`Page component not found: ${name}`);
        }

        const module = await match[1](); // Resolving the module properly

        if (module.default.layout === undefined) {
            module.default.layout = (page) => <Layout>{page}</Layout>
        }

        return module;
    },

    setup({ el, App, props }) {
        ReactDOM.createRoot(el).render(<App {...props} />)
    },

    progress: {
        color: '#4B5563',
    },
})
