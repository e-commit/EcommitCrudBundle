/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as callbackManager from './callback-manager'

export default function (callbacks, ...args) {
  if (undefined === callbacks || callbacks === null) {
    return
  }

  if (typeof callbacks === 'string' || callbacks instanceof String || callbacks instanceof Function) {
    callbacks = [callbacks]
  }

  if (Array !== callbacks.constructor) {
    return
  }

  const newCallbacks = []
  callbacks.forEach((value) => {
    addCallbacksToStack(value, newCallbacks)
  })

  newCallbacks.sort(function (a, b) {
    if (parseInt(a.priority, 10) >= parseInt(b.priority, 10)) {
      return -1
    }

    return 1
  })

  newCallbacks.forEach((value) => {
    processCallback(value.callback, args)
  })
}

function addCallbacksToStack (value, stack) {
  if (typeof value === 'string' || value instanceof String || value instanceof Function) {
    stack.push({
      callback: value,
      priority: 0
    })
  } else if (Array === value.constructor) {
    value.forEach((subValue) => {
      addCallbacksToStack(subValue, stack)
    })
  } else if (undefined !== value.callback) {
    stack.push({
      callback: value.callback,
      priority: (value.priority !== undefined) ? value.priority : 0
    })
  }
}

function processCallback (subject, args) {
  if (subject instanceof Function) {
    subject(...args)

    return
  }

  if (typeof subject !== 'string' && !(subject instanceof String)) {
    return
  }

  subject = callbackManager.getRegistredCallback(subject)
  if (subject) {
    subject(...args)
  }
}
