{**
* Copyright (c) 2015, Nosto Solutions Ltd
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
* @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
* @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
*}

{block name="nosto_product"}
	{if isset($nostoProduct) && is_object($nostoProduct)}
		<div class="nosto_product" style="display: none">
			<span class="url">{$nostoProduct->getUrl()|escape:'htmlall':'UTF-8'}</span>
			<span class="product_id">{$nostoProduct->getProductId()|escape:'htmlall':'UTF-8'}</span>
			<span class="name">{$nostoProduct->getName()|escape:'htmlall':'UTF-8'}</span>
			<span class="image_url">{$nostoProduct->getImageUrl()|escape:'htmlall':'UTF-8'}</span>
			{if $nostoProduct->getPrice()}
				<span class="price">{$nostoProduct->getPrice()->getPrice()|number_format:2:".":""}</span>
			{/if}
			{if $nostoProduct->getCurrency()}
				<span class="price_currency_code">{$nostoProduct->getCurrency()->getCode()|escape:'htmlall':'UTF-8'}</span>
			{/if}
			{if $nostoProduct->getAvailability()}
				<span class="availability">{$nostoProduct->getAvailability()->getAvailability()|escape:'htmlall':'UTF-8'}</span>
			{/if}
			{foreach from=$nostoProduct->getCategories() item=category}
				<span class="category">{$category|escape:'htmlall':'UTF-8'}</span>
			{/foreach}
			{if $nostoProduct->getFullDescription()}
				<span class="description">{$nostoProduct->getFullDescription()|escape:'htmlall':'UTF-8'}</span>
			{/if}
			{if $nostoProduct->getListPrice()}
				<span class="list_price">{$nostoProduct->getListPrice()->getPrice()|number_format:2:".":""}</span>
			{/if}
			{if $nostoProduct->getBrand()}
				<span class="brand">{$nostoProduct->getBrand()|escape:'htmlall':'UTF-8'}</span>
			{/if}
			{foreach from=$nostoProduct->getTags() key=type item=tags}
				{foreach from=$tags item=tag}
					<span class="{$type|escape:'quotes'}">{$tag|escape:'htmlall':'UTF-8'}</span>
				{/foreach}
			{/foreach}
			{if $nostoProduct->getDatePublished()}
				<span class="date_published">{$nostoProduct->getDatePublished()|date_format:'%Y-%m-%d'}</span>
			{/if}
			{if $nostoProduct->getPriceVariationId()}
				<span class="variation_id">{$nostoProduct->getPriceVariationId()|escape:'htmlall':'UTF-8'}</span>
				{foreach from=$nostoProduct->getPriceVariations() item=variation}
					<div class="variation">
						<span class="variation_id">{$variation->getId()->getId()|escape:'htmlall':'UTF-8'}</span>
						<span class="price_currency_code">{$variation->getCurrency()->getCode()|escape:'htmlall':'UTF-8'}</span>
						<span class="price">{$variation->getPrice()->getPrice()|number_format:2:".":""}</span>
						<span class="list_price">{$variation->getListPrice()->getPrice()|number_format:2:".":""}</span>
						<span class="availability">{$variation->getAvailability()->getAvailability()|escape:'htmlall':'UTF-8'}</span>
					</div>
				{/foreach}
			{/if}
		</div>
	{/if}
{/block}
{block name="nosto_product_category"}
	{if isset($nostoCategory) && is_object($nostoCategory)}
		<div class="nosto_category" style="display:none">{$nostoCategory->getCategoryPath()|escape:'htmlall':'UTF-8'}</div>
	{/if}
{/block}