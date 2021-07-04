/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';

export default function (callbacks, arg) {
    if (undefined === callbacks || callbacks === null) {
        return;
    }

    if (typeof callbacks === 'string' || callbacks instanceof String || callbacks instanceof Function) {
        callbacks = [callbacks];
    }

    if (Array !== callbacks.constructor) {
        return;
    }

    const newCallbacks = [];
    $.each(callbacks, function (key, value) {
        addCallbacksToStack(value, newCallbacks);
    });

    newCallbacks.sort(function (a, b) {
        if (parseInt(a.priority, 10) >= parseInt(b.priority, 10)) {
            return -1;
        }

        return 1;
    });

    $.each(newCallbacks, function (key, value) {
        processCallback(value.callback, arg);
    });
}

function addCallbacksToStack (value, stack) {
    if (typeof value === 'string' || value instanceof String || value instanceof Function) {
        stack.push({
            callback: value,
            priority: 0
        });
    } else if (Array === value.constructor) {
        $.each(value, function (key, subValue) {
            addCallbacksToStack(subValue, stack);
        });
    } else if (undefined !== value.callback) {
        stack.push({
            callback: value.callback,
            priority: (value.priority !== undefined) ? value.priority : 0
        });
    }
}

function processCallback (callback, arg) {
    if (callback instanceof Function) {
        callback(arg);

        return;
    }

    if (typeof callback !== 'string' && !(callback instanceof String)) {
        return;
    }

    const patternFunction = /^function\s*\(/i;
    if (patternFunction.test(callback)) {
        callback = eval('(' + callback + ')');
        callback(arg);

        return;
    }

    const patternFunctionContent = /^\/(.+)\/(.+)$/i;
    const groups = callback.match(patternFunctionContent);
    if (groups !== null && groups.length > 0) {
        console.debug(groups);
        callback = eval('(function(' + groups[1] + ') {' + groups[2] + '})');
        callback(arg);

        return;
    }

    eval(callback);
}
