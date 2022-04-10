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

describe('Test Ajax.sendRequest', function () {
  beforeEach(function () {
    jasmine.Ajax.install()

    jasmine.Ajax.stubRequest('/goodRequest').andReturn({
      status: 200,
      responseText: 'OK'
    })

    jasmine.Ajax.stubRequest('/resultJS').andReturn({
      status: 200,
      responseText: '<div id="subcontent">BEFORE</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script>'
    })

    jasmine.Ajax.stubRequest('/error404').andReturn({
      status: 404,
      responseText: 'Page not found !'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request', function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')

    ajax.sendRequest({
      url: '/goodRequest',
      onComplete: function (jqXHR, textStatus) {
        callbackComplete()
      },
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess(data)
      },
      onError: function (jqXHR, textStatus, errorThrown) {
        callbackError(jqXHR.responseText)
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(callbackSuccess).toHaveBeenCalledWith('OK')
    expect(callbackError).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send bad request', function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackError = jasmine.createSpy('error')
    const callbackComplete = jasmine.createSpy('complete')

    ajax.sendRequest({
      url: '/error404',
      onComplete: function (jqXHR, textStatus) {
        callbackComplete()
      },
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess(data)
      },
      onError: function (jqXHR, textStatus, errorThrown) {
        callbackError(jqXHR.responseText)
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/error404')
    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackError).toHaveBeenCalledWith('Page not found !')
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send request with callback priorities', function () {
    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')

    ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: [
        function (data, textStatus, jqXHR) {
          callbackSuccess1()
        },
        {
          priority: 99,
          callback: function (data, textStatus, jqXHR) {
            callbackSuccess2()
          }
        }
      ]
    })

    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).toHaveBeenCalledBefore(callbackSuccess1)
  })

  it('Send request without URL', function () {
    spyOn(window.console, 'error')
    ajax.sendRequest({})
    expect(window.console.error).toHaveBeenCalledWith('Value required: url')
  })

  it('Send request and update DOM with default mode', function () {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content"></div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: {
        callback: function (data, textStatus, jqXHR) {
          callbackSuccess($('#ajax-result').html())
        },
        priority: -99
      },
      update: '#ajax-result .content'
    })

    expect(callbackSuccess).toHaveBeenCalledWith('<div class="content">OK</div>')
  })

  it('Send request and update DOM with "update" mode', function () {
    testUpdate('update', '<div class="content">OK</div>')
  })

  it('Send request and update DOM with "before" mode', function () {
    testUpdate('before', 'OK<div class="content">X</div>')
  })

  it('Send request and update DOM with "after" mode', function () {
    testUpdate('after', '<div class="content">X</div>OK')
  })

  it('Send request and update DOM with "prepend" mode', function () {
    testUpdate('prepend', '<div class="content">OKX</div>')
  })

  it('Send request and update DOM with "append" mode', function () {
    testUpdate('append', '<div class="content">XOK</div>')
  })

  it('Send request and update DOM with bad mode', function () {
    spyOn(window.console, 'error')
    testUpdate('badMode', '<div class="content">X</div>')
    expect(window.console.error).toHaveBeenCalledWith('Bad updateMode: badMode')
  })

  it('Send request with method option', function () {
    ajax.sendRequest({
      url: '/goodRequest',
      method: 'GET'
    })

    expect(jasmine.Ajax.requests.mostRecent().method).toEqual('GET')
  })

  it('Send request with onBeforeSend option', function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    ajax.sendRequest({
      url: '/goodRequest',
      onBeforeSend: function (options) {
        callbackBeforeSend()
      },
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(callbackSuccess).toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalledBefore(callbackSuccess)
  })

  it('Send request canceled by onBeforeSend option', function () {
    const callbackSuccess = jasmine.createSpy('success')
    const callbackBeforeSend = jasmine.createSpy('beforeSend')

    ajax.sendRequest({
      url: '/goodRequest',
      onBeforeSend: function (options) {
        callbackBeforeSend()
        options.stop = true
      },
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(callbackSuccess).not.toHaveBeenCalled()
    expect(callbackBeforeSend).toHaveBeenCalled()
  })

  it('Send request with data option', function () {
    ajax.sendRequest({
      url: '/goodRequest',
      data: {
        var1: 'value1',
        var2: 'value2'
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['value1'], var2: ['value2']
    })
  })

  it('Send request with JS in response', function () {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    ajax.sendRequest({
      url: '/resultJS',
      onSuccess: {
        callback: function (data, textStatus, jqXHR) {
          callbackSuccess($('#ajax-result').html())
        },
        priority: -99
      },
      update: '#ajax-result .content'
    })

    expect(callbackSuccess).toHaveBeenCalledWith('<div class="content"><div id="subcontent">AFTER</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script></div>')
  })

  function testUpdate (updateMode, expectedContent) {
    $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>')
    const callbackSuccess = jasmine.createSpy('success')

    ajax.sendRequest({
      url: '/goodRequest',
      onSuccess: {
        callback: function (data, textStatus, jqXHR) {
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

    jasmine.Ajax.stubRequest('/goodRequest').andReturn({
      status: 200,
      responseText: 'OK'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with button', function () {
    $('body').append('<button class="html-test" id="buttonToTest">Go !</button>')

    const callbackSuccess = jasmine.createSpy('success')

    ajax.click($('#buttonToTest'), {
      url: '/goodRequest',
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with button and data-*', function () {
    $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-success="my_callback_on_success">Go !</button>')

    const callbackSuccess = jasmine.createSpy('success')

    callbackManager.registerCallback('my_callback_on_success', function (data, textStatus, jqXHR) {
      callbackSuccess()
    })

    ajax.click($('#buttonToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with button and data-* and options', function () {
    $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>')

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, textStatus, jqXHR) {
      callbackSuccess1()
    })

    ajax.click($('#buttonToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, textStatus, jqXHR) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (jqXHR, textStatus) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with button', function () {
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="buttonToTest" data-ec-crud-ajax-url="/goodRequest">Go !</a>')

    $('#buttonToTest').click()

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
  })

  it('Send auto-request with button canceled', function () {
    $(document).on('ec-crud-ajax-click-auto-before', '#clickToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest">Go !</button>')

    $('#clickToTest').click()

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest')
  })

  it('Send auto-request canceled by onBeforeSend option', function () {
    $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-before-send="my_callback_on_before_send">Go !</button>')

    callbackManager.registerCallback('my_callback_on_before_send', function (options) {
      options.stop = true
    })

    $('#clickToTest').click()

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest')
  })
})

describe('Test Ajax.link', function () {
  beforeEach(function () {
    jasmine.Ajax.install()

    jasmine.Ajax.stubRequest('/goodRequest').andReturn({
      status: 200,
      responseText: 'OK'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with link', function () {
    $('body').append('<a href="/goodRequest" class="html-test" id="linkToTest">Go !</a>')

    const callbackSuccess = jasmine.createSpy('success')

    ajax.link($('#linkToTest'), {
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with link and data-*', function () {
    $('body').append('<a href="/goodRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success">Go !</a>')

    const callbackSuccess = jasmine.createSpy('success')

    callbackManager.registerCallback('my_callback_on_success', function (data, textStatus, jqXHR) {
      callbackSuccess()
    })

    ajax.link($('#linkToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with link and data-* and options', function () {
    $('body').append('<a href="/badRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>')
    // href is overridden by url option

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, textStatus, jqXHR) {
      callbackSuccess1()
    })

    ajax.link($('#linkToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, textStatus, jqXHR) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (jqXHR, textStatus) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with link', function () {
    $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>')

    $('#linkToTest').click()

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
  })

  it('Send auto-request with link canceled', function () {
    $(document).on('ec-crud-ajax-link-auto-before', '#linkToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>')

    $('#linkToTest').click()

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-link-auto-before', '#linkToTest')
  })
})

describe('Test Ajax.form', function () {
  beforeEach(function () {
    jasmine.Ajax.install()

    jasmine.Ajax.stubRequest('/goodRequest').andReturn({
      status: 200,
      responseText: 'OK'
    })
  })

  afterEach(function () {
    jasmine.Ajax.uninstall()
    $('.html-test').remove()
    callbackManager.clear()
  })

  it('Send request with form', function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')

    ajax.sendForm($('#formToTest'), {
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['My value 1'], var2: ['My value 2']
    })
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with PUT form', function () {
    $('body').append('<form action="/goodRequest" method="PUT" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')

    ajax.sendForm($('#formToTest'), {
      onSuccess: function (data, textStatus, jqXHR) {
        callbackSuccess()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['My value 1'], var2: ['My value 2']
    })
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with form and data-*', function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="my_callback_on_success"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess = jasmine.createSpy('success')
    callbackManager.registerCallback('my_callback_on_success', function (data, textStatus, jqXHR) {
      callbackSuccess()
    })

    ajax.sendForm($('#formToTest'))

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['My value 1'], var2: ['My value 2']
    })
    expect(callbackSuccess).toHaveBeenCalled()
  })

  it('Send request with form and data-* and options', function () {
    $('body').append('<form action="/badRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="my_callback_on_success_1" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    // action is overridden by url option
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    const callbackSuccess1 = jasmine.createSpy('success1')
    const callbackSuccess2 = jasmine.createSpy('success2')
    const callbackComplete = jasmine.createSpy('complete')

    callbackManager.registerCallback('my_callback_on_success_1', function (data, textStatus, jqXHR) {
      callbackSuccess1()
    })

    ajax.sendForm($('#formToTest'), {
      url: '/badRequest', // overridden by data-ec-crud-ajax-url
      method: 'GET', // overridden by data-ec-crud-ajax-method
      onSuccess: function (data, textStatus, jqXHR) { // overridden by data-ec-crud-ajax-on-success
        callbackSuccess2()
      },
      onComplete: function (jqXHR, textStatus) {
        callbackComplete()
      }
    })

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['My value 1'],
      var2: ['My value 2']
    })
    expect(callbackSuccess1).toHaveBeenCalled()
    expect(callbackSuccess2).not.toHaveBeenCalled()
    expect(callbackComplete).toHaveBeenCalled()
  })

  it('Send auto-request with form', function () {
    $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    $('#formToTest').submit()

    expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest')
    expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST')
    expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
      var1: ['My value 1'],
      var2: ['My value 2']
    })
  })

  it('Send auto-request with form canceled', function () {
    $(document).on('ec-crud-ajax-form-auto-before', '#formToTest', function (event) {
      event.preventDefault()
    })
    $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>')
    $('#formToTest input[name=var1]').val('My value 1')
    $('#formToTest input[name=var2]').val('My value 2')

    $('#formToTest').submit()

    expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined()

    $(document).off('ec-crud-ajax-form-auto-before', '#formToTest')
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
