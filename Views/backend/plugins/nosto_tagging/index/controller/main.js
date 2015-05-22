//{namespace name=backend/nosto_tagging/controller/main}
//{block name="backend/index/controller/main"}
Ext.define('Shopware.apps.Index.controller.NostoTaggingMain', {
    override: 'Shopware.apps.Index.controller.Main',

    /**
     * @Override
     */
    init: function() {
        var me = this,
            result = me.callParent(arguments);

        Shopware.app.Application.addSubApplication({ name: 'Shopware.apps.NostoTagging' });

        return result;
    }
});
//{/block}