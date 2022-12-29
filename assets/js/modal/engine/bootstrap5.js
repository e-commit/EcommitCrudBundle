/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Modal } from 'bootstrap'
import runCallback from '../../callback'
import { getElement } from '../../options-resolver'

export function openModal (options) {
  const element = getElement(options.element)

  element.addEventListener('shown.bs.modal', e => {
    runCallback(options.onOpen, element)
  }, { once: true })

  element.addEventListener('hide.bs.modal', e => {
    runCallback(options.onClose, element)
  }, { once: true })

  const modal = new Modal(element, {
    focus: true
  })
  modal.show()
}

export function closeModal (element) {
  const modal = Modal.getInstance(getElement(element))
  modal.hide()
}
