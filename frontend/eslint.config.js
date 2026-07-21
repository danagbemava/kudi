import js from '@eslint/js'
import prettier from 'eslint-config-prettier/flat'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'

export default defineConfigWithVueTs(
  {
    ignores: ['dist/**', 'node_modules/**'],
  },
  js.configs.recommended,
  // "essential" = correctness rules only. The formatting rules in
  // "recommended" (line breaks, attributes-per-line) conflict with the
  // formatting already used here and would be noise, not signal.
  pluginVue.configs['flat/essential'],
  vueTsConfigs.recommended,
  {
    languageOptions: {
      globals: { ...globals.browser },
    },
    rules: {
      // The guard that matters: a component used in a template but never
      // imported renders as an inert unknown element at runtime, and neither
      // vue-tsc nor the build flags it. vue-router registers these globally.
      'vue/no-undef-components': ['error', { ignorePatterns: ['RouterLink', 'RouterView'] }],
    },
  },
  {
    files: ['**/*.test.ts'],
    languageOptions: {
      globals: { ...globals.node },
    },
  },
  // Last: turns off every rule that would argue with Prettier about layout.
  prettier,
)
