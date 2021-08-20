/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import runCallback from '@ecommit/crud-bundle/js/callback';
import * as callbackManager from '@ecommit/crud-bundle/js/callback-manager';

describe('Test callback', function () {
    it('Test callback with function', function () {
        const callback = jasmine.createSpy('callback');

        runCallback(function (arg) {
            callback(arg);
        }, 2);

        expect(callback).toHaveBeenCalledWith(2);
    });

    it('Test callback with string', function () {
        const myCallback = jasmine.createSpy('callback');
        callbackManager.registerCallback('my_callback', function (arg) {
            myCallback(arg);
        });

        runCallback('my_callback', 3);

        expect(myCallback).toHaveBeenCalledWith(3);
        callbackManager.clear();
    });

    it('Test callback with string - Callback not registred', function () {
        spyOn(window.console, 'error');
        runCallback('my_callback_never_registred', 3);
        expect(window.console.error).toHaveBeenCalledWith('Callback not found: my_callback_never_registred');
    });

    it('Test callback with sub array', function () {
        const callback1 = jasmine.createSpy('callback1');
        const callback2 = jasmine.createSpy('callback2');
        const callback3 = jasmine.createSpy('callback3');
        const callback4 = jasmine.createSpy('callback4');
        const callback5 = jasmine.createSpy('callback5');

        runCallback([
            function () {
                callback1();
            },
            [
                [
                    function () {
                        callback2();
                    },
                    function () {
                        callback3();
                    }
                ],
                function () {
                    callback4();
                }
            ],
            function () {
                callback5();
            }
        ]);

        expect(callback1).toHaveBeenCalledTimes(1);
        expect(callback2).toHaveBeenCalledTimes(1);
        expect(callback3).toHaveBeenCalledTimes(1);
        expect(callback4).toHaveBeenCalledTimes(1);
        expect(callback5).toHaveBeenCalledTimes(1);
    });

    it('Test callback with priorities', function () {
        const callback1 = jasmine.createSpy('callback1');
        const callback2 = jasmine.createSpy('callback2');
        const callback3 = jasmine.createSpy('callback3');
        const callback4 = jasmine.createSpy('callback4');
        const callback5 = jasmine.createSpy('callback5');

        callbackManager.registerCallback('my_callback3', function (arg) {
            callback3(arg);
        });
        callbackManager.registerCallback('my_callback4', function (arg) {
            callback4(arg);
        });
        callbackManager.registerCallback('my_callback5', function (arg) {
            callback5(arg);
        });

        runCallback([
            // Called third
            function (arg) {
                callback1(arg);
            },
            // Called first
            {
                priority: '99',
                callback: function (arg) {
                    callback2(arg);
                }
            },
            // Called second
            {
                priority: 10,
                callback: 'my_callback3'
            },
            // Called fourth
            {
                callback: 'my_callback4'
            },
            // Called in fifth
            'my_callback5'
        ], 'myValue');

        expect(callback1).toHaveBeenCalledTimes(1);
        expect(callback2).toHaveBeenCalledTimes(1);
        expect(callback3).toHaveBeenCalledTimes(1);
        expect(callback4).toHaveBeenCalledTimes(1);
        expect(callback5).toHaveBeenCalledTimes(1);

        expect(callback1).toHaveBeenCalledWith('myValue');
        expect(callback2).toHaveBeenCalledWith('myValue');

        expect(callback2).toHaveBeenCalledBefore(callback3);
        expect(callback3).toHaveBeenCalledBefore(callback1);
        expect(callback1).toHaveBeenCalledBefore(callback4);
        expect(callback4).toHaveBeenCalledBefore(callback5);

        callbackManager.clear();
    });

    it('Test callback with many arguments', function () {
        const callback = jasmine.createSpy('callback');

        runCallback(function (arg1, arg2) {
            callback(arg1, arg2);
        }, 2, 4);

        expect(callback).toHaveBeenCalledWith(2, 4);
    });
});
