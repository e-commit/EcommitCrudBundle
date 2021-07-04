/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import { click, sendForm, sendRequest, updateDom } from './ajax';
import { closeModal, openModal } from './modal/modal-manager';

$(document).on('submit', 'form.ec-crud-search-form', function (event) {
    event.preventDefault();

    const searchId = $(this).attr('data-crud-search-id');
    const listId = $(this).attr('data-crud-list-id');

    sendForm(this, {
        onSuccess: function (args) {
            const json = $.parseJSON(args.data);

            updateDom($('#' + searchId), 'update', json.render_search);
            updateDom($('#' + listId), 'update', json.render_list);
        }
    });
});

$(document).on('click', 'button.ec-crud-search-reset', function (event) {
    const searchId = $(this).attr('data-crud-search-id');
    const listId = $(this).attr('data-crud-list-id');

    click(this, {
        onSuccess: function (args) {
            const json = $.parseJSON(args.data);

            updateDom($('#' + searchId), 'update', json.render_search);
            updateDom($('#' + listId), 'update', json.render_list);
        }
    });
});

$(document).on('click', 'button.ec-crud-display-settings-button', function (event) {
    const displaySettingsContainerId = $(this).attr('data-display-settings');
    const isModal = $('#' + displaySettingsContainerId).attr('data-modal') === '1';

    if (isModal) {
        openDisplaySettings($('#' + displaySettingsContainerId));

        return;
    }

    if ($('#' + displaySettingsContainerId).is(':visible')) {
        closeDisplaySettings($('#' + displaySettingsContainerId));
    } else {
        openDisplaySettings($('#' + displaySettingsContainerId));
    }
});

$(document).on('click', 'button.ec-crud-display-settings-check-all-columns', function (event) {
    $(this).parents('div.ec-crud-display-settings').find('input[type=checkbox]').each(function () {
        $(this).prop('checked', true);
    });
});

$(document).on('click', 'button.ec-crud-display-settings-uncheck-all-columns', function (event) {
    $(this).parents('div.ec-crud-display-settings').find('input[type=checkbox]').each(function () {
        $(this).prop('checked', false);
    });
});

$(document).on('click', 'button.ec-crud-display-settings-raz', function (event) {
    const displaySettingsContainer = $(this).parents('div.ec-crud-display-settings');
    const displaySettingsContainerId = $(displaySettingsContainer).attr('id');
    const listId = $(displaySettingsContainer).attr('data-crud-list-id');

    closeDisplaySettings($('#' + displaySettingsContainerId));

    sendRequest({
        url: $(this).attr('data-reset-url'),
        update: $('#' + listId)
    });
});

$(document).on('submit', '.ec-crud-display-settings form', function (event) {
    event.preventDefault();

    const displaySettingsContainer = $(this).parents('div.ec-crud-display-settings');
    const displaySettingsContainerId = $(displaySettingsContainer).attr('id');
    const listId = $(displaySettingsContainer).attr('data-crud-list-id');

    closeDisplaySettings($('#' + displaySettingsContainerId));

    sendForm(this, {
        onSuccess: function (args) {
            const json = $.parseJSON(args.data);

            updateDom($('#' + listId), 'update', json.render_list);
            if (!json.form_is_valid) {
                openDisplaySettings($('#' + displaySettingsContainerId));
            }
        }
    });
});

function openDisplaySettings (displaySettingsContainer) {
    const isModal = $(displaySettingsContainer).attr('data-modal') === '1';
    if (isModal) {
        openModal({
            element: displaySettingsContainer
        });
    } else {
        $(displaySettingsContainer).show();
    }
}

function closeDisplaySettings (displaySettingsContainer) {
    const isModal = $(displaySettingsContainer).attr('data-modal') === '1';

    if (isModal) {
        closeModal(displaySettingsContainer);
    } else {
        $(displaySettingsContainer).hide();
    }
}
