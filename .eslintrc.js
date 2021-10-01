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
    parserOptions: {
        ecmaVersion: 2018,
        sourceType: 'module'
    },
    rules: {
        'linebreak-style': ['error', 'unix'],
    }
}
