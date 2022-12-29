import '@ecommit/crud-bundle/js/crud';
import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';
var modalEngine = require('@ecommit/crud-bundle/js/modal/engine/empty');

modalManager.defineEngine(modalEngine);
