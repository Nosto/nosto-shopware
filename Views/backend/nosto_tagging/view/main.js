/**
 * Shopware 4, 5
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

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
    width: "70%",

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
        me.items = me.tabPanel = Ext.create('Ext.tab.Panel', {
            layout: 'fit',
            items: []
        });
        me.callParent(arguments);
    },

    /**
     * Creates tabs for each account in the account store
     * and adds them to the tab panel.
     *
     * @public
     * @return void
     */
    initAccountTabs: function () {
        var me = this,
            i = 0,
            tab;

        me.accountStore.each(function(account) {
            tab = me.tabPanel.add({
                title: account.get('shopName'),
                xtype: 'component',
                autoEl: {
                    'tag': 'iframe',
                    'data-shopId': account.get('shopId'),
                    'src': account.get('url')
                },
                shopId: account.get('shopId')
            });
            if (++i === 1) {
                me.tabPanel.setActiveTab(tab);
            }
        });
    },

    /**
     * Getter for the active account model.
     *
     * @public
     * @return Shopware.apps.NostoTagging.model.Account
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