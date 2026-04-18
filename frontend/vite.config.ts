import { fileURLToPath, URL } from 'node:url'

import { defineConfig, PluginOption } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import vuetify from 'vite-plugin-vuetify'
import icons from 'unplugin-icons/vite'
import { existsSync, rmSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'

const hotFile = '../public/hot'
const portDevServer = 3330
const hostDevServer = `http://localhost:${portDevServer}`

function integratedDevServer(): PluginOption {
  return {
    name: 'integratedDevServer',
    enforce: 'post',
    configureServer(server) {
      server.httpServer?.once('listening', () => {
        writeFileSync(hotFile, hostDevServer)
      })

      process.on('exit', () => {
        if (existsSync(hotFile)) {
          rmSync(hotFile)
        }
      })
      process.on('SIGINT', () => process.exit())
      process.on('SIGTERM', () => process.exit())
      process.on('SIGHUP', () => process.exit())

      return () => server.middlewares.use((_, __, next) => {
        next()
      })
    },
  }
}

function absolutifyPaths(): PluginOption {
  return {
    name: 'absolutify-paths',
    enforce: 'pre',
    apply: 'serve',
    transform: (code: string) => {
      const transform = code.replace(/\/src\/(.*)\.(svg|png|jpg|woff2)/, `${hostDevServer}/src/$1.$2`)
      return {
        code: transform,
        map: null,
      }
    },
  }
}

export default defineConfig({
  plugins: [
    integratedDevServer(),
    absolutifyPaths(),
    vue(),
    vueDevTools(),
    vuetify({
      autoImport: false,
      styles: {
        configFile: './src/assets/styles/scss/vuetify.variable.scss'
      }
    }),
    icons({
      compiler: 'vue3',
      autoInstall: false
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  base: process.env.NODE_ENV === 'production' ? '/dist-build/' : '/',
  server: {
    port: portDevServer,
    watch: {
      usePolling: true,
    },
    cors: true,
  },
  build: {
    manifest: true,
    emptyOutDir: true,
    outDir: '../public/dist-build/',
    // SPA Vuetify 3 + charts + mathjs secara realistis punya satu-dua vendor
    // chunk > 900 kB (vuetify-components sendiri ~1 MB, mathjs ~600 kB).
    // 1500 kB memberi ruang tanpa menutupi regresi nyata dari app code.
    chunkSizeWarningLimit: 1500,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
      output: {
        /**
         * Manual chunking — memisahkan vendor berat ke chunk sendiri supaya:
         *  - Parallel download saat initial load.
         *  - Cache lebih tahan lama: vendor jarang berubah, chunk app yang
         *    sering berubah tidak ikut invalidate cache user.
         *  - Mengurangi ukuran main bundle.
         *
         * Untuk Vuetify, splitting dibuat lebih granular:
         *  - `vendor-vuetify-labs`    : komponen lab (VFileUpload, dsb).
         *  - `vendor-vuetify-tables`  : VDataTable & VDataTableServer (berat).
         *  - `vendor-vuetify-inputs`  : VAutocomplete/VTextField/VSelect family.
         *  - `vendor-vuetify`         : core & komponen layout sisanya.
         *
         * Library kecil (dayjs, axios, iconify JSON) dibiarkan ikut chunk
         * default Rollup — splitting terlalu granular justru menambah
         * overhead HTTP tanpa manfaat ukuran.
         */
        manualChunks: (id: string): string | undefined => {
          if (!id.includes('node_modules')) return undefined

          // Vuetify split bertingkat — cek path paling spesifik dulu.
          if (id.includes('vuetify/lib/labs/') || id.includes('vuetify/labs/')) {
            return 'vendor-vuetify-labs'
          }
          if (/vuetify\/lib\/components\/(VData|VVirtual)/.test(id)) {
            return 'vendor-vuetify-tables'
          }
          if (
            /vuetify\/lib\/components\/(VAutocomplete|VCombobox|VSelect|VTextField|VTextarea|VFileInput|VOtpInput|VNumberInput)/.test(id)
          ) {
            return 'vendor-vuetify-inputs'
          }
          if (id.includes('vuetify')) return 'vendor-vuetify'

          if (id.includes('ag-charts')) return 'vendor-charts'
          if (id.includes('mathjs') || id.includes('decimal.js') || id.includes('complex.js') || id.includes('fraction.js')) {
            return 'vendor-math'
          }
          if (id.includes('vee-validate') || id.includes('yup')) return 'vendor-validation'
          if (id.includes('@casl')) return 'vendor-casl'
          if (
            id.includes('vue-router')
            || id.includes('pinia')
            || id.includes('@vueuse')
            || /node_modules\/vue(\/|$)/.test(id)
          ) return 'vendor-vue'

          return undefined
        },
      },
    },
  },
})
