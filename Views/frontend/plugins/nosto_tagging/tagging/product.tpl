{block name="frontend_index_footer" append}
    <div class="nosto_product" style="display: none">
        <span class="url">{$nosto_product->getUrl()|escape:'htmlall':'UTF-8'}</span>
        <span class="product_id">{$nosto_product->getProductId()|escape:'htmlall':'UTF-8'}</span>
        <span class="name">{$nosto_product->getName()|escape:'htmlall':'UTF-8'}</span>
        <span class="image_url">{$nosto_product->getImageUrl()|escape:'htmlall':'UTF-8'}</span>
        <span class="price">{$nosto_product->getPrice()|escape:'htmlall':'UTF-8'}</span>
        <span class="list_price">{$nosto_product->getListPrice()|escape:'htmlall':'UTF-8'}</span>
        <span class="price_currency_code">{$nosto_product->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
        <span class="availability">{$nosto_product->getAvailability()|escape:'htmlall':'UTF-8'}</span>
        <span class="description">{$nosto_product->getDescription()|escape:'htmlall':'UTF-8'}</span>
        <span class="brand">{$nosto_product->getBrand()|escape:'htmlall':'UTF-8'}</span>
        <span class="date_published">{$nosto_product->getDatePublished()|escape:'htmlall':'UTF-8'}</span>
        {foreach from=$nosto_product->getCategories() item=category}
            <span class="category">{$category|escape:'htmlall':'UTF-8'}</span>
        {/foreach}
        {foreach from=$nosto_product->getTags() item=tag}
            <span class="tag1">{$tag|escape:'htmlall':'UTF-8'}</span>
        {/foreach}
    </div>
    {if isset($nosto_category) && is_object($nosto_category)}
        <div class="nosto_category" style="display:none">{$nosto_category->getCategoryPath()|escape:'htmlall':'UTF-8'}</div>
    {/if}
{/block}
