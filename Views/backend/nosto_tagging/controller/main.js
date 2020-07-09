/*
 * Copyright (c) 2020, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2020 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

//noinspection JSUnusedGlobalSymbols,JSCheckFunctionSignatures,JSUnresolvedVariable
Ext.define('Shopware.apps.NostoTagging.controller.Main', {
  /**
   * Extends the Enlight controller.
   * @string
   */
  extend: 'Enlight.app.Controller',

  /**
   * Settings for the controller.
   */
  settings: {
    postMessageOrigin: null
  },

  /**
   * Initializes the controller.
   *
   * @return void
   */
  init: function () {
    const me = this;
    me.showWindow();
    me.loadSettings();
    me.postMessageListener();
  },

  /**
   * Shows the main window.
   *
   * @return void
   */
  showWindow: function () {
    const me = this;
    me.accountStore = me.getStore('Account');
    //noinspection JSUnresolvedFunction
    me.mainWindow = me.getView('Main').create({
      accountStore: me.accountStore
    });
    me.mainWindow.show();
    //noinspection JSUnresolvedFunction
    me.mainWindow.setLoading(true);
    //noinspection JSUnusedGlobalSymbols
    me.accountStore.load({
      callback: function (records, op, success) {
        //noinspection JSUnresolvedFunction
        me.mainWindow.setLoading(false);
        if (success) {
          me.mainWindow.initAccountTabs();
        } else {
          throw new Error('Nosto: failed to load accounts.');
        }
      }
    });
  },

  /**
   * Loads controller settings.
   *
   * @return void
   */
  loadSettings: function () {
    //noinspection JSUnresolvedVariable
    const me = this;
    //noinspection JSUnresolvedVariable
    Ext.Ajax.request({
      method: 'GET',
      url: '{url controller=NostoTagging action=loadSettings}',
      success: function (response) {
        const op = Ext.decode(response.responseText);
        if (op.success && op.data) {
          me.settings = op.data;
        } else {
          throw new Error('Nosto: failed to load settings.');
        }
      }
    });
  },

  /**
   * Register event handler for window.postMessage() messages from Nosto through which we handle account creation,
   * connection and deletion.
   *
   * @return void
   */
  postMessageListener: function () {
    //noinspection JSUnresolvedVariable
    const me = this;
    window.addEventListener('message', Ext.bind(me.receiveMessage, me), false);
  },

  /**
   * Window.postMessage() event handler.
   *
   * Handles the communication between the iframe and the plugin.
   *
   * @param event Object
   * @return void
   */
  receiveMessage: function (event) {
    //noinspection JSCheckFunctionSignatures
    const me = this,
      originRegexp = new RegExp(me.settings.postMessageOrigin);
    let json,
      data,
      account,
      op,
      accountData;

    // Check the origin to prevent cross-site scripting.
    //noinspection JSUnresolvedVariable
    if (!originRegexp.test(event.origin)) {
      return;
    }
    // If the message does not start with '[Nosto]', then it is not for us.
    if (('' + event.data).substr(0, 7) !== '[Nosto]') {
      return;
    }

    json = ('' + event.data).substr(7);
    data = Ext.decode(json);
    if (typeof data === 'object' && data.type) {
      account = me.mainWindow.getActiveAccount();
      if (!account) {
        throw new Error('Nosto: failed to determine active account.');
      }
      switch (data.type) {
        case 'newAccount':
          //noinspection JSUnresolvedVariable
          if (data.params && data.params.email) {
            account.set('email', data.params.email);
            if (data.params.details) {
              account.set('details', JSON.stringify(data.params.details));
            }
          }
          account.save({
            success: function (record, op) {
              // why can't we get the model data binding to work?
              //noinspection JSUnresolvedVariable
              if (op.resultSet && op.resultSet.records) {
                accountData = op.resultSet.records[0].data;
                record.set('id', accountData.id);
                record.set('name', accountData.name);
                record.set('url', accountData.url);
                me.mainWindow.reloadIframe(record);
              } else {
                throw new Error('Nosto: failed to create new account.');
              }
            }
          });
          break;

        case 'removeAccount':
          account.destroy({
            success: function (record, op) {
              // why can't we get the model data binding to work?
              //noinspection JSUnresolvedVariable
              if (op.resultSet && op.resultSet.records) {
                accountData = op.resultSet.records[0].data;
                record.set('id', 0);
                record.set('name', '');
                record.set('url', accountData.url);
                me.mainWindow.reloadIframe(record);
              } else {
                throw new Error('Nosto: failed to delete account.');
              }
            }
          });
          break;

        case 'connectAccount':
          //noinspection JSUnresolvedVariable
          Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=NostoTagging action=connectAccount}',
            params: {
              shopId: account.get('shopId')
            },
            success: function (response) {
              op = Ext.decode(response.responseText);
              //noinspection JSUnresolvedVariable
              if (op.success && op.data.redirect_url) {
                //noinspection JSUnresolvedVariable
                window.location.href = op.data.redirect_url;
              } else {
                throw new Error('Nosto: failed to handle account connection.');
              }
            }
          });
          break;

        default:
          throw new Error('Nosto: invalid postMessage `type`.');
      }
    }
  }
});
