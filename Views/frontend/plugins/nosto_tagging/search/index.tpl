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

{block name="frontend_index_content" prepend}
    <div class="nosto_element" id="nosto-page-search1"></div>
{/block}

{block name="frontend_index_content" append}
    <div class="nosto_element" id="nosto-page-search2"></div>
	{if isset($nostoSearch) && is_object($nostoSearch)}
		<div class="nosto_search_term" style="display:none">{$nostoSearch->getSearchTerm()|escape:'htmlall':'UTF-8'}</div>
	{/if}
{/block}