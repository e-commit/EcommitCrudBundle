/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as optionsResolver from '../options-resolver'
import * as ajax from '../ajax'

const ENGINE_KEY = Symbol.for('ecommit.crudbundle.modalengine')
const globalSymbols = Object.getOwnPropertySymbols(global)
if (globalSymbols.indexOf(ENGINE_KEY) === -1) {
  global[ENGINE_KEY] = null
}

const ready = (callback) => {
  if (document.readyState !== 'loading') callback()
  else document.addEventListener('DOMContentLoaded', callback)
}

ready(function () {
  document.addEventListener('click', function (event) {
    if (event.target.matches('[data-ec-crud-toggle="modal"]')) {
      onClickModalAuto(event)
    }

    if (event.target.matches('button[data-ec-crud-toggle="remote-modal"]')) {
      onClickButtonRemoteModalAuto(event)
    }

    if (event.target.matches('a[data-ec-crud-toggle="remote-modal"]')) {
      onClickLinkRemoteModalAuto(event)
    }
  })
})

function onClickModalAuto (event) {
  event.preventDefault()
  const eventBefore = new CustomEvent('ec-crud-modal-auto-before', {
    bubbles: true,
    cancelable: true
  })
  event.target.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  openModal(optionsResolver.getDataAttributes(event.target, 'ecCrudModal'))
}

function onClickButtonRemoteModalAuto (event) {
  event.preventDefault()
  const eventBefore = new CustomEvent('ec-crud-remote-modal-auto-before', {
    bubbles: true,
    cancelable: true
  })
  event.target.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  openRemoteModal(optionsResolver.getDataAttributes(event.target, 'ecCrudModal'))
}

function onClickLinkRemoteModalAuto (event) {
  event.preventDefault()
  const eventBefore = new CustomEvent('ec-crud-remote-modal-auto-before', {
    bubbles: true,
    cancelable: true
  })
  event.target.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  // Options in data-* override href
  const options = optionsResolver.resolve(
    {
      url: event.target.getAttribute('href')
    },
    optionsResolver.getDataAttributes(event.target, 'ecCrudModal')
  )

  openRemoteModal(options)
}

export function defineEngine (newEngine) {
  global[ENGINE_KEY] = newEngine
}

export function getEngine () {
  if (global[ENGINE_KEY] === null) {
    console.error('Engine not defined')

    return
  }

  return global[ENGINE_KEY]
}

export function openModal (options) {
  options = optionsResolver.resolve(
    {
      element: null,
      onOpen: null,
      onClose: null
    },
    options
  )

  if (optionsResolver.isNotBlank(options.element) === false) {
    console.error('Value required: element')

    return
  }

  getEngine().openModal(options)
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
  )

  let hasError = false;
  ['url', 'element', 'elementContent', 'method'].forEach((value) => {
    if (optionsResolver.isNotBlank(options[value]) === false) {
      console.error('Value required: ' + value)
      hasError = true
    }
  })
  if (hasError === true) {
    return
  }

  const ajaxOptions = optionsResolver.resolve(
    {
      url: options.url,
      method: options.method,
      update: options.elementContent
    },
    options.ajaxOptions
  )

  const callbacksSuccess = [
    {
      priority: 1,
      callback: function (data, textStatus, jqXHR) {
        openModal(options)
      }
    }
  ]
  if (optionsResolver.isNotBlank(ajaxOptions.onSuccess)) {
    callbacksSuccess.push(ajaxOptions.onSuccess)
  }
  ajaxOptions.onSuccess = callbacksSuccess

  ajax.sendRequest(ajaxOptions)
}

export function closeModal (element) {
  getEngine().closeModal(element)
}
