{**
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
*}

{if isset($nostoOrder) && is_object($nostoOrder)}
{block name="frontend_index_content" append}
	<div class="nosto_purchase_order" style="display:none">
		<span class="order_number">{$nostoOrder->getOrderNumber()|escape:'htmlall':'UTF-8'}</span>
		<div class="buyer">
			<span class="first_name">{$nostoOrder->getBuyerInfo()->getFirstName()|escape:'htmlall':'UTF-8'}</span>
			<span class="last_name">{$nostoOrder->getBuyerInfo()->getLastName()|escape:'htmlall':'UTF-8'}</span>
			<span class="email">{$nostoOrder->getBuyerInfo()->getEmail()|escape:'htmlall':'UTF-8'}</span>
		</div>
		<div class="purchased_items">
			{foreach from=$nostoOrder->getPurchasedItems() item=lineItem}
				<div class="line_item">
					<span class="product_id">{$lineItem->getProductId()|escape:'htmlall':'UTF-8'}</span>
					<span class="quantity">{$lineItem->getQuantity()|escape:'htmlall':'UTF-8'}</span>
					<span class="name">{$lineItem->getName()|escape:'htmlall':'UTF-8'}</span>
					<span class="unit_price">{$lineItem->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
					<span class="price_currency_code">{$lineItem->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
				</div>
			{/foreach}
		</div>
	</div>
{/block}
{/if}