{block name="frontend_index_footer" append}
    <div class="nosto_cart" style="display:none">
        {foreach from=$nosto_cart->getLineItems() item=line_item}
            <div class="line_item">
                <span class="product_id">{$line_item->getProductId()|escape:'htmlall':'UTF-8'}</span>
                <span class="quantity">{$line_item->getQuantity()|escape:'htmlall':'UTF-8'}</span>
                <span class="name">{$line_item->getName()|escape:'htmlall':'UTF-8'}</span>
                <span class="unit_price">{$line_item->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
                <span class="price_currency_code">{$line_item->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
            </div>
        {/foreach}
    </div>
{/block}
