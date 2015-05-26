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
