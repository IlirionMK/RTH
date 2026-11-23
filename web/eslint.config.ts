import { globalIgnores } from 'eslint/config'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import pluginVue from 'eslint-plugin-vue'
import pluginVitest from '@vitest/eslint-plugin'
import pluginOxlint from 'eslint-plugin-oxlint'

export default defineConfigWithVueTs(
  {
    name: 'app/files-to-lint',
    files: ['**/*.{vue,ts,mts,tsx}'],
  },

  globalIgnores(['**/dist/**', '**/dist-ssr/**', '**/coverage/**']),

  ...pluginVue.configs['flat/essential'],
  vueTsConfigs.recommended,

  {
    name: 'app/shadcn-ui',
    files: ['src/components/ui/**/*.vue'],
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },

  {
    ...pluginVitest.configs.recommended,
    files: ['src/**/__tests__/*'],
  },

  ...pluginOxlint.buildFromOxlintConfigFile('.oxlintrc.json'),
)
