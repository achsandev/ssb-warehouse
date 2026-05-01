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
         * Manual chunking — split per LIBRARY, bukan per sub-direktori.
         *
         * Pelajaran dari TDZ error sebelumnya (`Cannot access 'et' before
         * initialization`): membagi satu library (mis. Vuetify) ke beberapa
         * chunk berbeda BERBAHAYA karena Vuetify punya circular dependency
         * antar komponen (VDataTable internally pakai VAutocomplete, dst).
         * Saat chunk loading order tidak deterministik, variable di-akses
         * sebelum module parent-nya ter-initialize → TDZ.
         *
         * Aturan aman:
         *   - Satu library = satu chunk.
         *   - Group library dengan dependency tight (vue + router + pinia)
         *     ke chunk yang sama supaya pasti ter-initialize bersama.
         *   - Library independent (charts, math) boleh chunk sendiri.
         */
        manualChunks: (id: string): string | undefined => {
          if (!id.includes('node_modules')) return undefined

          // Seluruh Vuetify dalam SATU chunk — wajib karena cross-component deps.
          if (id.includes('vuetify')) return 'vendor-vuetify'

          // ag-charts standalone, tidak ada cross-deps berbahaya.
          if (id.includes('ag-charts')) return 'vendor-charts'

          // mathjs ekosistem (mathjs + dependency-nya).
          if (
            id.includes('mathjs')
            || id.includes('decimal.js')
            || id.includes('complex.js')
            || id.includes('fraction.js')
          ) return 'vendor-math'

          // Vue core stack — vue, router, pinia, vueuse digabung supaya
          // urutan initialize-nya konsisten (pinia & router butuh vue).
          if (
            /node_modules\/vue(\/|$)/.test(id)
            || id.includes('vue-router')
            || id.includes('pinia')
            || id.includes('@vueuse')
          ) return 'vendor-vue'

          // Library kecil (dayjs, axios, casl, vee-validate, yup, dsb)
          // dibiarkan ikut chunk default Rollup — splitting terlalu granular
          // justru menambah HTTP overhead tanpa manfaat ukuran.
          return undefined
        },
      },
    },
  },
})
