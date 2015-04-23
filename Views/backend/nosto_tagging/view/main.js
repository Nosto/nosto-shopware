Ext.define('Shopware.apps.NostoTagging.view.Main', {
    extend: 'Enlight.app.Window',
    title: 'Nosto',
    layout: 'fit',

    initComponent: function () {
        var me = this;
        me.items = me.tabPanel = me.createTabPanel();
        me.callParent(arguments);
    },

    createTabPanel: function () {
        var me = this,
            tabs = [];

        me.accountStore.each(function(account) {
            tabs.push({
                title: account.get('shopName'),
                xtype: 'component',
                autoEl: {
                    tag: 'iframe',
                    src: account.get('url')
                },
                shopId: account.get('shopId')
            });
        });

        return Ext.create('Ext.tab.Panel', {
            layout: 'fit',
            items: tabs
        });
    },

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

    reloadIframe: function (account) {
        var me = this;
        // todo: find <iframe> in current tab and replace "src" with account.get('url')
    }
});