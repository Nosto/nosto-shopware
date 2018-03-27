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

{block name='frontend_index_header_meta_tags' append}
    <meta name="nosto-version" content="{$nostoVersion|escape:'htmlall':'UTF-8'}">
    <meta name="nosto-unique-id" content="{$nostoUniqueId|escape:'htmlall':'UTF-8'}">
    <meta name="nosto-language" content="{$nostoLanguage|escape:'htmlall':'UTF-8'}">
{/block}
{block name="frontend_index_header_javascript" append}
    {if isset($nostoAccountName) && isset($nostoAccountName)}
        <!-- Nosto Javascript Stub -->
        <script type="text/javascript">
            {literal}
            (function () {
                var name = "nostojs";
                window[name] = window[name] || function (cb) {
                            (window[name].q = window[name].q || []).push(cb);
                        };
            })();
            {/literal}
        </script>
        <!-- Nosto Tagging Script -->
        <script type="text/javascript"
                src="//{$nostoServerUrl|escape:'htmlall':'UTF-8'}/include/{$nostoAccountName|escape:'htmlall':'UTF-8'}"
                async></script>
        <script type="text/javascript">
            //<![CDATA[
            {literal}
            if (typeof Nosto === "undefined") {
                var Nosto = {};
            }
            {/literal}
            Nosto.addProductToCart = function (productNumber, element) {
                if (typeof nostojs !== 'undefined' && typeof element == 'object') {
                    var slotId = Nosto.resolveContextSlotId(element);
                    if (slotId) {
                        nostojs(function (api) {
                            api.recommendedProductAddedToCart(productNumber, slotId);
                        });
                    }
                }
                var form = document.createElement("form");
                form.setAttribute("method", "post");
                form.setAttribute("action", "{url controller=checkout action=addArticle}");

                var hiddenFields = {
                    "sActionIdentifier": "{$sUniqueRand}",
                    "sAdd": productNumber,
                    "sQuantity": 1
                };

                for (var key in hiddenFields) {
                    if (hiddenFields.hasOwnProperty(key)) {
                        var hiddenField = document.createElement("input");
                        hiddenField.setAttribute("type", "hidden");
                        hiddenField.setAttribute("name", key);
                        hiddenField.setAttribute("value", hiddenFields[key]);
                        form.appendChild(hiddenField);
                    }
                }
                document.body.appendChild(form);
                if (typeof CSRF === 'object' && typeof CSRF.updateForms === 'function') {
                    CSRF.updateForms();
                }
                form.submit();
            };
            Nosto.resolveContextSlotId = function (element) {
                var m = 20;
                var n = 0;
                var e = element;
                while (typeof e.parentElement !== "undefined" && e.parentElement) {
                    ++n;
                    e = e.parentElement;
                    if (e.getAttribute('class') == 'nosto_element' && e.getAttribute('id')) {
                        return e.getAttribute('id');
                    }
                    if (n >= m) {
                        return false;
                    }
                }
                return false;
            };

            //]]>
        </script>
    {/if}
{/block}
{block name="frontend_index_content" append}
    {if isset($nostoCustomer) && $nostoCustomer}
        <div class="nosto_customer" style="display:none">
            {if isset($nostoHcid) && !empty($nostoHcid)}
                <span class="hcid">{$nostoHcid|escape:'htmlall':'UTF-8'}</span>
            {/if}
            <span class="first_name">{$nostoCustomer->getFirstName()|escape:'htmlall':'UTF-8'}</span>
            <span class="last_name">{$nostoCustomer->getLastName()|escape:'htmlall':'UTF-8'}</span>
            <span class="email">{$nostoCustomer->getEmail()|escape:'htmlall':'UTF-8'}</span>
            <span class="customer_reference">{$nostoCustomer->getCustomerReference()|escape:'htmlall':'UTF-8'}</span>
        </div>
    {/if}
    <div class="nosto_cart" style="display:none">
        {if isset($nostoHcid) && !empty($nostoHcid)}
            <span class="hcid">{$nostoHcid|escape:'htmlall':'UTF-8'}</span>
        {/if}
        {if isset($nostoCart) && $nostoCart}
            {foreach from=$nostoCart->getLineItems() item=lineItem}
                <div class="line_item">
                    <span class="product_id">{$lineItem->getProductId()|escape:'htmlall':'UTF-8'}</span>
                    <span class="quantity">{$lineItem->getQuantity()|escape:'htmlall':'UTF-8'}</span>
                    <span class="name">{$lineItem->getName()|escape:'htmlall':'UTF-8'}</span>
                    <span class="unit_price">{$lineItem->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
                    <span class="price_currency_code">{$lineItem->getPriceCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
                </div>
            {/foreach}
        {/if}
    </div>
    {if isset($nostoPageType) && is_string($nostoPageType)}
        <div class="nosto_page_type"
             style="display:none">{$nostoPageType|escape:'htmlall':'UTF-8'}</div>
    {/if}

{/block}
