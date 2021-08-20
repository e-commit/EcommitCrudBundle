module.exports = {
    env: {
        'browser': true,
        'es6': true,
        'jasmine': true,
        'node': true
    },
    extends: [
        'standard'
    ],
    globals: {
        Atomics: 'readonly',
        SharedArrayBuffer: 'readonly'
    },
    ignorePatterns: ['/src/Resources/assets/js/scrollToFirstMessage.js'],
    parserOptions: {
        ecmaVersion: 2018,
        sourceType: 'module'
    },
    rules: {
        'indent': ['error', 4],
        'linebreak-style': ['error', 'unix'],
        'semi': 'off'
    }
}
