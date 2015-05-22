{if isset($nostoAccountName) && isset($nostoAccountName)}
{block name="frontend_index_header_javascript" append}
	<script type="text/javascript">
		//<![CDATA[
		{literal}(function(){function a(a){var b,c,d=window.document.createElement("iframe");d.src="javascript:false",(d.frameElement||d).style.cssText="width: 0; height: 0; border: 0";var e=window.document.createElement("div");e.style.display="none";var f=window.document.createElement("div");e.appendChild(f),window.document.body.insertBefore(e,window.document.body.firstChild),f.appendChild(d);try{c=d.contentWindow.document}catch(g){b=document.domain,d.src="javascript:var d=document.open();d.domain='"+b+"';void(0);",c=d.contentWindow.document}return c.open()._l=function(){b&&(this.domain=b);var c=this.createElement("scr".concat("ipt"));c.src=a,this.body.appendChild(c)},c.write("<bo".concat('dy onload="document._l();">')),c.close(),d}var b="nostojs";window[b]=window[b]||function(a){(window[b].q=window[b].q||[]).push(a)},window[b].l=new Date;var c=function(d,e){if(!document.body)return setTimeout(function(){c(d,e)},30);e=e||{},window[b].o=e;var f=document.location.protocol,g=["https:"===f?f:"http:","//",e.host||"connect.nosto.com",e.path||"/include/",d].join("");a(g)};window[b].init=c})();{/literal}
		nostojs.init("{$nostoAccountName|escape:"javascript":"UTF-8"}", {literal}{host:{/literal} "{$nostoServerUrl|escape:"javascript":"UTF-8"}"{literal}}{/literal});
		//]]>
	</script>
{/block}
{/if}
{block name="frontend_index_footer" append}
	{if isset($nostoCustomer) && is_object($nostoCustomer)}
		<div class="nosto_customer" style="display:none">
			<span class="first_name">{$nostoCustomer->getFirstName()|escape:'htmlall':'UTF-8'}</span>
			<span class="last_name">{$nostoCustomer->getLastName()|escape:'htmlall':'UTF-8'}</span>
			<span class="email">{$nostoCustomer->getEmail()|escape:'htmlall':'UTF-8'}</span>
		</div>
	{/if}
	<div class="nosto_cart" style="display:none">
		{if isset($nostoCart) && is_object($nostoCart)}
		{foreach from=$nostoCart->getLineItems() item=lineItem}
			<div class="line_item">
				<span class="product_id">{$lineItem->getProductId()|escape:'htmlall':'UTF-8'}</span>
				<span class="quantity">{$lineItem->getQuantity()|escape:'htmlall':'UTF-8'}</span>
				<span class="name">{$lineItem->getName()|escape:'htmlall':'UTF-8'}</span>
				<span class="unit_price">{$lineItem->getUnitPrice()|escape:'htmlall':'UTF-8'}</span>
				<span class="price_currency_code">{$lineItem->getCurrencyCode()|escape:'htmlall':'UTF-8'}</span>
			</div>
		{/foreach}
		{/if}
	</div>
{/block}