{if isset($nostoOrder) && is_object($nostoOrder)}
{block name="frontend_index_footer" append}
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