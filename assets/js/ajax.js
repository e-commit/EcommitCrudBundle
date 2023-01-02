/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as optionsResolver from './options-resolver'
import runCallback from './callback'

const ready = (callback) => {
  if (document.readyState !== 'loading') callback()
  else document.addEventListener('DOMContentLoaded', callback)
}

ready(function () {
  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-ec-crud-toggle="ajax-click"]')) {
      onClickAuto(event)
    }

    if (event.target.closest('a[data-ec-crud-toggle="ajax-link"]')) {
      onClickLinkAuto(event)
    }
  })

  document.addEventListener('submit', function (event) {
    if (event.target.matches('form[data-ec-crud-toggle="ajax-form"]')) {
      onSubmitFormAuto(event)
    }
  })
})

function onClickAuto (event) {
  event.preventDefault()
  const element = event.target.closest('[data-ec-crud-toggle="ajax-click"]')
  const eventBefore = new CustomEvent('ec-crud-ajax-click-auto-before', {
    bubbles: true,
    cancelable: true
  })
  element.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  click(element).catch((error) => console.error(error))
}

function onClickLinkAuto (event) {
  event.preventDefault()
  const aLink = event.target.closest('a[data-ec-crud-toggle="ajax-link"]')
  const eventBefore = new CustomEvent('ec-crud-ajax-link-auto-before', {
    bubbles: true,
    cancelable: true
  })
  aLink.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  link(aLink).catch((error) => console.error(error))
}

function onSubmitFormAuto (event) {
  event.preventDefault()
  const eventBefore = new CustomEvent('ec-crud-ajax-form-auto-before', {
    bubbles: true,
    cancelable: true
  })
  event.target.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }

  sendForm(event.target).catch((error) => console.error(error))
}

export function sendRequest (options) {
  const eventBeginning = new CustomEvent('ec-crud-ajax', {
    cancelable: true,
    detail: {
      options: options
    }
  })
  document.dispatchEvent(eventBeginning)
  if (eventBeginning.defaultPrevented) {
    return new Promise((resolve, reject) => {
      resolve(null)
    })
  }

  options = optionsResolver.resolve(
    {
      url: null,
      update: null,
      updateMode: 'update',
      onBeforeSend: null,
      onSuccess: null,
      onError: null,
      onComplete: null,
      responseDataType: 'text',
      method: 'POST',
      query: {},
      body: null,
      successfulResponseRequired: false,
      cache: false,
      options: {}
    },
    options
  )

  if (optionsResolver.isNotBlank(options.url) === false) {
    return new Promise((resolve, reject) => {
      reject(new TypeError('Value required: url'))
    })
  }

  options.urlResolved = resolveUrl(options)

  const callbacksSuccess = []
  if (optionsResolver.isNotBlank(options.update)) {
    callbacksSuccess.push({
      priority: 10,
      callback: (data, response) => updateDom(options.update, options.updateMode, data)
    })
  }
  if (optionsResolver.isNotBlank(options.onSuccess)) {
    callbacksSuccess.push(options.onSuccess)
  }

  let fetchOptions = {
    method: options.method
  }
  if (options.body) {
    if (typeof options.body === 'string' || options.body instanceof String || options.body instanceof FormData) {
      fetchOptions.body = options.body
    } else if (options.body instanceof Object) {
      const formData = new FormData()
      Object.entries(options.body).forEach(entry => {
        if (Array.isArray(entry[1])) {
          entry[1].forEach(subEntry => {
            formData.append(entry[0], subEntry)
          })
        } else {
          formData.append(entry[0], entry[1])
        }
      })
      fetchOptions.body = formData
    } else if (options.body !== null) {
      return new Promise((resolve, reject) => {
        reject(new TypeError('Bad type for option "body"'))
      })
    }
  }

  const eventBeforeSend = new CustomEvent('ec-crud-ajax-before-send', {
    cancelable: true,
    detail: {
      options: options
    }
  })
  document.dispatchEvent(eventBeforeSend)
  if (eventBeforeSend.defaultPrevented) {
    return new Promise((resolve, reject) => {
      resolve(null)
    })
  }
  if (optionsResolver.isNotBlank(options.onBeforeSend)) {
    runCallback(options.onBeforeSend, options)
  }
  if (options.stop !== undefined && options.stop === true) {
    return new Promise((resolve, reject) => {
      resolve(null)
    })
  }

  fetchOptions = optionsResolver.extend(fetchOptions, options.options)

  const fetchPromise = fetch(options.urlResolved, fetchOptions)
  const ajaxPromise = new Promise((resolve, reject) => {
    fetchPromise.then(response => {
      if (response.ok) {
        // Response OK (status in the range 200 – 299)

        let dataPromise
        if (options.responseDataType === 'text' || options.responseDataType === 'json') {
          // Using a clone avoids "TypeError: Already read" when response read is read a 2nd time later
          const responseCloned = response.clone()

          try {
            if (options.responseDataType === 'text') {
              dataPromise = responseCloned.text()
            } else if (options.responseDataType === 'json') {
              dataPromise = responseCloned.json()
            }
          } catch (e) {
          }
        } else {
          dataPromise = new Promise((resolve, reject) => {
            resolve(null)
          })
        }

        dataPromise.then(data => {
          executeEventsAndCallbacksSuccess(callbacksSuccess, options, data, response)
          resolve(response)
        })

        dataPromise.catch(error => {
          error = 'Error during fetching response body: ' + error
          executeEventsAndCallbacksError(options, error, response)
          reject(error)
        })
      } else {
        // Response not OK (status not in the range 200 – 299)
        executeEventsAndCallbacksError(options, response.statusText, response)
        if (options.successfulResponseRequired) {
          reject(new Error('The response is not successful: ' + response.statusText))
        } else {
          resolve(response)
        }
      }
    })

    fetchPromise.catch(error => {
      error = 'Error during query execution: ' + error
      executeEventsAndCallbacksError(options, error, null)
      reject(error)
    })
  })

  return ajaxPromise
}

export function click (element, options) {
  // Options in data-* override options argument
  options = optionsResolver.resolve(
    options,
    optionsResolver.getDataAttributes(element, 'ecCrudAjax')
  )

  return sendRequest(options)
}

export function link (link, options) {
  link = optionsResolver.getElement(link)
  // Options in data-* override options argument
  // Option argument override href
  options = optionsResolver.resolve(
    {
      url: link.getAttribute('href')
    },
    optionsResolver.resolve(
      options,
      optionsResolver.getDataAttributes(link, 'ecCrudAjax')
    )
  )

  return sendRequest(options)
}

export function sendForm (form, options) {
  form = optionsResolver.getElement(form)
  // Options in data-* override options argument
  // Option argument override action, method and data form
  options = optionsResolver.resolve(
    {
      url: form.getAttribute('action'),
      method: form.getAttribute('method'),
      body: new FormData(form)
    },
    optionsResolver.resolve(
      options,
      optionsResolver.getDataAttributes(form, 'ecCrudAjax')
    )
  )

  return sendRequest(options)
}

export function updateDom (element, updateMode, content) {
  const originElement = element
  element = optionsResolver.getElement(element)
  const eventBefore = new CustomEvent('ec-crud-ajax-update-dom-before', {
    bubbles: true,
    cancelable: true,
    detail: {
      element: originElement,
      updateMode: updateMode,
      content: content
    }
  })
  element.dispatchEvent(eventBefore)
  if (eventBefore.defaultPrevented) {
    return
  }
  updateMode = eventBefore.detail.updateMode
  content = eventBefore.detail.content

  if (updateMode === 'update') {
    element.innerHTML = content
  } else if (updateMode === 'before') {
    element.outerHTML = content + element.outerHTML
  } else if (updateMode === 'after') {
    element.outerHTML = element.outerHTML + content
  } else if (updateMode === 'prepend') {
    element.innerHTML = content + element.innerHTML
  } else if (updateMode === 'append') {
    element.innerHTML = element.innerHTML + content
  } else {
    console.error('Bad updateMode: ' + updateMode)

    return
  }

  const eventAfter = new CustomEvent('ec-crud-ajax-update-dom-after', {
    bubbles: true,
    detail: {
      element: originElement,
      updateMode: updateMode,
      content: content
    }
  })
  element.dispatchEvent(eventAfter)
}

function resolveUrl (options) {
  const url = new URL(options.url, window.location.origin)
  const searchParams = url.searchParams

  Object.entries(options.query).forEach(entry => {
    if (Array.isArray(entry[1])) {
      if (searchParams.has(entry[0])) {
        searchParams.delete(entry[0])
      }
      entry[1].forEach(subEntry => {
        searchParams.append(entry[0], subEntry)
      })
    } else {
      searchParams.set(entry[0], entry[1])
    }
  })

  if (!options.cache && !searchParams.has('_')) {
    searchParams.set('_', Date.now())
  }

  return url.toString()
}

function executeEventsAndCallbacksSuccess (callbacksSuccess, options, data, response) {
  const eventOnSuccess = new CustomEvent('ec-crud-ajax-on-success', {
    detail: {
      data: data,
      response: response
    }
  })
  document.dispatchEvent(eventOnSuccess)
  runCallback(callbacksSuccess, data, response)

  const eventOnComplete = new CustomEvent('ec-crud-ajax-on-complete', {
    detail: {
      statusText: response.statusText,
      response: response
    }
  })
  document.dispatchEvent(eventOnComplete)
  runCallback(options.onComplete, response.statusText, response)
}

function executeEventsAndCallbacksError (options, statusText, response) {
  const eventOnError = new CustomEvent('ec-crud-ajax-on-error', {
    detail: {
      statusText: statusText,
      response: response
    }
  })
  document.dispatchEvent(eventOnError)
  runCallback(options.onError, statusText, response)

  const eventOnComplete = new CustomEvent('ec-crud-ajax-on-complete', {
    detail: {
      statusText: statusText,
      response: response
    }
  })
  document.dispatchEvent(eventOnComplete)
  runCallback(options.onComplete, statusText, response)
}
