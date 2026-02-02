import path from 'path';
import { fileURLToPath, URL } from 'url';

import vue from '@vitejs/plugin-vue';
import { defineConfig, type UserConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig(({ mode }): UserConfig => {
  const isProduction = mode === 'production';

  return {
    base: '/wp-content/plugins/hostinger-easy-onboarding/assets/',

    plugins: [
      vue({
        template: {
          compilerOptions: {
            isCustomElement: (tag) => tag === 'hp-icon',
          },
        },
      }),
      viteStaticCopy({
        targets: [
          { src: 'src/images/*', dest: 'images' },
          { src: 'src/fonts/*', dest: 'fonts' },
        ],
      }),
    ],

    resolve: {
      alias: {
        '@vue-frontend': fileURLToPath(
          new URL('./vue-frontend/src', import.meta.url),
        ),
        '@': fileURLToPath(new URL('./vue-frontend/src', import.meta.url)),
      },
    },

    css: {
      preprocessorOptions: {
        scss: {
          silenceDeprecations: [
            'import',
            'global-builtin',
            'color-functions',
            'if-function',
          ],
        },
      },
    },

    build: {
      outDir: 'assets',
      emptyOutDir: false,
      sourcemap: !isProduction,
      minify: isProduction ? 'esbuild' : false,
      modulePreload: false,
      chunkSizeWarningLimit: 2000,

      rollupOptions: {
        input: {
          main: path.resolve(__dirname, 'src/js/main.js'),
          'global-scripts': path.resolve(__dirname, 'src/js/global-scripts.js'),
          style: path.resolve(__dirname, 'src/css/style.scss'),
          global: path.resolve(__dirname, 'src/css/global.scss'),
          'hts-preview': path.resolve(
            __dirname,
            'src/css/preview/preview.scss',
          ),
        },
        output: {
          entryFileNames: 'js/[name].min.js',
          chunkFileNames: 'js/chunks/[name].[hash:8].js',
          assetFileNames: ({ name = '' }) => {
            if (name.endsWith('.css')) {
              const baseName = name.replace('.css', '');
              const cssNames: Record<string, string> = {
                style: 'css/main.min.css',
                global: 'css/global.min.css',
                'hts-preview': 'css/hts-preview.min.css',
              };

              return cssNames[baseName] || `css/${baseName}.min.css`;
            }
            if (/\.(png|jpe?g|gif|svg)$/i.test(name)) {
              return 'images/[name][extname]';
            }
            if (/\.(woff2?|ttf|eot)$/i.test(name)) {
              return 'fonts/[name][extname]';
            }

            return 'assets/[name][extname]';
          },
        },
        external: ['jquery'],
      },
    },

    server: {
      port: 3000,
      hmr: { host: 'localhost' },
      watch: { usePolling: true },
    },
  };
});
