Ext.define('Shopware.apps.NostoTagging.model.Account', {
    extend: 'Ext.data.Model',
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
           // todo: how is this chosen??
            create: '{url action=createAccount}',
//            read : '{url action=getAccount}',
            update: '{url action=createAccount}'
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
