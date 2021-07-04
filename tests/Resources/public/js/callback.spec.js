/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import runCallback from '@ecommit/crud-bundle/js/callback';

describe('Test callback', function () {
    it('Test callback with function', function () {
        const callback = jasmine.createSpy('callback');

        runCallback(function (arg) {
            callback(arg);
        }, 2);

        expect(callback).toHaveBeenCalledWith(2);
    });

    it('Test callback with string', function () {
        global.myCallback = jasmine.createSpy('callback');

        runCallback('function (arg) { myCallback(arg); }', 3);

        expect(global.myCallback).toHaveBeenCalledWith(3);
    });

    it('Test callback with function content with argument', function () {
        global.myCallback = jasmine.createSpy('callback');

        runCallback('/arg/myCallback(arg)', 5);

        expect(global.myCallback).toHaveBeenCalledWith(5);
    });

    it('Test callback with function content without argument', function () {
        global.myCallback = jasmine.createSpy('callback');

        runCallback('myCallback(5)', 4);

        expect(global.myCallback).toHaveBeenCalledWith(5);
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
        global.callback3 = jasmine.createSpy('callback3');
        global.callback4 = jasmine.createSpy('callback4');
        global.callback5 = jasmine.createSpy('callback5');

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
                callback: 'callback3()'
            },
            // Called fourth
            {
                callback: 'callback4()'
            },
            // Called in fifth
            'callback5()'
        ], 'myValue');

        expect(callback1).toHaveBeenCalledTimes(1);
        expect(callback2).toHaveBeenCalledTimes(1);
        expect(global.callback3).toHaveBeenCalledTimes(1);
        expect(global.callback4).toHaveBeenCalledTimes(1);
        expect(global.callback5).toHaveBeenCalledTimes(1);

        expect(callback1).toHaveBeenCalledWith('myValue');
        expect(callback2).toHaveBeenCalledWith('myValue');

        expect(callback2).toHaveBeenCalledBefore(global.callback3);
        expect(global.callback3).toHaveBeenCalledBefore(callback1);
        expect(callback1).toHaveBeenCalledBefore(global.callback4);
        expect(global.callback4).toHaveBeenCalledBefore(global.callback5);
    });
});
