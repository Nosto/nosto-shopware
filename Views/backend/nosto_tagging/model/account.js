Ext.define('Shopware.apps.NostoTagging.model.Account', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields:[
        { name:'id', type:'int' },
        { name:'name', type:'string' },
        { name:'url', type:'string' },
        { name:'shopId', type:'int' },
        { name:'shopName', type:'string' }
    ],
    proxy: {
        type: 'ajax',
        api: {
            create: '{url action=createAccount}',
            update: '{url action=createAccount}',
            destroy: '{url action=deleteAccount}'
        },
        reader: {
            idProperty : 'id',
            type : 'json',
            root : 'data'
        },
        writer: {
            type : 'json',
            writeAllFields: true
        }
    }
});
