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
    <div class="nosto_element" id="nosto-page-product1"></div>
    <div class="nosto_element" id="nosto-page-product2"></div>
    <div class="nosto_element" id="nosto-page-product3"></div>
    {if isset($nostoProduct) && $nostoProduct}
        <div class="nosto_product" style="display: none">
            <span class="url">{$nostoProduct->getUrl()|escape:'htmlall':'UTF-8'}</span>
            <span class="product_id">{$nostoProduct->getProductId()|escape:'htmlall':'UTF-8'}</span>
            <span class="name">{$nostoProduct->getName()|escape:'htmlall':'UTF-8'}</span>
            <span class="image_url">{$nostoProduct->getImageUrl()|escape:'htmlall':'UTF-8'}</span>
            <span class="price">{$nostoProduct->getPrice()|escape:'htmlall':'UTF-8'}</span>
            <span class="price_currency_code">{$nostoProduct->getPriceCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
            <span class="availability">{$nostoProduct->getAvailability()|escape:'htmlall':'UTF-8'}</span>
            {foreach from=$nostoProduct->getCategories() item=category}
                <span class="category">{$category|escape:'htmlall':'UTF-8'}</span>
            {/foreach}
            {if $nostoProduct->getDescription()}
                <span class="description">{$nostoProduct->getDescription()|escape:'htmlall':'UTF-8'}</span>
            {/if}
            {if $nostoProduct->getListPrice()}
                <span class="list_price">{$nostoProduct->getListPrice()|escape:'htmlall':'UTF-8'}</span>
            {/if}
            {if $nostoProduct->getBrand()}
                <span class="brand">{$nostoProduct->getBrand()|escape:'htmlall':'UTF-8'}</span>
            {/if}
            {foreach from=$nostoProduct->getTag1() key=type item=tag}
                <span class="tag1">{$tag|escape:'htmlall':'UTF-8'}</span>
            {/foreach}
            {foreach from=$nostoProduct->getTag2() key=type item=tag}
                <span class="tag2">{$tag|escape:'htmlall':'UTF-8'}</span>
            {/foreach}
            {foreach from=$nostoProduct->getTag3() key=type item=tag}
                <span class="tag3">{$tag|escape:'htmlall':'UTF-8'}</span>
            {/foreach}
            {foreach from=$nostoProduct->getAlternateImageUrls() item=$alternateImageUrl}
                <span class="alternate_image_url">{$alternateImageUrl|escape:'htmlall':'UTF-8'}</span>
            {/foreach}
            {if $nostoProduct->getReviewCount()}
                <span class="review_count">{$nostoProduct->getReviewCount()|escape: 'htmlall':'UTF-8'}</span>
            {/if}
            {if $nostoProduct->getRatingValue()}
                <span class="rating_value">{$nostoProduct->getRatingValue()|escape: 'htmlall':'UTF-8'}</span>
            {/if}
            {if $nostoProduct->getSkus()}
                {foreach from=$nostoProduct->getSkus() item=sku}
                    <span class="nosto_sku">
                        <span class="id">{$sku->getId()|escape:'htmlall':'UTF-8'}</span>
                        <span class="name">{$sku->getName()|escape:'htmlall':'UTF-8'}</span>
                        <span class="price">{$sku->getPrice()|escape:'htmlall':'UTF-8'}</span>
                        <span class="list_price">{$sku->getListPrice()|escape:'htmlall':'UTF-8'}</span>
                        <span class="url">{$sku->getUrl()|escape:'htmlall':'UTF-8'}</span>
                        <span class="image_url">{$sku->getImageUrl()|escape:'htmlall':'UTF-8'}</span>
                        <span class="gtin">{$sku->getGtin()|escape:'htmlall':'UTF-8'}</span>
                        <span class="availability">{$sku->getAvailability()|escape:'htmlall':'UTF-8'}</span>
                        {if $sku->getCustomFields() and $sku->getCustomFields()|is_array}
                            <span class="custom_fields">
                                {foreach from=$sku->getCustomFields() key=key item=value}
                                    <span class="{$key|escape:'htmlall':'UTF-8'}">
                                        {$value|escape:'htmlall':'UTF-8'}
                                    </span>
                                {/foreach}
                            </span>
                        {/if}
                    </span>
                {/foreach}
            {/if}
        </div>
    {/if}
    {if isset($nostoCategory) && $nostoCategory}
        <div class="nosto_category"
             style="display:none">{$nostoCategory->getCategoryPath()|escape:'htmlall':'UTF-8'}</div>
    {/if}
{/block}
