import {defineConfig} from "vite";
import {resolve} from "path";
import react from "@vitejs/plugin-react";
// @ts-ignore
import packageJson from './package.json';
import {dynamicBase} from "vite-plugin-dynamic-base";
export default defineConfig((configEnv) => {
    const isDevelopment = configEnv.mode === "development";
    const generateSourceMaps = process.env.GENERATE_SOURCE_MAPS === 'true';
    const outDir = generateSourceMaps ? 'dist-debug' : 'dist';
    return {
        base: (process.env.NODE_ENV === "production") ? "/__dynamic_base__/" : "/",
        plugins: [
            react(),
            dynamicBase({
                publicPath: 'window.rapidload_optimizer.page_optimizer_package_base',
                transformIndexHtml: true // BUILD + PREVIEW in local breaks when true
            }),
            {
                name: 'generate-asset-map',
                generateBundle(options, bundle) {
                    console.log('Generating asset map...');
                    const assetMap: Record<string, string> = {};
                    for (const [fileName, asset] of Object.entries(bundle)) {
                        console.log(asset.name);

                        if ('fileName' in asset) {
                            // Remove hash from the key
                            const keyWithoutHash = asset.fileName.replace(/(.+)\.[\w-]+(\.[^.]+)$/, '$1$2');
                            assetMap[keyWithoutHash] = asset.fileName;
                        }
                    }
                    
                    const phpArray = Object.entries(assetMap)
                        .map(([key, value]) => `    '${key}' => '${value}'`)
                        .join(",\n");
                    
                    const phpContent = `<?php
                    if (!defined('ABSPATH')) {
                        exit;
                    }
                    define('RAPIDLOAD_ASSET_MAP', [
                    ${phpArray}
                    ]);
                    `;
                    
                    this.emitFile({
                        type: 'asset',
                        fileName: 'asset-map.php',
                        source: phpContent
                    });
                    
                    console.log('Asset map generated and saved as PHP file');
                }
            }
        ],
        define: {
            '__OPTIMIZER_VERSION__': JSON.stringify(packageJson.version),
        },
        resolve: {
            alias: {
                app: resolve(__dirname, "src", "app"),
                components: resolve(__dirname, "src", "components"),
                '@/components': resolve(__dirname, "src", "components"),
                hooks: resolve(__dirname, "src", "hooks"),
                lib: resolve(__dirname, "src", "lib"),
            },
        },
        build: {
            outDir: outDir,
            sourcemap: generateSourceMaps,
            rollupOptions: {
                output: {
                    manualChunks: {
                        ['core-vendor']: [
                            'react',
                            'react-dom',
                            'tailwind-merge',
                            'webfontloader'
                        ],
                        vendor: [
                            'prism-react-renderer',
                            '@tanstack/react-table',
                            '@radix-ui/react-toast',
                            '@radix-ui/react-select',
                            'focus-lock'
                        ],
                        animations: [
                            'framer-motion',
                        ],
                    },
                    // entryFileNames: `assets/[name].js`,
                    entryFileNames: `assets/[name].[hash].js`,
                    chunkFileNames: `assets/[name].[hash].js`,
                    assetFileNames: (assetInfo) => {
                        if (assetInfo.name && /\.(woff2?|eot|ttf|otf)$/.test(assetInfo.name)) {
                            return `assets/[name].[ext]`;
                        }
                        return `assets/[name].[hash].[ext]`;
                    }
                }
            }
        },
    };
});
