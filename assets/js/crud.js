/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { click, sendForm, sendRequest, updateDom } from './ajax'
import { closeModal, openModal } from './modal/modal-manager'
import { getElement } from './options-resolver'

const ready = (callback) => {
  if (document.readyState !== 'loading') callback()
  else document.addEventListener('DOMContentLoaded', callback)
}

ready(function () {
  document.addEventListener('submit', function (event) {
    if (event.target.matches('form[data-ec-crud-toggle="search-form"]')) {
      onSubmitCrudSearchForm(event)
    }

    if (event.target.matches('[data-ec-crud-toggle="display-settings"] form')) {
      onCrudDisplaySettingsSubmit(event)
    }
  })

  document.addEventListener('click', function (event) {
    if (event.target.matches('button[data-ec-crud-toggle="search-reset"]')) {
      onResetCrudSearchForm(event)
    }

    if (event.target.matches('button[data-ec-crud-toggle="display-settings-button"]')) {
      onCrudDisplaySettingsOpen(event)
    }

    if (event.target.matches('button[data-ec-crud-toggle="display-settings-check-all-columns"]')) {
      onCrudDisplaySettingsCheckAllColumns(event)
    }

    if (event.target.matches('button[data-ec-crud-toggle="display-settings-uncheck-all-columns"]')) {
      onCrudDisplaySettingsUncheckAllColumns(event)
    }

    if (event.target.matches('button[data-ec-crud-toggle="display-settings-reset"]')) {
      onCrudDisplaySettingsReset(event)
    }
  })
})

function onSubmitCrudSearchForm (event) {
  event.preventDefault()

  const form = event.target
  const searchContainer = getElement('#' + form.getAttribute('data-crud-search-id'))
  const listContainer = getElement('#' + form.getAttribute('data-crud-list-id'))

  sendForm(form, {
    responseDataType: 'json',
    onSuccess: function (json, response) {
      updateDom(searchContainer, 'update', json.render_search)
      updateDom(listContainer, 'update', json.render_list)
    }
  })
}

function onResetCrudSearchForm (event) {
  const button = event.target
  const searchContainer = getElement('#' + button.getAttribute('data-crud-search-id'))
  const listContainer = getElement('#' + button.getAttribute('data-crud-list-id'))

  click(button, {
    responseDataType: 'json',
    onSuccess: function (json, response) {
      updateDom(searchContainer, 'update', json.render_search)
      updateDom(listContainer, 'update', json.render_list)
    }
  })
}

function onCrudDisplaySettingsOpen (event) {
  const button = event.target
  const displaySettingsContainer = getElement('#' + button.getAttribute('data-display-settings'))
  const isModal = displaySettingsContainer.getAttribute('data-modal') === '1'

  if (isModal) {
    openDisplaySettings(displaySettingsContainer)

    return
  }

  if (displaySettingsContainer.offsetWidth > 0 && displaySettingsContainer.offsetHeight > 0) { // is visible ?
    closeDisplaySettings(displaySettingsContainer)
  } else {
    openDisplaySettings(displaySettingsContainer)
  }
}

function onCrudDisplaySettingsCheckAllColumns (event) {
  const button = event.target
  button.parentNode.closest('div[data-ec-crud-toggle="display-settings"]').querySelectorAll('input[type=checkbox]').forEach(checkbox => {
    checkbox.checked = true
  })
}

function onCrudDisplaySettingsUncheckAllColumns (event) {
  const button = event.target
  button.parentNode.closest('div[data-ec-crud-toggle="display-settings"]').querySelectorAll('input[type=checkbox]').forEach(checkbox => {
    checkbox.checked = false
  })
}

function onCrudDisplaySettingsReset (event) {
  const button = event.target
  const displaySettingsContainer = button.parentNode.closest('div[data-ec-crud-toggle="display-settings"]')
  const listContainer = getElement('#' + displaySettingsContainer.getAttribute('data-crud-list-id'))

  closeDisplaySettings(displaySettingsContainer)

  sendRequest({
    url: button.getAttribute('data-reset-url'),
    update: listContainer
  })
}

function onCrudDisplaySettingsSubmit (event) {
  event.preventDefault()
  const form = event.target

  const displaySettingsContainer = form.parentNode.closest('div[data-ec-crud-toggle="display-settings"]')
  const listContainer = getElement('#' + displaySettingsContainer.getAttribute('data-crud-list-id'))

  closeDisplaySettings(displaySettingsContainer)

  sendForm(form, {
    responseDataType: 'json',
    onSuccess: function (json, response) {
      const displaySettingsContainerId = displaySettingsContainer.getAttribute('id') // Backup before deletion (by updateDom)
      updateDom(listContainer, 'update', json.render_list)
      if (!json.form_is_valid) {
        openDisplaySettings(getElement('#' + displaySettingsContainerId))
      }
    }
  })
}

function openDisplaySettings (displaySettingsContainer) {
  const isModal = displaySettingsContainer.getAttribute('data-modal') === '1'
  if (isModal) {
    openModal({
      element: displaySettingsContainer
    })
  } else {
    displaySettingsContainer.style.display = 'block'
  }
}

function closeDisplaySettings (displaySettingsContainer) {
  const isModal = displaySettingsContainer.getAttribute('data-modal') === '1'

  if (isModal) {
    closeModal(displaySettingsContainer)
  } else {
    displaySettingsContainer.style.display = 'none'
  }
}
