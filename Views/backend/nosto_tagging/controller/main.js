/**
 * Copyright (c) 2015, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

Ext.define('Shopware.apps.NostoTagging.controller.Main', {
    /**
     * Extends the Enlight controller.
     * @string
     */
    extend: 'Enlight.app.Controller',

    /**
     * Initializes the controller.
     *
     * @return void
     */
    init: function () {
        var me = this;
        me.control({
            'nosto-sidebar-general button[action=update-accounts]': {
                click: me.onUpdateAccounts
            },
            'nosto-sidebar-multi-currency button[action=update-exchange-rates]': {
                click: me.onUpdateExchangeRates
            },
            'nosto-sidebar-multi-currency button[action=submit-multi-currency-settings]': {
                click: me.onSubmitMultiCurrencySettings
            }
        });
        me.showWindow();
        me.postMessageListener();
    },

    /**
     * Shows the main window.
     *
     * @return void
     */
    showWindow: function () {
        var me = this;

        me.mainWindow = me.getView('Main').create();
        me.mainWindow.show();
        me.mainWindow.setLoading(true);

        me.batchStore = me.getStore('Batch');
        me.batchStore.load({
            callback: function(records, op, success) {
                me.mainWindow.setLoading(false);
                if (success) {
                    me.settings = records[0].getConfigs().getAt(0);
                    me.mainWindow.fireEvent('storesLoaded', records[0]);
                } else {
                    throw new Error('Nosto: failed to load stores.');
                }
            }
        });
    },

    /**
     * Register event handler for window.postMessage() messages from Nosto through which we handle account creation,
     * connection and deletion.
     *
     * @return void
     */
    postMessageListener: function () {
        var me = this;
        window.addEventListener('message', Ext.bind(me.receiveMessage, me), false);
    },

    /**
     * Window.postMessage() event handler.
     *
     * Handles the communication between the iframe and the plugin.
     *
     * @param event Object
     * @return void
     */
    receiveMessage: function(event) {
        var me = this,
            originRegexp = new RegExp(me.settings.get('postMessageOrigin')),
            json,
            data,
            account,
            op,
            accountData;

        // Check the origin to prevent cross-site scripting.
        if (!originRegexp.test(event.origin)) {
            return;
        }
        // If the message does not start with '[Nosto]', then it is not for us.
        if ((''+event.data).substr(0, 7) !== '[Nosto]') {
            return;
        }

        json = (''+event.data).substr(7);
        data = Ext.decode(json);
        if (typeof data === 'object' && data.type) {
            account = me.mainWindow.getActiveAccount();
            if (!account) {
                throw new Error('Nosto: failed to determine active account.');
            }
            switch (data.type) {
                case 'newAccount':
                    if (data.params && data.params.email) {
                        account.set('email', data.params.email);
                    }
                    account.save({
                        success: function(record, op) {
                            // why can't we get the model data binding to work?
                            if (op.resultSet && op.resultSet.records) {
                                accountData = op.resultSet.records[0].data;
                                record.set('id', accountData.id);
                                record.set('name', accountData.name);
                                record.set('url', accountData.url);
                                me.mainWindow.reloadIframe(record);
                            } else {
                                throw new Error('Nosto: failed to create new account.');
                            }
                        }
                    });
                    break;

                case 'removeAccount':
                    account.destroy({
                        success: function(record, op) {
                            // why can't we get the model data binding to work?
                            if (op.resultSet && op.resultSet.records) {
                                accountData = op.resultSet.records[0].data;
                                record.set('id', 0);
                                record.set('name', '');
                                record.set('url', accountData.url);
                                me.mainWindow.reloadIframe(record);
                            } else {
                                throw new Error('Nosto: failed to delete account.');
                            }
                        }
                    });
                    break;

                case 'connectAccount':
                    Ext.Ajax.request({
                        method: 'POST',
                        url: '{url controller=NostoTagging action=connectAccount}',
                        params: {
                            shopId: account.get('shopId')
                        },
                        success: function(response) {
                            op = Ext.decode(response.responseText);
                            if (op.success && op.data.redirect_url) {
                                window.location.href = op.data.redirect_url;
                            } else {
                                throw new Error('Nosto: failed to handle account connection.');
                            }
                        }
                    });
                    break;

                case 'syncAccount':
                    Ext.Ajax.request({
                        method: 'POST',
                        url: '{url controller=NostoTagging action=syncAccount}',
                        params: {
                            shopId: account.get('shopId')
                        },
                        success: function(response) {
                            op = Ext.decode(response.responseText);
                            if (op.success && op.data.redirect_url) {
                                window.location.href = op.data.redirect_url;
                            } else {
                                throw new Error('Nosto: failed to handle account synchronisation.');
                            }
                        }
                    });
                    break;

                default:
                    throw new Error('Nosto: invalid postMessage `type`.');
            }
        }
    },

    /**
     * Updates currency exchange rates for all Nosto accounts using multi currency.
     *
     * @return void
     */
    onUpdateExchangeRates: function() {
        var me = this,
            op;

        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=NostoTagging action=updateCurrencyExchangeRates}',
            success: function(response) {
                op = Ext.decode(response.responseText);
                if (op.success && op.data.messages) {
                    me.growl(op.data.messages);
                } else {
                    throw new Error('Nosto: failed to update currency exchange rates.');
                }
            }
        });
    },

    /**
     * Updates all Nosto accounts. Sync info like shop url and currency settings to Nosto.
     *
     * @return void
     */
    onUpdateAccounts: function() {
        var me = this,
            op;

        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=NostoTagging action=updateAccounts}',
            success: function(response) {
                op = Ext.decode(response.responseText);
                if (op.success && op.data.messages) {
                    me.growl(op.data.messages);
                } else {
                    throw new Error('Nosto: failed to update Nosto accounts.');
                }
            }
        });
    },

    /**
     * Event listener for the Multi Currency form submit button click event.
     * Submits the form to save the multi currency settings.
     *
     * @return void
     */
    onSubmitMultiCurrencySettings: function () {
        var me = this,
            form = me.mainWindow.sideBar.multiCurrencySettings.settingsForm;

        me.saveAdvancedSettings(form);
    },

    /**
     * Saves the "Advanced Settings" by submitting the passed form.
     * There will be one form per accordion panel, but the submit action will
     * be the same for all of them.
     *
     * @param form object
     * @return void
     */
    saveAdvancedSettings: function (form) {
        var me = this;

        if (form.getForm().isValid()) {
            form.submit({
                success: function(form, action) {
                    if (action.result.data.messages) {
                        me.growl(action.result.data.messages);
                    } else {
                        me.growl([
                            {
                                title: 'Success',
                                text: 'Settings have been saved.'
                            }
                        ]);
                    }
                },
                failure: function(form, action) {
                    if (action.result.data.messages) {
                        me.growl(action.result.data.messages);
                    } else {
                        me.growl([
                            {
                                title: 'Error',
                                text: 'Settings have NOT been saved.'
                            }
                        ]);
                    }
                }
            });
        }
    },

    /**
     * Shows a "growl" message notification to the user.
     *
     * @param messages object
     * @return void
     */
    growl: function(messages) {
        for (var i = 0, l = messages.length; i < l; i++) {
            Shopware.Notification.createStickyGrowlMessage({
                title: messages[i].title,
                text: messages[i].text
            });
        }
    }
});