/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default function (stopCondition, timeout = 10000) {
  const dateTimeout = Date.now() + timeout

  return new Promise(resolve => {
    testCondition(stopCondition, dateTimeout, resolve)
  })
}

function testCondition (stopCondition, dateTimeout, resolve) {
  if (Date.now() > dateTimeout) {
    resolve('timeout')

    return
  }

  if (stopCondition()) {
    resolve('ok')

    return
  }

  setTimeout(() => {
    testCondition(stopCondition, dateTimeout, resolve)
  }, 1000)
}
