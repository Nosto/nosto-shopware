/**
 * Copyright (c) 2015, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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

        me.items = me.createElements();
        me.registerEvents();
        me.callParent(arguments);
        me.on('storesLoaded', me.onStoresLoaded, me);
    },

    /**
     * Registers additional component events.
     *
     * @return void
     */
    registerEvents: function() {
        this.addEvents(
            'storesLoaded'
        );
    },

    /**
     * Event listener for the stores loaded event.
     * Here we populate all the data in the view.
     *
     * @param object stores
     * @return void
     */
    onStoresLoaded: function (stores) {
        var me = this;

        // Create the Nosto account management tabs.
        me.accountStore = stores.getAccounts();
        me.createAccountTabs();

        // Populate the advanced settings sidebar.
        me.sideBar.populatePanels(stores);
    },

    /**
     * Creates the window elements.
     *
     * @return object
     */
    createElements: function () {
        var me = this;

        me.tabPanel = Ext.create('Ext.tab.Panel', {
            region: 'center',
            items: []
        });
        me.sideBar = Ext.create('Shopware.apps.NostoTagging.view.Sidebar', {
            region: 'east',
            items: []
        });

        return Ext.create('Ext.container.Container', {
            layout: 'border',
            items: [
                me.tabPanel,
                me.sideBar
            ]
        });
    },

    /**
     * Creates tabs for each account in the account store
     * and adds them to the tab panel.
     *
     * @public
     * @return void
     */
    createAccountTabs: function () {
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