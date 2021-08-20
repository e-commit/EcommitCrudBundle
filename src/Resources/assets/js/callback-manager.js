/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const ENGINE_KEY = Symbol.for('ecommit.crudbundle.callbackengine');
const globalSymbols = Object.getOwnPropertySymbols(global);
if (globalSymbols.indexOf(ENGINE_KEY) === -1) {
    global[ENGINE_KEY] = [];
}

export function registerCallback (name, callback) {
    if (typeof name !== 'string' && !(name instanceof String)) {
        console.error('Bad name');
    }
    if (!(callback instanceof Function)) {
        console.error('Invalid callback ' + name);
    }

    global[ENGINE_KEY][name] = callback;
}

export function callbackIsRegistred (name) {
    if (typeof name !== 'string' && !(name instanceof String)) {
        console.error('Bad name');
    }

    return (undefined !== global[ENGINE_KEY][name]);
}

export function getRegistredCallback (name) {
    if (!callbackIsRegistred(name)) {
        console.error('Callback not found: ' + name);

        return null;
    }

    return global[ENGINE_KEY][name];
}

export function clear () {
    global[ENGINE_KEY] = [];
}
