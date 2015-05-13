{block name="frontend_index_footer" append}
    <div class="nosto_cart" style="display:none">
        {foreach from=$nostoCart->getLineItems() item=lineItem}
            <div class="line_item">
                <span class="product_id">{$lineItem->getProductId()|escape:'htmlall':'UTF-8'}</span>
                <span class="quantity">{$lineItem->getQuantity()|escape:'htmlall':'UTF-8'}</span>
                <span class="name">{$lineItem->getName()|escape:'htmlall':'UTF-8'}</span>
                <span class="unit_price">{$lineItem->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
                <span class="price_currency_code">{$lineItem->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
            </div>
        {/foreach}
    </div>
{/block}
