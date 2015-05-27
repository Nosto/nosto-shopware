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

{block name="frontend_index_header_javascript" append}
{if isset($nostoAccountName) && isset($nostoAccountName)}
	<script type="text/javascript">
		//<![CDATA[
		{literal}(function(){function a(a){var b,c,d=window.document.createElement("iframe");d.src="javascript:false",(d.frameElement||d).style.cssText="width: 0; height: 0; border: 0";var e=window.document.createElement("div");e.style.display="none";var f=window.document.createElement("div");e.appendChild(f),window.document.body.insertBefore(e,window.document.body.firstChild),f.appendChild(d);try{c=d.contentWindow.document}catch(g){b=document.domain,d.src="javascript:var d=document.open();d.domain='"+b+"';void(0);",c=d.contentWindow.document}return c.open()._l=function(){b&&(this.domain=b);var c=this.createElement("scr".concat("ipt"));c.src=a,this.body.appendChild(c)},c.write("<bo".concat('dy onload="document._l();">')),c.close(),d}var b="nostojs";window[b]=window[b]||function(a){(window[b].q=window[b].q||[]).push(a)},window[b].l=new Date;var c=function(d,e){if(!document.body)return setTimeout(function(){c(d,e)},30);e=e||{},window[b].o=e;var f=document.location.protocol,g=["https:"===f?f:"http:","//",e.host||"connect.nosto.com",e.path||"/include/",d].join("");a(g)};window[b].init=c})();{/literal}
		nostojs.init("{$nostoAccountName|escape:"javascript":"UTF-8"}", {literal}{host:{/literal} "{$nostoServerUrl|escape:"javascript":"UTF-8"}"{literal}}{/literal});
		//]]>
	</script>
	<script type="text/javascript">
		//<![CDATA[
		{literal}
		if (typeof Nosto === "undefined") {
			var Nosto = {};
		}
		{/literal}
		Nosto.addProductToCart = function (productNumber) {
			var form = document.createElement("form");
			form.setAttribute("method", "post");
			form.setAttribute("action", "{url controller=checkout action=addArticle}");

			var hiddenFields = {
				"sActionIdentifier": "{$sUniqueRand}",
				"sAdd": productNumber,
				"sQuantity": 1
			};

			for(var key in hiddenFields) {
				if(hiddenFields.hasOwnProperty(key)) {
					var hiddenField = document.createElement("input");
					hiddenField.setAttribute("type", "hidden");
					hiddenField.setAttribute("name", key);
					hiddenField.setAttribute("value", hiddenFields[key]);
					form.appendChild(hiddenField);
				}
			}

			document.body.appendChild(form);
			form.submit();
		};
		//]]>
	</script>
{/if}
{/block}
{block name="frontend_index_content" append}
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