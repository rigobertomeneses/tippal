/**
 * ESLint Configuration para appnew54
 *
 * Incluye detección y auto-fix de imports no usados
 */

import js from '@eslint/js';
import reactPlugin from 'eslint-plugin-react';
import reactHooksPlugin from 'eslint-plugin-react-hooks';
import reactNativePlugin from 'eslint-plugin-react-native';
import unusedImportsPlugin from 'eslint-plugin-unused-imports';

export default [
  js.configs.recommended,
  {
    files: ['**/*.{js,jsx}'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      parserOptions: {
        ecmaFeatures: {
          jsx: true,
        },
      },
      globals: {
        // React Native globals
        __DEV__: 'readonly',
        // Node.js globals
        module: 'readonly',
        require: 'readonly',
        process: 'readonly',
        console: 'readonly',
        setTimeout: 'readonly',
        setInterval: 'readonly',
        clearTimeout: 'readonly',
        clearInterval: 'readonly',
        Buffer: 'readonly',
        // Browser/React Native globals
        fetch: 'readonly',
        FormData: 'readonly',
        navigator: 'readonly',
      },
    },
    plugins: {
      react: reactPlugin,
      'react-hooks': reactHooksPlugin,
      'react-native': reactNativePlugin,
      'unused-imports': unusedImportsPlugin,
    },
    rules: {
      // React Rules
      'react/jsx-uses-react': 'error',
      'react/jsx-uses-vars': 'error',
      'react/prop-types': 'off', // No usamos PropTypes en este proyecto

      // React Hooks Rules
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',

      // React Native Rules
      'react-native/no-unused-styles': 'warn',
      'react-native/no-inline-styles': 'off', // Permitimos inline styles
      'react-native/no-color-literals': 'off',

      // ⭐ UNUSED IMPORTS - Auto-fix activado
      'no-unused-vars': 'off', // Desactivar regla base
      'unused-imports/no-unused-imports': 'error', // Remover imports no usados
      'unused-imports/no-unused-vars': [
        'warn',
        {
          vars: 'all',
          varsIgnorePattern: '^_',
          args: 'after-used',
          argsIgnorePattern: '^_',
        },
      ],

      // General Rules
      'no-console': 'off', // Permitimos console.log en desarrollo
      'no-debugger': 'warn',
      'no-undef': 'error',
    },
    settings: {
      react: {
        version: 'detect',
      },
    },
  },
  {
    // Ignorar archivos generados y dependencias
    ignores: [
      'node_modules/**',
      '.expo/**',
      'android/**',
      'ios/**',
      'dist/**',
      'build/**',
      '*.config.js',
      'babel.config.js',
      'metro.config.js',
    ],
  },
];
