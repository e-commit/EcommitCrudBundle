/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as optionsRevolser from '@ecommit/crud-bundle/js/options-resolver';
import $ from 'jquery';

describe('Test options-resolver.resolve', function () {
    const defaultOptions = {
        var1: 'hello',
        var2: null,
        var3: true,
        var4: 1,
        var5: 'world',
        var6: null
    };

    it('Empty options', function () {
        expect(optionsRevolser.resolve(defaultOptions, {})).toEqual(defaultOptions);
    });

    it('Options with values', function () {
        const options = {
            var3: null,
            var5: 'world',
            var6: 'hello',
            var7: 'extra',
            var8: null
        };

        const expected = {
            var1: 'hello',
            var2: null,
            var3: null,
            var4: 1,
            var5: 'world',
            var6: 'hello',
            var7: 'extra',
            var8: null
        }

        expect(optionsRevolser.resolve(defaultOptions, options)).toEqual(expected);
    });
});

describe('Test options-resolver.getDataAttributes', function () {
    afterEach(function () {
        $('.html-test').remove();
    });

    it('Element doesn\'t exist', function () {
        expect(optionsRevolser.getDataAttributes('#badId', 'myPrefix')).toEqual({});
    });

    it('Element exists', function () {
        $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1" data-badprefix="1" data-my-prefix-var2="value2"></div>');

        const expected = {
            var1: 'value1',
            var2: 'value2'
        };

        expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual(expected);
    });

    it('Element without attribute', function () {
        $('body').append('<div id="myDiv" class="html-test"></div>');

        expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual({});
    });

    it('Element with different data types', function () {
        $('body').append('<div id="myDiv" class="html-test" data-my-prefix-var1="value1" data-badprefix="1" data-my-prefix-var2="16" data-my-prefix-var3="false" data-my-prefix-var4="[8]" data-my-prefix-var5="{&quot;result&quot;:true, &quot;count&quot;:100}"></div>');

        const expected = {
            var1: 'value1',
            var2: 16,
            var3: false,
            var4: [8],
            var5: {
                result: true,
                count: 100
            }
        };

        expect(optionsRevolser.getDataAttributes('#myDiv', 'myPrefix')).toEqual(expected);
    });
});

describe('Test options-resolver.isNotBlank', function () {
    it('Undefined is blank', function () {
        expect(optionsRevolser.isNotBlank(undefined)).toBe(false);
    });

    it('Null is blank', function () {
        expect(optionsRevolser.isNotBlank(null)).toBe(false);
    });

    it('Empty string is blank', function () {
        expect(optionsRevolser.isNotBlank('')).toBe(false);
    });

    it('String is not blank', function () {
        expect(optionsRevolser.isNotBlank('string')).toBe(true);
    });

    it('Int is not blank', function () {
        expect(optionsRevolser.isNotBlank(8)).toBe(true);
    });

    it('Empty array is blank', function () {
        expect(optionsRevolser.isNotBlank([])).toBe(false);
    });

    it('Array is not blank', function () {
        expect(optionsRevolser.isNotBlank(['val'])).toBe(true);
    });
});
