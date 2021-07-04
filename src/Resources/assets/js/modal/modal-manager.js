/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as optionsResolver from '../options-resolver';
import $ from 'jquery';
import * as ajax from '../ajax';

const ENGINE_KEY = Symbol.for('ecommit.crudbundle.modalengine');
const globalSymbols = Object.getOwnPropertySymbols(global);
if (globalSymbols.indexOf(ENGINE_KEY) === -1) {
    global[ENGINE_KEY] = null;
}

$(function () {
    $(document).on('click', '.ec-crud-modal-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-modal-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        openModal(optionsResolver.getDataAttributes(this, 'ecCrudModal'));
    });

    $(document).on('click', 'button.ec-crud-remote-modal-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-remote-modal-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        openRemoteModal(optionsResolver.getDataAttributes(this, 'ecCrudModal'));
    });

    $(document).on('click', 'a.ec-crud-remote-modal-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-remote-modal-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        // Options in data-* override href
        const options = optionsResolver.resolve(
            {
                url: $(this).attr('href')
            },
            optionsResolver.getDataAttributes(this, 'ecCrudModal')
        );

        openRemoteModal(options);
    });
});

export function defineEngine (newEngine) {
    global[ENGINE_KEY] = newEngine;
}

export function getEngine () {
    if (global[ENGINE_KEY] === null) {
        console.error('Engine not defined');

        return;
    }

    return global[ENGINE_KEY];
}

export function openModal (options) {
    options = optionsResolver.resolve(
        {
            element: null,
            onOpen: null,
            onClose: null
        },
        options
    );

    if (optionsResolver.isNotBlank(options.element) === false) {
        console.error('Value required: element');

        return;
    }

    getEngine().openModal(options);
}

export function openRemoteModal (options) {
    options = optionsResolver.resolve(
        {
            url: null,
            element: null,
            elementContent: null,
            onOpen: null,
            onClose: null,
            method: 'POST',
            ajaxOptions: {}
        },
        options
    );

    let hasError = false;
    $.each(['url', 'element', 'elementContent', 'method'], function (index, value) {
        if (optionsResolver.isNotBlank(options[value]) === false) {
            console.error('Value required: ' + value);
            hasError = true;
        }
    });
    if (hasError === true) {
        return;
    }

    const ajaxOptions = optionsResolver.resolve(
        {
            url: options.url,
            method: options.method,
            update: options.elementContent
        },
        options.ajaxOptions
    );

    const callbacksSuccess = [
        {
            priority: 1,
            callback: function (args) {
                openModal(options);
            }
        }
    ];
    if (optionsResolver.isNotBlank(ajaxOptions.onSuccess)) {
        callbacksSuccess.push(ajaxOptions.onSuccess);
    }
    ajaxOptions.onSuccess = callbacksSuccess;

    ajax.sendRequest(ajaxOptions);
}

export function closeModal (element) {
    getEngine().closeModal(element);
}
