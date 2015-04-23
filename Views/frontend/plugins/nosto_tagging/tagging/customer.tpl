{block name="frontend_index_footer" append}
    <div class="nosto_customer" style="display:none">
        <span class="first_name">{$nosto_customer->getFirstName()|escape:'htmlall':'UTF-8'}</span>
        <span class="last_name">{$nosto_customer->getLastName()|escape:'htmlall':'UTF-8'}</span>
        <span class="email">{$nosto_customer->getEmail()|escape:'htmlall':'UTF-8'}</span>
    </div>
{/block}
