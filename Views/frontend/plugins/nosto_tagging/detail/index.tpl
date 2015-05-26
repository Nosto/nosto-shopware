{**
* Shopware 4, 5
* Copyright © shopware AG
*
* According to our dual licensing model, this program can be used either
* under the terms of the GNU Affero General Public License, version 3,
* or under a proprietary license.
*
* The texts of the GNU Affero General Public License with an additional
* permission and of our proprietary license can be found at and
* in the LICENSE file you have received along with this program.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* "Shopware" is a registered trademark of shopware AG.
* The licensing of the program under the AGPLv3 does not imply a
* trademark license. Therefore any rights, title and interest in
* our trademarks remain entirely with us.
*}

{block name="frontend_index_content" append}
    <div class="nosto_element" id="nosto-page-product1"></div>
    <div class="nosto_element" id="nosto-page-product2"></div>
    <div class="nosto_element" id="nosto-page-product3"></div>
	{if isset($nostoProduct) && is_object($nostoProduct)}
		<div class="nosto_product" style="display: none">
			<span class="url">{$nostoProduct->getUrl()|escape:'htmlall':'UTF-8'}</span>
			<span class="product_id">{$nostoProduct->getProductId()|escape:'htmlall':'UTF-8'}</span>
			<span class="name">{$nostoProduct->getName()|escape:'htmlall':'UTF-8'}</span>
			<span class="image_url">{$nostoProduct->getImageUrl()|escape:'htmlall':'UTF-8'}</span>
			<span class="price">{$nostoProduct->getPrice()|escape:'htmlall':'UTF-8'}</span>
			<span class="list_price">{$nostoProduct->getListPrice()|escape:'htmlall':'UTF-8'}</span>
			<span class="price_currency_code">{$nostoProduct->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
			<span class="availability">{$nostoProduct->getAvailability()|escape:'htmlall':'UTF-8'}</span>
			<span class="description">{$nostoProduct->getDescription()|escape:'htmlall':'UTF-8'}</span>
			<span class="brand">{$nostoProduct->getBrand()|escape:'htmlall':'UTF-8'}</span>
			<span class="date_published">{$nostoProduct->getDatePublished()|escape:'htmlall':'UTF-8'}</span>
			{foreach from=$nostoProduct->getCategories() item=category}
				<span class="category">{$category|escape:'htmlall':'UTF-8'}</span>
			{/foreach}
			{foreach from=$nostoProduct->getTags() item=tag}
				<span class="tag1">{$tag|escape:'htmlall':'UTF-8'}</span>
			{/foreach}
		</div>
	{/if}
	{if isset($nostoCategory) && is_object($nostoCategory)}
		<div class="nosto_category" style="display:none">{$nostoCategory->getCategoryPath()|escape:'htmlall':'UTF-8'}</div>
	{/if}
{/block}