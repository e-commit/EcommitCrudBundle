/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as ajax from '@ecommit/crud-bundle/js/ajax'
import * as callbackManager from '@ecommit/crud-bundle/js/callback-manager'
import $ from 'jquery'
import wait from './wait'

describe('Test Ajax.sendRequest', function () {
  beforeEach(function () {
    jasmine.Ajax.install()
    addJasmineAjaxFormDataSupport()

    jasmine.Ajax.stubRequest(/goodRequest/).andReturn({
      status: 200,
      statusText: 'OK',
      response: 'CONTENT',
      responseText: 'CONTENT'
    })

    jasmine.Ajax.stubRequest(/resultJSON/).andReturn({
      status: 200,
      statusText: 'OK',
      response: '{"var1": "value1", "var2": "value2"}',
      responseText: '{"var1": "value1", "var2": "value2"}'
    })

    jasmine.Ajax.stubRequest(/badJSON/).andReturn({
      status: 200,
      statusText: 'OK',
      response: '{"var1":',
      responseText: '{"var1":'
    })

    jasmine.Ajax.stubRequest(/resultJavaScript/).andReturn({
      status: 200,
      statusText: 'OK',
      response: '<div id="subcontent">BEFORE</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script>',
      responseText: '<div id="subcontent">BEFORE</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script>'
    })

    jasmine.Ajax.stubRequest(/error404/).andReturn({
      status: 404,
      statusText: 'Not Found',
      response: 'Page not found !',
      responseText: 'Page not found !'
    })

    jasmine.Ajax.stubRequest(/failure/).andError()
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
    $(document).off('ec-crud-ajax-on-success')
    $(document).off('ec-crud-ajax-on-error')
    $(document).off('ec-crud-ajax-on-complete')
  })

  it('Send request', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')
    const eventSuccess = jasmine.createSpy('event-success')
    const eventError = jasmine.createSpy('event-error')
    const eventComplete = jasmine.createSpy('event-complete')

    $(document).on('ec-crud-ajax-on-success', function (event) {
      expect(event.detail.data).toEqual('CONTENT')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventSuccess()
    })
    $(document).on('ec-crud-ajax-on-error', function (event) {
      eventError()
    })
    $(document).on('ec-crud-ajax-on-complete', function (event) {
      expect(event.detail.statusText).toEqual('OK')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventComplete()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onComplete: function (statusText, response) {
        expect(statusText).toEqual('OK')
        expect(response).toBeInstanceOf(Response)
        callbackComplete()
      },
      onSuccess: function (data, response) {
        expect(data).toEqual('CONTENT')
        expect(response).toBeInstanceOf(Response)
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(() => {
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(callbackSuccess).toHaveBeenCalledBefore(callbackComplete)
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).not.toHaveBeenCalled()
    expect(eventSuccess).toHaveBeenCalledBefore(eventComplete)
    expect(eventError).not.toHaveBeenCalled()
    expect(eventComplete).toHaveBeenCalled()
  })

  it('Send bad request', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')
    const eventSuccess = jasmine.createSpy('event-success')
    const eventError = jasmine.createSpy('event-error')
    const eventComplete = jasmine.createSpy('event-complete')

    $(document).on('ec-crud-ajax-on-success', function (event) {
      eventSuccess()
    })
    $(document).on('ec-crud-ajax-on-error', function (event) {
      expect(event.detail.statusText).toEqual('Not Found')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventError()
    })
    $(document).on('ec-crud-ajax-on-complete', function (event) {
      expect(event.detail.statusText).toEqual('Not Found')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventComplete()
    })

    const promise = ajax.sendRequest({
      url: '/error404',
      onComplete: function (statusText, response) {
        expect(statusText).toEqual('Not Found')
        expect(response).toBeInstanceOf(Response)
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        expect(statusText).toEqual('Not Found')
        expect(response).toBeInstanceOf(Response)
        callbackError()
      }
    }).catch(() => {
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/error404')
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).toHaveBeenCalledBefore(callbackComplete)
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).not.toHaveBeenCalled()
    expect(eventSuccess).not.toHaveBeenCalled()
    expect(eventError).toHaveBeenCalledBefore(eventComplete)
    expect(eventComplete).toHaveBeenCalled()
  })

  it('Send bad request with successfulResponseRequired', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')
    const eventSuccess = jasmine.createSpy('event-success')
    const eventError = jasmine.createSpy('event-error')
    const eventComplete = jasmine.createSpy('event-complete')

    $(document).on('ec-crud-ajax-on-success', function (event) {
      eventSuccess()
    })
    $(document).on('ec-crud-ajax-on-error', function (event) {
      expect(event.detail.statusText).toEqual('Not Found')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventError()
    })
    $(document).on('ec-crud-ajax-on-complete', function (event) {
      expect(event.detail.statusText).toEqual('Not Found')
      expect(event.detail.response).toBeInstanceOf(Response)
      eventComplete()
    })

    const promise = ajax.sendRequest({
      url: '/error404',
      successfulResponseRequired: true,
      onComplete: function (statusText, response) {
        expect(statusText).toEqual('Not Found')
        expect(response).toBeInstanceOf(Response)
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        expect(statusText).toEqual('Not Found')
        expect(response).toBeInstanceOf(Response)
        callbackError()
      }
    }).catch(error => {
      expect(error).toBeInstanceOf(Error)
      expect(error.message).toEqual('The response is not successful: Not Found')
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeUndefined()
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/error404')
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).toHaveBeenCalledBefore(callbackComplete)
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).toHaveBeenCalled()
    expect(eventSuccess).not.toHaveBeenCalled()
    expect(eventError).toHaveBeenCalledBefore(eventComplete)
    expect(eventComplete).toHaveBeenCalled()
  })

  it('Send bad request with fetch error', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')
    const eventSuccess = jasmine.createSpy('event-success')
    const eventError = jasmine.createSpy('event-error')
    const eventComplete = jasmine.createSpy('event-complete')

    $(document).on('ec-crud-ajax-on-success', function (event) {
      eventSuccess()
    })
    $(document).on('ec-crud-ajax-on-error', function (event) {
      expect(event.detail.statusText).toMatch(/Error during query execution:.+failed/)
      expect(event.detail.response).toBeNull()
      eventError()
    })
    $(document).on('ec-crud-ajax-on-complete', function (event) {
      expect(event.detail.statusText).toMatch(/Error during query execution:.+failed/)
      expect(event.detail.response).toBeNull()
      eventComplete()
    })

    const promise = ajax.sendRequest({
      url: '/failure',
      onComplete: function (statusText, response) {
        expect(statusText).toMatch('failed')
        expect(response).toBeNull()
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        expect(statusText).toMatch(/Error during query execution:.+failed/)
        expect(response).toBeNull()
        callbackError()
      }
    }).catch(error => {
      expect(error).toMatch(/Error during query execution:.+failed/)
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeUndefined()
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/failure')
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).toHaveBeenCalledBefore(callbackComplete)
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).toHaveBeenCalled()
    expect(eventSuccess).not.toHaveBeenCalled()
    expect(eventError).toHaveBeenCalledBefore(eventComplete)
    expect(eventComplete).toHaveBeenCalled()
  })

  it('Send request with callback priorities', async function () {
    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')

    await ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: [
        function (data, response) {
          callbackSuccess1()
        },
        {
          priority: 99,
          callback: function (data, response) {
            callbackSuccess2()
          }
        }
      ]
    })

    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).toHaveBeenCalledBefore(callbackSuccess1)
  })

  it('Send request without URL', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')

    const promise = ajax.sendRequest({
      onComplete: function (statusText, response) {
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(error => {
      expect(error).toBeInstanceOf(TypeError)
      expect(error.message).toEqual('Value required: url')
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeUndefined()
    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).not.toHaveBeenCalled()
    expect(callbackCatch).toHaveBeenCalled()
  })

  it('Send request with string body', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      body: 'body-content',
      onComplete: function (statusText, response) {
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(() => {
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().params).toEqual('body-content')
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).not.toHaveBeenCalled()
  })

  it('Send request with FormData body', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')

    const formData = new FormData()
    formData.append('var1', 'My value 1')
    formData.append('var2[]', 'val2A')
    formData.append('var2[]', 'val2C')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      body: formData,
      onComplete: function (statusText, response) {
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(() => {
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2[]', 'val2A'], ['var2[]', 'val2C']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).not.toHaveBeenCalled()
  })

  it('Send request with object body', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      body: {
        var1: 'My value 1',
        'var2[]': ['val2A', 'val2C']
      },
      onComplete: function (statusText, response) {
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(() => {
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2[]', 'val2A'], ['var2[]', 'val2C']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
    expect(callbackCatch).not.toHaveBeenCalled()
  })

  it('Send request with bad body', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')
    const callbackCatch = jasmine.createSpy('catch')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      body: true,
      onComplete: function (statusText, response) {
        callbackComplete()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        callbackError()
      }
    }).catch(error => {
      expect(error).toBeInstanceOf(TypeError)
      expect(error.message).toEqual('Bad type for option "body"')
      callbackCatch()
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeUndefined()
    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).not.toHaveBeenCalled()
    expect(callbackCatch).toHaveBeenCalled()
  })

  it('Send request with query option', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest?var1=val1&var2[]=val2a&var3=val3',
      query: {
        var1: 'new1', // override
        'var2[]': ['new2a', 'new2b'], // override
        var4: 'new4'
      },
      cache: true
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toEqual('http://localhost:9876/goodRequest?var1=new1&var3=val3&var2%5B%5D=new2a&var2%5B%5D=new2b&var4=new4')
  })

  it('Send request with cache', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest',
      cache: true
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch(/goodRequest$/)
  })

  it('Send request with default cache', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest'
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch(/goodRequest\?_=\d+$/)
  })

  it('Send request without cache', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest',
      cache: false
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch(/goodRequest\?_=\d+$/)
  })

  it('Send request without cache but param is already used', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest?_=val',
      cache: false
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch(/goodRequest\?_=val$/)
  })

  it('Send request with relative URL', async function () {
    const promise = ajax.sendRequest({
      url: '/goodRequest',
      cache: true
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toEqual('http://localhost:9876/goodRequest')
  })

  it('Send request with absolute URL', async function () {
    const promise = ajax.sendRequest({
      url: 'http://test.demo/goodRequest',
      cache: true
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toEqual('http://test.demo/goodRequest')
  })

  it('Send request and update DOM with default mode', async function () {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content"></div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: {
        callback: function (data, response) {
          callbackSuccess($('#ajax-result').html())
        },
        priority: -99
      },
      update: '#ajax-result .content'
    })

    expect(callbackSuccess).toHaveBeenCalledWith('<div class="content">CONTENT</div>')
  })

  it('Send request and update DOM with "update" mode', async function () {
    await testUpdate('update', '<div class="content">CONTENT</div>')
  })

  it('Send request and update DOM with "before" mode', async function () {
    await testUpdate('before', 'CONTENT<div class="content">X</div>')
  })

  it('Send request and update DOM with "after" mode', async function () {
    await testUpdate('after', '<div class="content">X</div>CONTENT')
  })

  it('Send request and update DOM with "prepend" mode', async function () {
    await testUpdate('prepend', '<div class="content">CONTENTX</div>')
  })

  it('Send request and update DOM with "append" mode', async function () {
    await testUpdate('append', '<div class="content">XCONTENT</div>')
  })

  it('Send request and update DOM with bad mode', async function () {
    spyOn(window.console, 'error')
    await testUpdate('badMode', '<div class="content">X</div>')
    expect(window.console.error).toHaveBeenCalledWith('Bad updateMode: badMode')
  })

  it('Send request with method option', async function () {
    await ajax.sendRequest({
      url: '/goodRequest',
      method: 'GET'
    })

    expect(jasmine.Ajax.requests.mostRecent().method).toEqual('GET')
  })

  it('Send request with onBeforeSend option', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onBeforeSend: function (options) {
        expect(options).toBeInstanceOf(Object)
        expect(options.url).toEqual('/goodRequest')
        callbackBeforeSend()
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalledBefore(callbackSuccess)
  })

  it('Send request canceled by onBeforeSend option', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onBeforeSend: function (options) {
        expect(options).toBeInstanceOf(Object)
        expect(options.url).toEqual('/goodRequest')
        callbackBeforeSend()
        options.stop = true
      },
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeNull()
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalled()
  })

  it('Test ec-crud-ajax-before-send event', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    $(document).on('ec-crud-ajax-before-send', function (event) {
      expect(event.detail.options).toBeInstanceOf(Object)
      expect(event.detail.options.url).toEqual('/goodRequest')
      callbackBeforeSend()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalledBefore(callbackSuccess)

    $(document).off('ec-crud-ajax-before-send')
  })

  it('Send request canceled by ec-crud-ajax-before-send event', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    $(document).on('ec-crud-ajax-before-send', function (event) {
      expect(event.detail.options).toBeInstanceOf(Object)
      expect(event.detail.options.url).toEqual('/goodRequest')
      event.preventDefault()
      callbackBeforeSend()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeNull()
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalled()

    $(document).off('ec-crud-ajax-before-send')
  })

  it('Test ec-crud-ajax event', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeginning = jasmine.createSpy('beginning')

    $(document).on('ec-crud-ajax', function (event) {
      expect(event.detail.options).toBeInstanceOf(Object)
      expect(event.detail.options.url).toEqual('/goodRequest')
      callbackBeginning()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackBeginning).toHaveBeenCalled()

    $(document).off('ec-crud-ajax')
  })

  it('Send request canceled by ec-crud-ajax event', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeginning = jasmine.createSpy('beginning')

    $(document).on('ec-crud-ajax', function (event) {
      expect(event.detail.options).toBeInstanceOf(Object)
      expect(event.detail.options.url).toEqual('/goodRequest')
      event.preventDefault()
      callbackBeginning()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeNull()
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackBeginning).toHaveBeenCalled()

    $(document).off('ec-crud-ajax')
  })

  it('Send request with options changed by ec-crud-ajax event', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeginning = jasmine.createSpy('beginning')

    $(document).on('ec-crud-ajax', function (event) {
      expect(event.detail.options).toBeInstanceOf(Object)
      expect(event.detail.options.body).toBeUndefined()
      event.detail.options.body = 'BODY ADDED BY EVENT'
      callbackBeginning()
    })

    const promise = ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().params).toEqual('BODY ADDED BY EVENT')
    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackBeginning).toHaveBeenCalled()

    $(document).off('ec-crud-ajax')
  })

  it('Send request with text responseDataType', async function () {
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/goodRequest',
      responseDataType: 'text',
      onSuccess: function (data, response) {
        expect(data).toBeInstanceOf(String)
        expect(data).toEqual('CONTENT')
        expect(response).toBeInstanceOf(Response)
        callbackSuccess()
      }
    })

    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with json responseDataType', async function () {
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/resultJSON',
      responseDataType: 'json',
      onSuccess: function (data, response) {
        expect(data).not.toBeInstanceOf(String)
        expect(data).toEqual({ var1: 'value1', var2: 'value2' })
        expect(response).toBeInstanceOf(Response)
        callbackSuccess()
      }
    })

    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with json responseDataType and bad result', async function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackCatch = jasmine.createSpy('catch')

    await ajax.sendRequest({
      url: '/badJSON',
      responseDataType: 'json',
      onSuccess: function (data, response) {
        callbackSuccess()
      },
      onError: function (statusText, response) {
        expect(statusText).toMatch(/Error during fetching response body:.+JSON\.parse/)
        expect(response).toBeInstanceOf(Response)
        callbackError()
      }
    }).catch(error => {
      expect(error).toMatch(/Error during fetching response body:.+JSON\.parse/)
      callbackCatch()
    })

    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).toHaveBeenCalled()
    expect(callbackCatch).toHaveBeenCalled()
  })

  it('Send request with no responseDataType', async function () {
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/goodRequest',
      responseDataType: null,
      onSuccess: function (data, response) {
        expect(data).toBeNull()
        expect(response).toBeInstanceOf(Response)
        callbackSuccess()
      }
    })

    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with JS in response', async function () {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/resultJavaScript',
      onSuccess: {
        callback: function (data, response) {
          callbackSuccess($('#ajax-result').html())
        },
        priority: -99
      },
      update: '#ajax-result .content'
    })

    expect(callbackSuccess).toHaveBeenCalledWith('<div class="content"><div id="subcontent">AFTER</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script></div>')
  })

  async function testUpdate (updateMode, expectedContent) {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: {
        callback: function (data, response) {
          callbackSuccess($('#ajax-result').html())
        },
        priority: -99
      },
      update: '#ajax-result .content',
      updateMode: updateMode
    })

    expect(callbackSuccess).toHaveBeenCalledWith(expectedContent)
  }
})

describe('Test Ajax.click', function () {
  beforeEach(function () {
    jasmine.Ajax.install()
    addJasmineAjaxFormDataSupport()

    jasmine.Ajax.stubRequest(/goodRequest/).andReturn({
      status: 200,
      responseText: 'CONTENT'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with button', async function () {
    $('body').append('<button class="html-test" id="buttonToTest">Go !</button>')

    const callbackSuccess = jasmine.createSpy('success')

    const promise = ajax.click($('#buttonToTest'), {
      url: '/goodRequest',
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with button and data-*', async function () {
    $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-success="my_callback_on_success">Go !</button>')

    const callbackSuccess = jasmine.createSpy('success')

    callbackManager.registerCallback('my_callback_on_success', function (data, response) {
      callbackSuccess()
    })

    await ajax.click($('#buttonToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with button and data-* and options', async function () {
    $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>')

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, response) {
      callbackSuccess1()
    })

    await ajax.click($('#buttonToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, response) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (statusText, response) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with button', async function () {
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="buttonToTest" data-ec-crud-ajax-url="/goodRequest">Go !</a>')

    $('#buttonToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
  })

  it('Send auto-request with button canceled', async function () {
    $(document).on('ec-crud-ajax-click-auto-before', '#clickToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest">Go !</button>')

    $('#clickToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest')
  })

  it('Send auto-request canceled by onBeforeSend option', async function () {
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-before-send="my_callback_on_before_send">Go !</button>')

    callbackManager.registerCallback('my_callback_on_before_send', function (options) {
      options.stop = true
    })

    $('#clickToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest')
  })

  it('Send auto-request with button and error', async function () {
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="buttonToTest">Go !</a>')
    spyOn(window.console, 'error')

    $('#buttonToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()
    expect(window.console.error).toHaveBeenCalledWith(new TypeError('Value required: url'))
  })
})

describe('Test Ajax.link', function () {
  beforeEach(function () {
    jasmine.Ajax.install()
    addJasmineAjaxFormDataSupport()

    jasmine.Ajax.stubRequest(/goodRequest/).andReturn({
      status: 200,
      responseText: 'CONTENT'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with link', async function () {
    $('body').append('<a href="/goodRequest" class="html-test" id="linkToTest">Go !</a>')

    const callbackSuccess = jasmine.createSpy('success')

    const promise = ajax.link($('#linkToTest'), {
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with link and data-*', async function () {
    $('body').append('<a href="/goodRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success">Go !</a>')

    const callbackSuccess = jasmine.createSpy('success')

    callbackManager.registerCallback('my_callback_on_success', function (data, response) {
      callbackSuccess()
    })

    await ajax.link($('#linkToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with link and data-* and options', async function () {
    $('body').append('<a href="/badRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>')
    // href is overridden by url option

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, response) {
      callbackSuccess1()
    })

    await ajax.link($('#linkToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, response) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (statusText, response) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with link', async function () {
    $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>')

    $('#linkToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
  })

  it('Send auto-request with link canceled', async function () {
    $(document).on('ec-crud-ajax-link-auto-before', '#linkToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>')

    $('#linkToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-link-auto-before', '#linkToTest')
  })

  it('Send auto-request with link and error', async function () {
    $('body').append('<a class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>')
    spyOn(window.console, 'error')

    $('#linkToTest').click()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()
    expect(window.console.error).toHaveBeenCalledWith(new TypeError('Value required: url'))
  })
})

describe('Test Ajax.form', function () {
  beforeEach(function () {
    jasmine.Ajax.install()
    addJasmineAjaxFormDataSupport()

    jasmine.Ajax.stubRequest(/goodRequest/).andReturn({
      status: 200,
      responseText: 'CONTENT'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with form', async function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')

    const promise = ajax.sendForm($('#formToTest'), {
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })
    expect(promise).toBeInstanceOf(Promise)

    const response = await promise

    expect(response).toBeInstanceOf(Response)
    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2', 'My value 2']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with form with multi values', async function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest"><input type="text" name="var1" /><select name="var2[]" multiple><option value="val2A">val2A</option><option value="val2B">val2B</option><option value="val2C">val2C</option></select></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest option[value=val2A]').prop('selected', true)
    $('#formToTest option[value=val2C]').prop('selected', true)

    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendForm($('#formToTest'), {
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2[]', 'val2A'], ['var2[]', 'val2C']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with PUT form', async function () {
    $('body').append('<form action="/goodRequest" method="PUT" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')

    await ajax.sendForm($('#formToTest'), {
      onSuccess: function (data, response) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2', 'My value 2']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with form and data-*', async function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="my_callback_on_success"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')
    callbackManager.registerCallback('my_callback_on_success', function (data, response) {
      callbackSuccess()
    })

    await ajax.sendForm($('#formToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2', 'My value 2']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with form and data-* and options', async function () {
    $('body').append('<form action="/badRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    // action is overridden by url option
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, response) {
      callbackSuccess1()
    })

    await ajax.sendForm($('#formToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, response) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (statusText, response) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2', 'My value 2']]) // Parsed by addJasmineAjaxFormDataSupport
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with form', async function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    $('#formToTest').submit()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent().url).toMatch('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual([['var1', 'My value 1'], ['var2', 'My value 2']]) // Parsed by addJasmineAjaxFormDataSupport
  })

  it('Send auto-request with form canceled', async function () {
    $(document).on('ec-crud-ajax-form-auto-before', '#formToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    $('#formToTest').submit()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-form-auto-before', '#formToTest')
  })

  it('Send auto-request with form and error', async function () {
    $('body').append('<form  method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /></form>')
    spyOn(window.console, 'error')

    $('#formToTest').submit()

    await wait(() => {
      return false
    }, 500)

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()
    expect(window.console.error).toHaveBeenCalledWith(new TypeError('Value required: url'))
  })
})

describe('Test Ajax.updateDom', function () {
  beforeEach(function () {
    $('body').append('<div id="container" class="html-test"><div class="content">X</div></div>')
  })

  afterEach(function () {
    $('.html-test').remove()
    $(document).off('ec-crud-ajax-update-dom-before')
    $(document).off('ec-crud-ajax-update-dom-after')
    callbackManager.clear()
  })

  it('Update with "update" mode', function () {
    testUpdateDom('update', '<div class="content">OK</div>')
  })

  it('Update with "before" mode', function () {
    testUpdateDom('before', 'OK<div class="content">X</div>')
  })

  it('Update with "after" mode', function () {
    testUpdateDom('after', '<div class="content">X</div>OK')
  })

  it('Update with "prepend" mode', function () {
    testUpdateDom('prepend', '<div class="content">OKX</div>')
  })

  it('Update with "append" mode', function () {
    testUpdateDom('append', '<div class="content">XOK</div>')
  })

  it('Update with "update" mode and ec-crud-ajax-update-dom-before event - change updateMode', function () {
    $(document).on('ec-crud-ajax-update-dom-before', function (event) {
      event.updateMode = 'append'
    })

    testUpdateDom('update', '<div class="content">XOK</div>')
  })

  it('Update with "update" mode and ec-crud-ajax-update-dom-before event - change content', function () {
    $(document).on('ec-crud-ajax-update-dom-before', function (event) {
      event.content = 'NEW OK'
    })

    testUpdateDom('update', '<div class="content">NEW OK</div>')
  })

  it('Update with "update" mode and ec-crud-ajax-update-dom-before event - preventDefault', function () {
    $(document).on('ec-crud-ajax-update-dom-before', function (event) {
      event.preventDefault()
    })

    testUpdateDom('update', '<div class="content">X</div>')
  })

  it('Update with "update" mode and ec-crud-ajax-update-dom-after event', function () {
    $(document).on('ec-crud-ajax-update-dom-after', function (event) {
      $(event.element).find('.content').html('OK')
    })

    ajax.updateDom('#container .content', 'update', '<div><span class="content"></span></div>')
    expect($('#container').html()).toEqual('<div class="content"><div><span class="content">OK</span></div></div>')
  })

  it('Update with "update" mode and ec-crud-ajax-update-dom-after event - with scopes', function () {
    const callbackCalled1 = jasmine.createSpy('called1')
    const callbackCalled2 = jasmine.createSpy('called2')
    const callbackNotCalled = jasmine.createSpy('not-called')

    $(document).on('ec-crud-ajax-update-dom-before', function (event) {
      callbackCalled1()
    })
    $(document).on('ec-crud-ajax-update-dom-before', '#container', function (event) {
      callbackCalled2()
    })
    $(document).on('ec-crud-ajax-update-dom-before', '#id-does-not-exit', function (event) {
      callbackNotCalled()
    })

    testUpdateDom('update', '<div class="content">OK</div>')
    expect(callbackCalled1).toHaveBeenCalled()
    expect(callbackCalled2).toHaveBeenCalled()
    expect(callbackNotCalled).not.toHaveBeenCalled()
  })

  function testUpdateDom (updateMode, expected) {
    ajax.updateDom('#container .content', updateMode, 'OK')
    expect($('#container').html()).toEqual(expected)
  }

  it('Update with bad mode', function () {
    spyOn(window.console, 'error')
    ajax.updateDom('#container .content', 'badMode', 'OK')
    expect(window.console.error).toHaveBeenCalledWith('Bad updateMode: badMode')
    expect($('#container').html()).toEqual('<div class="content">X</div>')
  })
})

function addJasmineAjaxFormDataSupport () {
  jasmine.Ajax.addCustomParamParser({
    test: function (xhr) {
      return xhr.params instanceof FormData
    },
    parse: function (params) {
      const array = []
      params.forEach((value, key) => {
        array.push([key, value])
      })

      return array
    }
  })
}
