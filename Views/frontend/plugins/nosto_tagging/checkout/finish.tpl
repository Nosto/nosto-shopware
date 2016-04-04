{**
* Copyright (c) 2016, Nosto Solutions Ltd
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
* @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
* @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
*}

{block name="frontend_index_content" append}
	<div class="nosto_element" id="thankyou-nosto-1"></div>
	<div class="nosto_element" id="thankyou-nosto-2"></div>
{if isset($nostoOrder) && is_object($nostoOrder)}
	<div class="nosto_purchase_order" style="display:none">
		<span class="order_number">{$nostoOrder->getOrderNumber()|escape:'htmlall':'UTF-8'}</span>
		<span class="order_status_code">{$nostoOrder->getOrderStatus()->getCode()|escape:'htmlall':'UTF-8'}</span>
		<span class="order_status_label">{$nostoOrder->getOrderStatus()->getLabel()|escape:'htmlall':'UTF-8'}</span>
		<span class="payment_provider">{$nostoOrder->getPaymentProvider()|escape:'htmlall':'UTF-8'}</span>
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
{/if}
{/block}
