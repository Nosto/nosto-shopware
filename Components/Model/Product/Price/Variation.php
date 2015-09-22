<?php
/**
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
 */

/**
 * Model for product price variation information. This is used when compiling the info about a
 * product that is sent to Nosto if the multi currency setting is set to "priceVariation".
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Base
 * Implements NostoProductPriceVariationInterface
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product_Price_Variation extends Shopware_Plugins_Frontend_NostoTagging_Components_Base implements NostoProductPriceVariationInterface
{
	/**
	 * @var NostoPriceVariation
	 */
	protected $id;

	/**
	 * @var NostoCurrencyCode
	 */
	protected $currency;

	/**
	 * @var NostoPrice
	 */
	protected $price;

	/**
	 * @var NostoPrice
	 */
	protected $listPrice;

	/**
	 * @var NostoProductAvailability
	 */
	protected $availability;

	/**
	 * Constructor.
	 *
	 * @param \Shopware\Models\Article\Article $article
	 * @param \Shopware\Models\Shop\Currency $currency
	 * @param NostoProductAvailability $availability
	 */
	public function __construct(\Shopware\Models\Article\Article $article, Shopware\Models\Shop\Currency $currency, NostoProductAvailability $availability)
    {
		$this->id = new NostoPriceVariation($currency->getCurrency());
		$this->currency = new NostoCurrencyCode($currency->getCurrency());
        $this->price = $this->getPriceHelper()->getArticlePriceInclTax($article, $currency);
        $this->listPrice = $this->getPriceHelper()->getArticleListPriceInclTax($article, $currency);
        $this->availability = $availability;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @inheritdoc
     */
    public function getListPrice()
    {
        return $this->listPrice;
    }

    /**
     * @inheritdoc
     */
    public function getAvailability()
    {
        return $this->availability;
    }
}
