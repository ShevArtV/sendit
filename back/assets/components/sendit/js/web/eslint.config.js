import js from '@eslint/js';
import globals from 'globals';

export default [
  js.configs.recommended, // Используем рекомендованные правила ESLint
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        ...globals.browser,
        iziToast: 'readonly',
        ym: 'readonly',
      },
    },
    rules: {
      'indent': ['error', 2],
      'quotes': ['error', 'single', { allowTemplateLiterals: true }],
      'semi': ['error', 'always'],
      'no-useless-escape': 'off',
    }
  },
];
