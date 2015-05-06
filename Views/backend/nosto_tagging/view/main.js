Ext.define('Shopware.apps.NostoTagging.view.Main', {
    /**
     * Extends the Enlight application window.
     * @string
     */
    extend: 'Enlight.app.Window',

    /**
     * Window title.
     * @string
     */
    title: 'Nosto',

    /**
     * Window layout.
     * @string
     */
    layout: 'fit',

    /**
     * Window width.
     * @integer
     */
    width: 1024,

    /**
     * Window height.
     * @integer
     */
    height: "90%",

    /**
     * Initializes the component.
     *
     * @public
     * @return void
     */
    initComponent: function () {
        var me = this;
        me.items = me.tabPanel = me.createTabPanel();
        me.callParent(arguments);
    },

    /**
     * Creates a tab panel which holds off the account settings per Shop.
     *
     * @public
     * @return Ext.tab.Panel
     */
    createTabPanel: function () {
        var me = this,
            tabs = [];

        me.accountStore.each(function(account) {
            tabs.push({
                title: account.get('shopName'),
                xtype: 'component',
                autoEl: {
                    'tag': 'iframe',
                    'data-shopId': account.get('shopId'),
                    'src': account.get('url')
                },
                shopId: account.get('shopId')
            });
        });

        return Ext.create('Ext.tab.Panel', {
            layout: 'fit',
            items: tabs
        });
    },

    /**
     * Getter for the active account model.
     *
     * @public
     * @returns Shopware.apps.NostoTagging.model.Account
     */
    getActiveAccount: function () {
        var me = this,
            activeTab = me.tabPanel.getActiveTab(),
            activeAccount = null;

        me.accountStore.each(function(account) {
            if (account.get('shopId') === activeTab.shopId) {
                activeAccount = account;
            }
        });
        return activeAccount;
    },

    /**
     * Reloads the active iframe window with url from account model.
     *
     * @public
     * @param account Shopware.apps.NostoTagging.model.Account
     */
    reloadIframe: function (account) {
        var me = this,
            elements;

        elements = Ext.query('#' + me.tabPanel.getId() + ' iframe[data-shopId="' + account.get('shopId') + '"]');
        if (typeof elements[0] !== 'undefined') {
            elements[0].src = account.get('url');
        } else {
            throw new Error('Nosto: failed to re-load iframe for shop #' + account.get('shopId'));
        }
    }
});