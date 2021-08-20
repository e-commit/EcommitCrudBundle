/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as modalManager from '@ecommit/crud-bundle/js/modal/modal-manager';
import $ from 'jquery';
const testEngine = require('./engine/test');
const bootstrap3Engine = require('@ecommit/crud-bundle/js/modal/engine/bootstrap3');

it('Get engine when not defined', function () {
    modalManager.defineEngine(null);

    spyOn(window.console, 'error');

    const engine = modalManager.getEngine();
    expect(window.console.error).toHaveBeenCalledWith('Engine not defined');

    expect(engine).toBeUndefined();
});

describe('Test Modal-manager with spy engine', function () {
    beforeEach(function () {
        this.spyEngine = {
            openModal: function (options) {
            },
            closeModal: function (element) {
            }
        }
        spyOn(this.spyEngine, 'openModal');
        spyOn(this.spyEngine, 'closeModal');

        modalManager.defineEngine(this.spyEngine);

        jasmine.Ajax.install();
        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });
    });

    afterEach(function () {
        $('.html-test').remove();
        jasmine.Ajax.uninstall();
    });

    it('Test openModal', function () {
        modalManager.openModal({
            element: '#myId'
        });

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
    });

    it('Test openModal without element option', function () {
        spyOn(window.console, 'error');
        modalManager.openModal({});
        expect(window.console.error).toHaveBeenCalledWith('Value required: element');
    });

    it('Test closeModal', function () {
        modalManager.closeModal('#myId');

        expect(this.spyEngine.openModal).not.toHaveBeenCalled();
        expect(this.spyEngine.closeModal).toHaveBeenCalled();
    });

    it('Test auto openModal', function () {
        $('body').append('<a href="#" class="html-test ec-crud-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
    });

    it('Test auto openModal canceled', function () {
        $(document).on('ec-crud-modal-auto-before', '#linkToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<a href="#" class="html-test ec-crud-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).not.toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();

        $(document).off('ec-crud-modal-auto-before', '#linkToTest');
    });

    it('Test auto openRemoteModal - link', function () {
        $('body').append('<a href="#" class="html-test ec-crud-remote-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content" data-ec-crud-modal-url="/goodRequest">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
    });

    it('Test auto openRemoteModal - link with href', function () {
        $('body').append('<a href="/goodRequest" class="html-test ec-crud-remote-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
    });

    it('Test auto openRemoteModal - link - prioriry url attr', function () {
        $('body').append('<a href="/badRequest" class="html-test ec-crud-remote-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content" data-ec-crud-modal-url="/goodRequest">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
    });

    it('Test auto openRemoteModal - link - canceled', function () {
        $(document).on('ec-crud-remote-modal-auto-before', '#linkToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<a href="#" class="html-test ec-crud-remote-modal-auto" id="linkToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content" data-ec-crud-modal-url="/goodRequest">Go !</a>');

        $('#linkToTest').click();

        expect(this.spyEngine.openModal).not.toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-remote-modal-auto-before', '#linkToTest');
    });

    it('Test auto openRemoteModal - button', function () {
        $('body').append('<button class="html-test ec-crud-remote-modal-auto" id="buttonToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content" data-ec-crud-modal-url="/goodRequest">Go !</button>');

        $('#buttonToTest').click();

        expect(this.spyEngine.openModal).toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
    });

    it('Test auto openRemoteModal - button - canceled', function () {
        $(document).on('ec-crud-remote-modal-auto-before', '#buttonToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<button class="html-test ec-crud-remote-modal-auto" id="buttonToTest" data-ec-crud-modal-element="#myId" data-ec-crud-modal-element-content="#myId .content" data-ec-crud-modal-url="/goodRequest">Go !</button>');

        $('#buttonToTest').click();

        expect(this.spyEngine.openModal).not.toHaveBeenCalled();
        expect(this.spyEngine.closeModal).not.toHaveBeenCalled();
        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-remote-modal-auto-before', '#buttonToTest');
    });
});

describe('Test Modal-manager with test engine', function () {
    beforeEach(function () {
        modalManager.defineEngine(testEngine);
        $('body').append('<div id="test-modal"><div class="content"></div></div>');

        jasmine.Ajax.install();
        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });
        jasmine.Ajax.stubRequest('/error404').andReturn({
            status: 404,
            responseText: 'Page not found !'
        });
    });

    afterEach(function () {
        $('#test-modal').remove();
        jasmine.Ajax.uninstall();
    });

    describe('Test Modal-manager.defineEngine/getEngine', function () {
        it('getEngine is testEngine', function () {
            expect(modalManager.getEngine()).toEqual(testEngine);
        });

        it('Define bootstrap3 engine', function () {
            modalManager.defineEngine(bootstrap3Engine);
            expect(modalManager.getEngine()).toEqual(bootstrap3Engine);
        });

        it('Test openModal with onOpen and onClose options', function () {
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');

            modalManager.openModal({
                element: '#test-modal',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                }
            });

            expect(callbackOpen).toHaveBeenCalledWith($('#test-modal'));
            expect(callbackClose).not.toHaveBeenCalled();

            modalManager.closeModal('#test-modal');

            expect(callbackOpen).toHaveBeenCalledTimes(1);
            expect(callbackClose).toHaveBeenCalledWith($('#test-modal'));
        });
    });

    describe('Test Modal-manager.openRemoteModal', function () {
        it('Test openRemoteModal', function () {
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');

            modalManager.openRemoteModal({
                url: '/goodRequest',
                element: '#test-modal',
                elementContent: '#test-modal .content',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                }
            });

            expect(callbackOpen).toHaveBeenCalledWith($('#test-modal'));
            expect(callbackClose).not.toHaveBeenCalled();
            expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
            expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
            expect($('#test-modal .content').html()).toBe('OK');
        });

        it('Test openRemoteModal without option', function () {
            spyOn(window.console, 'error');
            modalManager.openRemoteModal({});
            expect(window.console.error).toHaveBeenCalledWith('Value required: url');
            expect(window.console.error).toHaveBeenCalledWith('Value required: element');
            expect(window.console.error).toHaveBeenCalledWith('Value required: elementContent');
        });

        it('Test openRemoteModal without element option', function () {
            spyOn(window.console, 'error');
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');

            modalManager.openRemoteModal({
                url: '/goodRequest',
                elementContent: '#test-modal .content',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                }
            });

            expect(window.console.error).toHaveBeenCalledWith('Value required: element');
            expect(callbackOpen).not.toHaveBeenCalled();
            expect(callbackClose).not.toHaveBeenCalled();
            expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();
        });

        it('Test openRemoteModal with ajaxOptions.onSuccess', function () {
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');
            const callbackSuccess1 = jasmine.createSpy('success1');
            const callbackSuccess2 = jasmine.createSpy('success2');

            modalManager.openRemoteModal({
                url: '/goodRequest',
                element: '#test-modal',
                elementContent: '#test-modal .content',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                },
                ajaxOptions: {
                    onSuccess: [
                        {
                            priority: 6,
                            callback: function (data, textStatus, jqXHR) {
                                callbackSuccess1();
                            }
                        },
                        {
                            priority: -2,
                            callback: function (data, textStatus, jqXHR) {
                                callbackSuccess2();
                            }
                        }
                    ]
                }
            });

            expect(callbackOpen).toHaveBeenCalledWith($('#test-modal'));
            expect(callbackSuccess1).toHaveBeenCalledBefore(callbackOpen);
            expect(callbackOpen).toHaveBeenCalledBefore(callbackSuccess2);
            expect(callbackClose).not.toHaveBeenCalled();
            expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
            expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
            expect($('#test-modal .content').html()).toBe('OK');
        });

        it('Test openRemoteModal with method option', function () {
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');

            modalManager.openRemoteModal({
                url: '/goodRequest',
                element: '#test-modal',
                elementContent: '#test-modal .content',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                },
                method: 'PUT'
            });

            expect(callbackOpen).toHaveBeenCalledWith($('#test-modal'));
            expect(callbackClose).not.toHaveBeenCalled();
            expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
            expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT');
            expect($('#test-modal .content').html()).toBe('OK');
        });

        it('Test openRemoteModal with bad request', function () {
            const callbackOpen = jasmine.createSpy('open');
            const callbackClose = jasmine.createSpy('close');

            modalManager.openRemoteModal({
                url: '/error404',
                element: '#test-modal',
                elementContent: '#test-modal .content',
                onOpen: function (element) {
                    callbackOpen(element);
                },
                onClose: function (element) {
                    callbackClose(element);
                }
            });

            expect(callbackOpen).not.toHaveBeenCalled();
            expect(callbackClose).not.toHaveBeenCalled();
            expect(jasmine.Ajax.requests.mostRecent().url).toBe('/error404');
            expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
            expect($('#test-modal .content').html()).toBe('');
        });
    });
});
