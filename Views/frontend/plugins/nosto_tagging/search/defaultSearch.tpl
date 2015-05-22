{block name="frontend_index_content_top" prepend}
    <div class="nosto_element" id="nosto-page-search1"></div>
{/block}

{block name="frontend_index_footer" prepend}
    <div class="nosto_element" id="nosto-page-search2"></div>
	{if isset($nostoSearch) && is_object($nostoSearch)}
		<div class="nosto_search_term" style="display:none">{$nostoSearch->getSearchTerm()|escape:'htmlall':'UTF-8'}</div>
	{/if}
{/block}