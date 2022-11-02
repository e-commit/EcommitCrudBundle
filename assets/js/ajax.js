/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery'
import * as optionsResolver from './options-resolver'
import runCallback from './callback'

$(function () {
  $(document).on('click', '.ec-crud-ajax-click-auto', function (event) {
    event.preventDefault()
    const eventBefore = $.Event('ec-crud-ajax-click-auto-before')
    $(this).trigger(eventBefore)
    if (eventBefore.isDefaultPrevented()) {
      return
    }

    click(this).catch((error) => console.error(error))
  })

  $(document).on('click', 'a.ec-crud-ajax-link-auto', function (event) {
    event.preventDefault()
    const eventBefore = $.Event('ec-crud-ajax-link-auto-before')
    $(this).trigger(eventBefore)
    if (eventBefore.isDefaultPrevented()) {
      return
    }

    link(this).catch((error) => console.error(error))
  })

  $(document).on('submit', 'form.ec-crud-ajax-form-auto', function (event) {
    event.preventDefault()
    const eventBefore = $.Event('ec-crud-ajax-form-auto-before')
    $(this).trigger(eventBefore)
    if (eventBefore.isDefaultPrevented()) {
      return
    }

    sendForm(this).catch((error) => console.error(error))
  })
})

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

  fetchOptions = $.extend(fetchOptions, options.options)

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
  )

  return sendRequest(options)
}

export function sendForm (form, options) {
  // Options in data-* override options argument
  // Option argument override action, method and data form
  options = optionsResolver.resolve(
    {
      url: $(form).attr('action'),
      method: $(form).attr('method'),
      body: new FormData($(form).get(0))
    },
    optionsResolver.resolve(
      options,
      optionsResolver.getDataAttributes(form, 'ecCrudAjax')
    )
  )

  return sendRequest(options)
}

export function updateDom (element, updateMode, content) {
  const eventBefore = $.Event('ec-crud-ajax-update-dom-before', {
    element: element,
    updateMode: updateMode,
    content: content
  })
  $(element).trigger(eventBefore)
  if (eventBefore.isDefaultPrevented()) {
    return
  }
  updateMode = eventBefore.updateMode
  content = eventBefore.content

  if (updateMode === 'update') {
    $(element).html(content)
  } else if (updateMode === 'before') {
    $(element).before(content)
  } else if (updateMode === 'after') {
    $(element).after(content)
  } else if (updateMode === 'prepend') {
    $(element).prepend(content)
  } else if (updateMode === 'append') {
    $(element).append(content)
  } else {
    console.error('Bad updateMode: ' + updateMode)

    return
  }

  const eventAfter = $.Event('ec-crud-ajax-update-dom-after', {
    element: element,
    updateMode: updateMode,
    content: content
  })
  $(element).trigger(eventAfter)
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
