{block name="frontend_index_footer" append}
    <div class="nosto_customer" style="display:none">
        <span class="first_name">{$nostoCustomer->getFirstName()|escape:'htmlall':'UTF-8'}</span>
        <span class="last_name">{$nostoCustomer->getLastName()|escape:'htmlall':'UTF-8'}</span>
        <span class="email">{$nostoCustomer->getEmail()|escape:'htmlall':'UTF-8'}</span>
    </div>
{/block}
