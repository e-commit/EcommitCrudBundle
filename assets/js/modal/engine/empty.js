/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import runCallback from '../../callback'

export function openModal (options) {
  runCallback(options.onOpen, options.element)
}

export function closeModal (element) {
  runCallback(element)
}
