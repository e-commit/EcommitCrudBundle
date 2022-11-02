/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Use fetch with jasmine-ajax
window.fetch = undefined
require('whatwg-fetch')

const testsContext = require.context('.', true, /\.spec\.js$/)
testsContext.keys().forEach(testsContext)
