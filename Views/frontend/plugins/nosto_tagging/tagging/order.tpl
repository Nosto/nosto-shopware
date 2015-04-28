{block name="frontend_index_footer" append}
    <div class="nosto_purchase_order" style="display:none">
        <span class="order_number">{$nosto_order->getOrderNumber()|escape:'htmlall':'UTF-8'}</span>
        <div class="buyer">
            <span class="first_name">{$nosto_order->getBuyerInfo()->getFirstName()|escape:'htmlall':'UTF-8'}</span>
            <span class="last_name">{$nosto_order->getBuyerInfo()->getLastName()|escape:'htmlall':'UTF-8'}</span>
            <span class="email">{$nosto_order->getBuyerInfo()->getEmail()|escape:'htmlall':'UTF-8'}</span>
        </div>
        <div class="purchased_items">
            {foreach from=$nosto_order->getPurchasedItems() item=line_item}
                <div class="line_item">
                    <span class="product_id">{$line_item->getProductId()|escape:'htmlall':'UTF-8'}</span>
                    <span class="quantity">{$line_item->getQuantity()|escape:'htmlall':'UTF-8'}</span>
                    <span class="name">{$line_item->getName()|escape:'htmlall':'UTF-8'}</span>
                    <span class="unit_price">{$line_item->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
                    <span class="price_currency_code">{$line_item->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
                </div>
            {/foreach}
        </div>
    </div>
{/block}
