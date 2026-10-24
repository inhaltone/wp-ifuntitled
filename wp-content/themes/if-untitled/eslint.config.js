import js from '@eslint/js';
import globals from 'globals';
import { defineConfig, globalIgnores } from 'eslint/config';

export default defineConfig([
    globalIgnores(['dist/', 'node_modules/']),
    js.configs.recommended,
    {
        files: ['src/**/*.js'],
        languageOptions: { globals: globals.browser },
        rules: {
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'prefer-const': 'error',
            eqeqeq: ['error', 'always'],
        },
    },
    {
        files: ['*.js', 'vite-plugins/**/*.js'],
        languageOptions: { globals: globals.node },
    },
]);
