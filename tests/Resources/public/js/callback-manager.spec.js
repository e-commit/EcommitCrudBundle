/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as callbackManager from '@ecommit/crud-bundle/js/callback-manager';

describe('Test callback manager', function () {
    afterEach(function () {
        callbackManager.clear();
    });

    it('Test registerCallback with invalid name', function () {
        spyOn(window.console, 'error');
        callbackManager.registerCallback(function () {}, function () {});
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test registerCallback with null name', function () {
        spyOn(window.console, 'error');
        callbackManager.registerCallback(null, function () {});
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test registerCallback with invalid callback', function () {
        spyOn(window.console, 'error');
        callbackManager.registerCallback('my_callback', 'callback');
        expect(window.console.error).toHaveBeenCalledWith('Invalid callback my_callback');
    });

    it('Test registerCallback with null callback', function () {
        spyOn(window.console, 'error');
        callbackManager.registerCallback('my_callback', null);
        expect(window.console.error).toHaveBeenCalledWith('Invalid callback my_callback');
    });

    it('Test callbackIsRegistred with invalid name', function () {
        spyOn(window.console, 'error');
        callbackManager.callbackIsRegistred(function () {});
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test callbackIsRegistred with null name', function () {
        spyOn(window.console, 'error');
        callbackManager.callbackIsRegistred(null);
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test callbackIsRegistred with found callback', function () {
        callbackManager.registerCallback('my_callback', function () {});
        expect(callbackManager.callbackIsRegistred('my_callback')).toBeTrue();
    });

    it('Test callbackIsRegistred with not found callback', function () {
        expect(callbackManager.callbackIsRegistred('my_callback')).toBeFalse();
    });

    it('Test getRegistredCallback with invalid name', function () {
        spyOn(window.console, 'error');
        callbackManager.getRegistredCallback(function () {});
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test getRegistredCallback with null name', function () {
        spyOn(window.console, 'error');
        callbackManager.getRegistredCallback(null);
        expect(window.console.error).toHaveBeenCalledWith('Bad name');
    });

    it('Test getRegistredCallback with found callback', function () {
        callbackManager.registerCallback('my_callback', function () {});
        const callback = callbackManager.getRegistredCallback('my_callback');
        expect(callback).toBeInstanceOf(Function);
    });

    it('Test getRegistredCallback with not found callback', function () {
        spyOn(window.console, 'error');
        const callback = callbackManager.getRegistredCallback('my_callback');
        expect(window.console.error).toHaveBeenCalledWith('Callback not found: my_callback');
        expect(callback).toBeNull();
    });

    it('Test clear', function () {
        callbackManager.registerCallback('my_callback', function () {});
        expect(callbackManager.getRegistredCallback('my_callback')).toBeInstanceOf(Function);

        callbackManager.clear();

        spyOn(window.console, 'error');
        const callback = callbackManager.getRegistredCallback('my_callback');
        expect(window.console.error).toHaveBeenCalledWith('Callback not found: my_callback');
        expect(callback).toBeNull();
    });
});
