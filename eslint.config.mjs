import js from '@eslint/js';
import globals from 'globals';
export default [js.configs.recommended,{files:['**/*.js','**/*.mjs'],languageOptions:{globals:{...globals.node,...globals.browser}},rules:{'no-unused-vars':['error',{argsIgnorePattern:'^_',varsIgnorePattern:'^_'}]}},{ignores:['**/build/**','**/node_modules/**']}];
