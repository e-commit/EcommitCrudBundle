/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import runCallback from '@ecommit/crud-bundle/js/callback'
import $ from 'jquery'

export function openModal (options) {
  runCallback(options.onOpen, $(options.element))

  $(document).on('DOMNodeRemoved', options.element + ' .content', function (event) {
    runCallback(options.onClose, $(options.element))
  })
}

export function closeModal (element) {
  $(element + ' .content').remove()
  $(document).off('remove', element + ' .content')
}
