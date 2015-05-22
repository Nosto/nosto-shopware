Ext.define('Shopware.apps.NostoTagging.controller.Main', {
    /**
     * Extends the Enlight controller.
     * @string
     */
    extend: 'Enlight.app.Controller',

    /**
     * Settings for the controller.
     */
    settings: {
        postMessageOrigin: null
    },

    /**
     * Initializes the controller.
     *
     * @return void
     */
    init: function () {
        var me = this;
        me.showWindow();
        me.loadSettings();
        me.postMessageListener();
    },

    /**
     * Shows the main window.
     *
     * @return void
     */
    showWindow: function () {
        var me = this;
        me.accountStore = me.getStore('Account');
        me.mainWindow = me.getView('Main').create({
            accountStore: me.accountStore
        });
        me.mainWindow.show();
        me.mainWindow.setLoading(true);
        me.accountStore.load({
            callback: function(records, operation, success) {
                me.mainWindow.setLoading(false);
                if (success) {
                    me.mainWindow.initAccountTabs();
                } else {
                    throw new Error('Nosto: failed to load accounts.');
                }
            }
        });
    },

    /**
     * Loads controller settings.
     *
     * @return void
     */
    loadSettings: function () {
        var me = this;
        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=NostoTagging action=loadSettings}',
            success: function(response) {
                var operation = Ext.decode(response.responseText);
                if (operation.success && operation.data) {
                    me.settings = operation.data;
                } else {
                    throw new Error('Nosto: failed to load settings.');
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
            json,
            data,
            account,
            operation;

        // Check the origin to prevent cross-site scripting.
        if (event.origin !== decodeURIComponent(me.settings.postMessageOrigin)) {
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
                    account.save({
                        success: function(record, op) {
                            // why can't we get the model data binding to work?
                            if (op.resultSet && op.resultSet.records) {
                                record.set('url', op.resultSet.records[0].data.url);
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
                                record.set('url', op.resultSet.records[0].data.url);
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
                            operation = Ext.decode(response.responseText);
                            if (operation.success && operation.data.redirect_url) {
                                window.location.href = operation.data.redirect_url;
                            } else {
                                throw new Error('Nosto: failed to handle account connection.');
                            }
                        }
                    });
                    break;

                default:
                    throw new Error('Nosto: invalid postMessage `type`.');
            }
        }
    }
});