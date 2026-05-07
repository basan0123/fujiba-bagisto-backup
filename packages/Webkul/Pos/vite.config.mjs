import { defineConfig, loadEnv } from "vite";
import vue from '@vitejs/plugin-vue';
import laravel from "laravel-vite-plugin";
import { VitePWA } from 'vite-plugin-pwa';
import path from "path";

export default defineConfig(({ mode }) => {
    const envDir = "../../../";

    Object.assign(process.env, loadEnv(mode, envDir));

    return {
        envDir,

        server: {
            host: process.env.VITE_HOST || "localhost",
            port: process.env.VITE_PORT || 5174,
        },

        build: {
            emptyOutDir: true,

            rollupOptions: {
                output: {
                    manualChunks: undefined,
                },
            },

            chunkSizeWarningLimit: 2000,
        },

        plugins: [
            vue(),

            laravel({
                hotFile: "../../../public/pos-vite.hot",
                publicDirectory: mode === "publish"
                    ? "publishable"
                    : "../../../public/themes/pos",
                buildDirectory: "build",
                input: [
                    "src/Resources/assets/js/admin.js",
                    "src/Resources/assets/js/shop.js",
                    "src/Resources/assets/css/admin.css",
                    "src/Resources/assets/css/shop.css",
                ],
                refresh: true,
            }),

            VitePWA({
                registerType: 'autoUpdate',
                injectRegister: 'inline',
                includeAssets: [
                    '**/*.js',
                    '**/*.css'
                ],
                devOptions: {
                    enabled: false,
                    type: 'module'
                },
                workbox: {
                    globPatterns: [
                        '**/*.{js,css,html,ico,png,svg,json,woff2,woff,ttf}'
                    ],
                    // Bundle just crossed 2 MiB after adding the products-list
                    // status/category column. Bump the precache cap so workbox
                    // includes the main shop chunk; we still chunk-warn at 2000kB.
                    maximumFileSizeToCacheInBytes: 5 * 1024 * 1024,
                    cleanupOutdatedCaches: true,
                    clientsClaim: true,
                    skipWaiting: true,
                    navigateFallback: '/pos/index.html',

                    runtimeCaching: [
                        {
                            urlPattern: ({ request }) =>
                                request.mode === 'navigate',
                            handler: 'NetworkFirst',
                            options: {
                                cacheName: 'html-cache',
                                networkTimeoutSeconds: 3
                            },
                        },
                        {
                            urlPattern: ({ request }) =>
                                request.destination === 'script' ||
                                request.destination === 'style',
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'static-resources',
                            },
                        },
                        {
                            urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/,
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'images-cache',
                                expiration: {
                                    maxEntries: 60,
                                    maxAgeSeconds: 60 * 60 * 24 * 30
                                }
                            }
                        },
                    ],
                },

                manifest: {
                    name: 'POS Application',
                    short_name: 'POS',
                    description: 'Point of Sale Application',
                    theme_color: '#000000',
                    background_color: '#ffffff',
                    start_url: '/pos',
                    scope: '/pos/',
                    display: 'standalone',
                    orientation: 'portrait',
                    icons: [
                        {
                            src: '/themes/pos/build/images/icon-192.png',
                            sizes: '192x192',
                            type: 'image/png',
                            purpose: 'any maskable'
                        },
                        {
                            src: '/themes/pos/build/images/icon-512.png',
                            sizes: '512x512',
                            type: 'image/png',
                            purpose: 'any maskable'
                        }
                    ]
                }
            })
        ],

        resolve: {
            alias: {
                '@src': path.resolve(__dirname, 'src/Resources/assets/js'),
                '@images': path.resolve(__dirname, 'src/Resources/assets/images'),
                '@components': path.resolve(__dirname, 'src/Resources/assets/js/components'),
                '@skeletons': path.resolve(__dirname, 'src/Resources/assets/js/components/skeletons'),
                '@graphql': path.resolve(__dirname, 'src/Resources/assets/js/graphql'),
            },
        },

        experimental: {
            renderBuiltUrl(filename, { hostType }) {
                return { relative: true };
            },
        },
    };
});
