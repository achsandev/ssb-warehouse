/// <reference types="vite/client" />

declare module 'vuetify/styles' {}
declare module 'vuetify/util/colors' {
  const colors: Record<string, Record<string, string>>
  export default colors
}
