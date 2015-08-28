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

Ext.define('Shopware.apps.NostoTagging.view.sidebar.Currency', {
    /**
     * @string
     */
    extend:'Ext.panel.Panel',

    /**
     * @string
     */
    alias: 'widget.nosto-sidebar-multi-currency',

    /**
     * @string
     */
    title: '{s name=sidebar/currency/title}Multi Currency{/s}',

    /**
     * @object
     */
    snippets: {
        notice: '{s name=sidebar/currency/snippets_notice}Choose what multi currency method should be used. By default Nosto uses the exchange rates from the shop to display recommendations in the correct currency. You can update the currency exchange rates in Nosto by clicking the button below. Note that there is also a cron job available for periodically updating the rates.{/s}',
        field: {
            multiCurrencyMethod: {
                label: '{s name=sidebar/currency/snippets_field_multiCurrencyMethod_label}Multi Currency Method{/s}'
            }
        },
        button: {
            updateExchangeRates: {
                label: '{s name=sidebar/currency/snippets_button_updateExchangeRates_label}Update Exchange Rates{/s}'
            },
            submit: {
                label: '{s name=sidebar/currency/snippets_button_submit_label}Save{/s}'
            }
        }
    },

    /**
     * @number
     */
    bodyPadding: 10,

    /**
     * Initializes the component.
     *
     * @public
     * @return void
     */
    initComponent:function () {
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

        me.multiCurrencyMethodCombo = Ext.create('Ext.form.field.ComboBox', {
            name: 'multiCurrencyMethod',
            forceSelection: true,
            queryMode: 'local',
            valueField: 'id',
            displayField: 'name',
            fieldLabel: me.snippets.field.multiCurrencyMethod.label
        });

        return me.settingsForm = Ext.create('Ext.form.Panel', {
            url: '{url controller=NostoTagging action=saveAdvancedSettings}',
            border: false,
            items: [
                Ext.create('Ext.form.FieldSet', {
                    layout: 'anchor',
                    border: false,
                    padding: 10,
                    defaults: {
                        labelWidth: 120,
                        anchor: '100%'
                    },
                    items: [
                        {
                            xtype: 'container',
                            cls: Ext.baseCSSPrefix + 'global-notice-text',
                            html: me.snippets.notice
                        },
                        me.multiCurrencyMethodCombo,
                        {
                            xtype: 'button',
                            text: me.snippets.button.updateExchangeRates.label,
                            cls: 'small secondary',
                            action: 'update-exchange-rates'
                        }
                    ]
                })
            ],
            buttons: [
                {
                    text: me.snippets.button.submit.label,
                    cls: 'small primary',
                    action: 'submit-multi-currency-settings'
                }
            ]
        });
    },

    /**
     * Loads data from stores into this settings form.
     * Binds the data stores to the form elements and sets loads the current
     * form data.
     *
     * @param stores object
     * @return void
     */
    loadStoreData: function (stores) {
        var me = this;

        me.multiCurrencyMethodCombo.bindStore(stores.getMultiCurrencyMethods());
        me.settingsForm.loadRecord(stores.getSettings().getAt(0));
    }
});
