Ext.define('Shopware.apps.NostoTagging.store.Account', {
    extend: 'Ext.data.Store',
    model: 'Shopware.apps.NostoTagging.model.Account',
    autoLoad: true,
    proxy: {
        type: 'ajax',
        url: '{url action=getAccounts}',
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
