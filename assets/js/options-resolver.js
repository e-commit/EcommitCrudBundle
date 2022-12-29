/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export function resolve (defaultOptions, options) {
  Object.keys(options).forEach(key => options[key] === undefined ? delete options[key] : {})

  return extend({}, defaultOptions, options)
}

export function getDataAttributes (element, prefix) {
  const prefixLength = prefix.length
  const attributes = {}

  element = getElement(element)
  if (!element) {
    return attributes
  }

  Object.entries(element.dataset).forEach((property) => {
    const index = property[0]
    const value = property[1]
    if (index.length > prefixLength && index.substr(0, prefixLength) === prefix) {
      let newIndex = index.substr(prefixLength)
      newIndex = newIndex.charAt(0).toLowerCase() + newIndex.slice(1)
      attributes[newIndex] = transformDataValue(value)
    }
  })

  return attributes
}

function transformDataValue (value) {
  if (value.length === 0) {
    return null
  }

  if (/^\d+$/.test(value)) {
    return parseInt(value, 10)
  } else if (/^true$/i.test(value)) {
    return true
  } else if (/^false$/i.test(value)) {
    return false
  } else if (/^[[{]/i.test(value)) {
    try {
      return JSON.parse(value)
    } catch (e) {
      return value
    }
  }

  return value
}

export function isNotBlank (value) {
  if (undefined === value || value === null || value.length === 0) {
    return false
  }

  return true
}

export function getElement (element) {
  if (element === null) {
    return null
  } else if (typeof element === 'string' || element instanceof String) {
    return document.querySelector(element)
  } else if (element instanceof Element) {
    return element
  } else if (typeof element === 'object' && element.jquery !== undefined) {
    const elementByJquery = element.get(0)
    if (elementByJquery instanceof Element) {
      return elementByJquery
    }
  }

  return null
}

export function extend () {
  for (let i = 1; i < arguments.length; i++) {
    for (const key in arguments[i]) {
      if (Object.prototype.hasOwnProperty.call(arguments[i], key)) {
        arguments[0][key] = arguments[i][key]
      }
    }
  }

  return arguments[0]
}
