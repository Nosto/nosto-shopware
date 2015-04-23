Ext.define('Shopware.apps.NostoTagging.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;
        me.accountStore = me.getStore('Account');
        me.accountStore.on('load', function (store) {
            me.mainWindow = me.getView('Main').create({
                accountStore: store
            });
            me.mainWindow.show();
        });

        // Register event handler for window.postMessage() messages from Nosto.
        window.addEventListener('message', Ext.bind(me.receiveMessage, me), false);
    },

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
                    // todo: handle errors.
                    account.save();
                    me.mainWindow.reloadIframe(account);
                    break;

                case 'removeAccount':
                    // todo: handle errors.
                    account.destroy();
                    me.mainWindow.reloadIframe(account);
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