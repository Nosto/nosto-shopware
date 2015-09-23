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

Ext.define('Shopware.apps.NostoTagging.view.Sidebar', {
    /**
     * @string
     */
    extend: 'Ext.panel.Panel',

    /**
     * @string
     */
    alias: 'widget.nosto-sidebar',

    /**
     * @string
     */
    region: 'east',

    /**
     * @object
     */
    layout: 'accordion',

    /**
     * @boolean
     */
    collapsible: true,

    /**
     * @boolean
     */
    collapsed: true,

    /**
     * @string
     */
    title: '{s name=sidebar/title}Advanced Settings{/s}',

    /**
     * @integer
     */
    width: 350,

    /**
     * Initializes the component.
     *
     * @public
     * @return void
     */
    initComponent: function () {
        var me = this;

        me.items = me.createElements();
        me.callParent(arguments);
    },

    /**
     * Creates the component elements.
     *
     * @return object
     */
    createElements: function () {
        var me = this;

        me.generalSettings = Ext.create('Shopware.apps.NostoTagging.view.sidebar.General');
        me.accountSettings = Ext.create('Shopware.apps.NostoTagging.view.sidebar.Account');
        me.multiCurrencySettings = Ext.create('Shopware.apps.NostoTagging.view.sidebar.Currency');

        return [
            me.generalSettings,
            me.accountSettings,
            me.multiCurrencySettings
        ];
    },

    /**
     * populates the sidebar panels with data form the given stores.
     *
     * @param stores object
     * @return void
     */
    populatePanels: function (stores) {
        var me = this;

        me.generalSettings.loadStoreData(stores);
        me.accountSettings.loadStoreData(stores);
        me.multiCurrencySettings.loadStoreData(stores);
    }
});