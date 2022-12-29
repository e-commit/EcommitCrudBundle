/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as optionsRevolser from '@ecommit/crud-bundle/js/options-resolver'
import $ from 'jquery'

describe('Test options-resolver.resolve', function () {
  const defaultOptions = {
    var1: 'hello',
    var2: null,
    var3: true,
    var4: 1,
    var5: 'world',
    var6: null
  }

  it('Empty options', function () {
    expect(optionsRevolser.resolve(defaultOptions, {})).toEqual(defaultOptions)
  })

  it('Options with values', function () {
    const options = {
      var3: null,
      var5: 'world',
      var6: 'hello',
      var7: 'extra',
      var8: null
    }

    const expected = {
      var1: 'hello',
      var2: null,
      var3: null,
      var4: 1,
      var5: 'world',
      var6: 'hello',
      var7: 'extra',
      var8: null
    }

    expect(optionsRevolser.resolve(defaultOptions, options)).toEqual(expected)
  })
})

describe('Test options-resolver.getDataAttributes', function () {
  afterEach(function () {
    $('.html-test').remove()
  })

  it('Element doesn\'t exist', function () {
    expect(optionsRevolser.getDataAttributes('#badId', 'myPrefix')).toEqual({})
  })

  it('Element exists', function () {
    $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1" data-badprefix="1" data-my-prefix-var2="value2"></div>')

    const expected = {
      var1: 'value1',
      var2: 'value2'
    }

    expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual(expected)
  })

  it('Element without attribute', function () {
    $('body').append('<div id="myDiv" class="html-test"></div>')

    expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual({})
  })

  it('Element with different data types', function () {
    $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1" data-badprefix="1" data-my-prefix-var2="16" data-my-prefix-var3="falSe" data-my-prefix-var4="[8]" data-my-prefix-var5="{&quot;result&quot;:true, &quot;count&quot;:100}" data-my-prefix-var6="trUe" data-my-prefix-var7="[a" data-my-prefix-var8=""></div>')

    const expected = {
      var1: 'value1',
      var2: 16,
      var3: false,
      var4: [8],
      var5: {
        result: true,
        count: 100
      },
      var6: true,
      var7: '[a',
      var8: null
    }

    expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual(expected)
  })

  it('Element with Element', function () {
    $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1"></div>')
    const element = document.querySelector('#myDiv')

    expect(optionsRevolser.getDataAttributes(element, 'myPrefix')).toEqual({ var1: 'value1' })
  })

  it('Element with jQuery', function () {
    $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1"></div>')
    const element = $('#myDiv')

    expect(optionsRevolser.getDataAttributes(element, 'myPrefix')).toEqual({ var1: 'value1' })
  })
})

describe('Test options-resolver.isNotBlank', function () {
  it('Undefined is blank', function () {
    expect(optionsRevolser.isNotBlank(undefined)).toBe(false)
  })

  it('Null is blank', function () {
    expect(optionsRevolser.isNotBlank(null)).toBe(false)
  })

  it('Empty string is blank', function () {
    expect(optionsRevolser.isNotBlank('')).toBe(false)
  })

  it('String is not blank', function () {
    expect(optionsRevolser.isNotBlank('string')).toBe(true)
  })

  it('Int is not blank', function () {
    expect(optionsRevolser.isNotBlank(8)).toBe(true)
  })

  it('Empty array is blank', function () {
    expect(optionsRevolser.isNotBlank([])).toBe(false)
  })

  it('Array is not blank', function () {
    expect(optionsRevolser.isNotBlank(['val'])).toBe(true)
  })
})

describe('Test options-resolver.getElement', function () {
  beforeEach(function () {
    $('body').append('<div id="container" class="html-test"><div id="myDiv1" class="myDiv"></div><div id="myDiv2" class="myDiv"></div></div>')
  })

  afterEach(function () {
    $('.html-test').remove()
  })

  it('Test with null', function () {
    const result = optionsRevolser.getElement(null)

    expect(result).toBeNull()
  })

  it('Test with string - found', function () {
    const result = optionsRevolser.getElement('#myDiv1')

    expect(result).toBeInstanceOf(Element)
    expect(result.getAttribute('id')).toEqual('myDiv1')
  })

  it('Test with string - found (multiple)', function () {
    const result = optionsRevolser.getElement('.myDiv')

    expect(result).toBeInstanceOf(Element)
    expect(result.getAttribute('id')).toEqual('myDiv1')
  })

  it('Test with string - not found', function () {
    const result = optionsRevolser.getElement('#myDiv3')

    expect(result).toBeNull()
  })

  it('Test with element', function () {
    const element = document.querySelector('#myDiv1')
    const result = optionsRevolser.getElement(element)

    expect(result).toBeInstanceOf(Element)
    expect(result.getAttribute('id')).toEqual('myDiv1')
    expect(result).toBe(element)
  })

  // If "jquery" dependency deleted in the future, the "Test with fake jQuery" test must be kept (replacement test)
  it('Test with jQuery', function () {
    const result = optionsRevolser.getElement($('#myDiv1'))

    expect(result).toBeInstanceOf(Element)
    expect(result.getAttribute('id')).toEqual('myDiv1')
  })

  // If "jquery" dependency deleted in the future, the "Test with fake empty jQuery" test must be kept (replacement test)
  it('Test with empty jQuery', function () {
    const result = optionsRevolser.getElement($('#notFound'))

    expect(result).toBeNull()
  })

  it('Test with fake jQuery', function () {
    const object = {
      get: function (index) {
        if (index === 0) {
          return document.querySelector('#myDiv1')
        }

        return undefined
      },
      jquery: 'fake'
    }
    const result = optionsRevolser.getElement(object)

    expect(result).toBeInstanceOf(Element)
    expect(result.getAttribute('id')).toEqual('myDiv1')
  })

  it('Test with fake empty jQuery', function () {
    const object = {
      get: function (index) {
        return undefined
      },
      jquery: 'fake'
    }
    const result = optionsRevolser.getElement(object)

    expect(result).toBeNull()
  })

  it('Test with other type', function () {
    const result = optionsRevolser.getElement({})

    expect(result).toBeNull()
  })

  it('Test with undefined', function () {
    const result = optionsRevolser.getElement(undefined)

    expect(result).toBeNull()
  })
})
