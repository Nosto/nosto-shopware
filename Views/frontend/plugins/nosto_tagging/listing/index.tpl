{block name="frontend_index_footer" prepend}
    <div class="nosto_element" id="nosto-page-category1"></div>
    <div class="nosto_element" id="nosto-page-category2"></div>
	{if isset($nostoCategory) && is_object($nostoCategory)}
		<div class="nosto_category" style="display:none">{$nostoCategory->getCategoryPath()|escape:'htmlall':'UTF-8'}</div>
	{/if}
{/block}