/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';
import runCallback from '../../callback';

export function openModal (options) {
    // Suppression des événements
    $(options.element).off('shown.bs.modal');
    $(options.element).off('hide.bs.modal');

    $(options.element).on('shown.bs.modal', function (e) {
        runCallback(options.onOpen, $(options.element));
    });

    $(options.element).on('hide.bs.modal', function (e) {
        runCallback(options.onClose, $(options.element));
    });

    $(options.element).modal({
        show: true,
        focus: true
    });
}

export function closeModal (element) {
    $(element).modal('hide');
}
