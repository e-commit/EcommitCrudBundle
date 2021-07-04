/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import * as optionsResolver from './options-resolver';
import runCallback from './callback';

$(function () {
    $(document).on('click', '.ec-crud-ajax-click-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-ajax-click-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        click(this);
    });

    $(document).on('click', 'a.ec-crud-ajax-link-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-ajax-link-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        link(this);
    });

    $(document).on('submit', 'form.ec-crud-ajax-form-auto', function (event) {
        event.preventDefault();
        const eventBefore = $.Event('ec-crud-ajax-form-auto-before');
        $(this).trigger(eventBefore);
        if (eventBefore.isDefaultPrevented()) {
            return;
        }

        sendForm(this);
    });
});

export function sendRequest (options) {
    options = optionsResolver.resolve(
        {
            url: null,
            update: null,
            updateMode: 'update',
            onBeforeSend: null,
            onSuccess: null,
            onError: null,
            onComplete: null,
            dataType: 'html',
            method: 'POST',
            data: null,
            cache: false,
            options: {}
        },
        options
    );

    options = $.extend({}, options, options.options)

    if (optionsResolver.isNotBlank(options.url) === false) {
        console.error('Value required: url');

        return;
    }

    if (optionsResolver.isNotBlank(options.onBeforeSend)) {
        runCallback(options.onBeforeSend, options);
    }
    if (options.stop !== undefined && options.stop === true) {
        return;
    }

    const callbacksSuccess = [];
    if (optionsResolver.isNotBlank(options.update)) {
        callbacksSuccess.push({
            priority: 10,
            callback: function (args) {
                updateDom(options.update, options.updateMode, args.data);
            }
        })
    }
    if (optionsResolver.isNotBlank(options.onSuccess)) {
        callbacksSuccess.push(options.onSuccess);
    }

    $.ajax({
        url: options.url,
        type: options.method,
        dataType: options.dataType,
        cache: options.cache,
        data: options.data,
        success: function (data, textStatus, jqXHR) {
            runCallback(callbacksSuccess, {
                data: data,
                textStatus: textStatus,
                jqXHR: jqXHR
            });
        },
        error: function (jqXHR, textStatus, errorThrown) {
            runCallback(options.onError, {
                jqXHR: jqXHR,
                textStatus: textStatus,
                errorThrown: errorThrown
            });
        },
        complete: function (jqXHR, textStatus) {
            runCallback(options.onComplete, {
                jqXHR: jqXHR,
                textStatus: textStatus
            });
        }
    });
}

export function click (element, options) {
    // Options in data-* override options argument
    options = optionsResolver.resolve(
        options,
        optionsResolver.getDataAttributes(element, 'ecCrudAjax')
    );

    sendRequest(options);
}

export function link (link, options) {
    // Options in data-* override options argument
    // Option argument override href
    options = optionsResolver.resolve(
        {
            url: $(link).attr('href')
        },
        optionsResolver.resolve(
            options,
            optionsResolver.getDataAttributes(link, 'ecCrudAjax')
        )
    );

    sendRequest(options);
}

export function sendForm (form, options) {
    // Options in data-* override options argument
    // Option argument override action, method and data form
    options = optionsResolver.resolve(
        {
            url: $(form).attr('action'),
            method: $(form).attr('method'),
            data: $(form).serialize()
        },
        optionsResolver.resolve(
            options,
            optionsResolver.getDataAttributes(form, 'ecCrudAjax')
        )
    );

    sendRequest(options);
}

export function updateDom (element, updateMode, content) {
    const eventBefore = $.Event('ec-crud-ajax-update-dom-before', {
        element: element,
        updateMode: updateMode,
        content: content
    });
    $(element).trigger(eventBefore);
    if (eventBefore.isDefaultPrevented()) {
        return;
    }
    updateMode = eventBefore.updateMode;
    content = eventBefore.content;

    if (updateMode === 'update') {
        $(element).html(content);
    } else if (updateMode === 'before') {
        $(element).before(content);
    } else if (updateMode === 'after') {
        $(element).after(content);
    } else if (updateMode === 'prepend') {
        $(element).prepend(content);
    } else if (updateMode === 'append') {
        $(element).append(content);
    } else {
        console.error('Bad updateMode: ' + updateMode);

        return;
    }

    const eventAfter = $.Event('ec-crud-ajax-update-dom-after', {
        element: element,
        updateMode: updateMode,
        content: content
    });
    $(element).trigger(eventAfter);
}
