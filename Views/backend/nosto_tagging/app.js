Ext.define('Shopware.apps.NostoTagging', {
    extend: 'Enlight.app.SubApplication',
    name: 'Shopware.apps.NostoTagging',
    bulkLoad: true,
    loadPath: '{url action=load}',
    controllers: ['Main'],
    stores:['Account'],
    models:['Account'],
    views: ['Main'],
    launch: function () {
        var me = this,
            ctrl = me.getController('Main');
        return ctrl.mainWindow;
    }
});