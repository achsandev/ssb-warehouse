/**
 * Registrasi module AG Charts Community.
 *
 * Sejak ag-charts v13, setiap series/axis harus di-register eksplisit sebelum
 * chart pertama dibuat. `AllCommunityModule` mencakup seluruh fitur community
 * (bar, line, donut, area, axis, legend, locale, dst).
 *
 * Docs: https://www.ag-grid.com/charts/r/module-registry/
 */
import { AllCommunityModule, ModuleRegistry } from 'ag-charts-community'

ModuleRegistry.registerModules([AllCommunityModule])
