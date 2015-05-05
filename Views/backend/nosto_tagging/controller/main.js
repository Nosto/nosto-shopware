Ext.define('Shopware.apps.NostoTagging.controller.Main', {
    /**
     * Extends the Enlight controller.
     * @string
     */
    extend: 'Enlight.app.Controller',

    /**
     * Initializes the controller.
     *
     * Loads the account model store and create a new window for the account configurations.
     *
     * @return void
     */
    init: function () {
        var me = this;
        me.accountStore = me.getStore('Account');
        me.accountStore.load({
            callback: function(records, operation, success) {
                if (success) {
                    me.mainWindow = me.getView('Main').create({
                        accountStore: me.accountStore
                    });
                    me.mainWindow.show();
                } else {
                    throw new Error('Nosto: failed to load accounts.');
                }
            }
        });

        // Register event handler for window.postMessage() messages from Nosto.
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

        // todo: enable security check
        // Check the origin to prevent cross-site scripting.
//        if (event.origin !== decodeURIComponent(settings.origin)) {
//            return;
//        }
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
                            // todo: figure out how to get the account data binding to work.
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
                        success: function(record) {
                            // todo: figure out how to get the account data binding to work.
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