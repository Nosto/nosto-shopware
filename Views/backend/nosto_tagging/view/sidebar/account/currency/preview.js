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

Ext.define('Shopware.apps.NostoTagging.view.sidebar.account.currency.Preview', {
    /**
     * @string
     */
    extend: 'Ext.container.Container',

    /**
     * @string
     */
    alias: 'widget.nosto-sidebar-account-currency-preview',

    /**
     * @object
     */
    snippets: {
        notice: '{s name=sidebar/account/currency/preview/snippets_notice}Preview of the current currency formats extracted from Shopware. These are synchronised to Nosto when clicking the `Update Accounts` button above. Synchronising is only needed if the formats have changed in Shopware since Nosto was installed.{/s}'
    },

    /**
     * @object
     */
    defaults: {
        style: {
            margin: '15px 0 15px 0'
        }
    },

    /**
     * Initializes the component.
     *
     * @public
     * @return void
     */
    initComponent: function () {
        var me = this;

        me.callParent(arguments);
    },

    /**
     * Binds the store to this container, i.e. creates the currency preview
     * items defined in the store and renders them.
     *
     * @param store object
     * @return void
     */
    bindStore: function (store) {
        var me = this,
            i,
            j,
            group,
            child,
            groups,
            fieldSet;

        store.group('shopName');
        groups = store.getGroups();

        me.add({
            xtype: 'container',
            cls: Ext.baseCSSPrefix + 'global-notice-text',
            html: me.snippets.notice
        });

        for (i in groups) {
            if (groups.hasOwnProperty(i)) {
                group = groups[i];
                fieldSet = Ext.create('Ext.form.FieldSet', {
                    title: group.name,
                    items: []
                });
                for (j in group.children) {
                    if (group.children.hasOwnProperty(j)) {
                        child = group.children[j];
                        fieldSet.add({
                            xtype: 'container',
                            cls: Ext.baseCSSPrefix,
                            html: child.data.preview
                        });
                    }
                }

                me.add(fieldSet);
            }
        }
    }
});
