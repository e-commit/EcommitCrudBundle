/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';

export function resolve (defaultOptions, options) {
    Object.keys(options).forEach(key => options[key] === undefined ? delete options[key] : {});

    return $.extend({}, defaultOptions, options);
}

export function getDataAttributes (element, prefix) {
    const prefixLength = prefix.length;
    const attributes = {};

    $.each($(element).data(), function (index, value) {
        if (index.length > prefixLength && index.substr(0, prefixLength) === prefix) {
            let newIndex = index.substr(prefixLength);
            newIndex = newIndex.charAt(0).toLowerCase() + newIndex.slice(1);
            attributes[newIndex] = value;
        }
    });

    return attributes;
}

export function isNotBlank (value) {
    if (undefined === value || value === null || value.length === 0) {
        return false;
    }

    return true;
}
