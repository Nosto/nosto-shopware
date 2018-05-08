<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
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
 */

use Shopware\Models\Article\Detail;

/**
 * Class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CustomFields
 *
 * Helper used to amend custom tagging of 'Settings' and 'Free Text Fields'
 * of products both in Product and SKU Models.
 *
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Helper_CustomFields
{
    const CUSTOM_FIELD_WEIGHT = 'weight';
    const CUSTOM_FIELD_HEIGHT = 'height';
    const CUSTOM_FIELD_LENGTH = 'length';
    const CUSTOM_FIELD_WIDTH = 'width';
    const CUSTOM_FIELD_FREE_SHIPPING = 'free_shipping';

    /**
     * @var array Built in Shopware properties that should not be tagged
     */
    public static $ignoredCustomFieldsProperties = array(
        'id',
        'articleDetailId',
        'articleDetail',
        'articleId',
        'article'
    );

    /**
     * Returns an array with defined properties in the settings
     * panel of the variant/main product
     *
     * @param Detail $detail
     * @return array
     */
    public function getDetailSettingsCustomFields(Detail $detail)
    {
        $settingsCustomFields = array();

        if (!empty($detail->getWeight())) {
            $settingsCustomFields[self::CUSTOM_FIELD_WEIGHT] = $detail->getWeight();
        }
        if (!empty($detail->getWidth())) {
            $settingsCustomFields[self::CUSTOM_FIELD_WIDTH] = $detail->getWidth();
        }
        if (!empty($detail->getShippingFree())) {
            $settingsCustomFields[self::CUSTOM_FIELD_FREE_SHIPPING] = $detail->getShippingFree();
        }
        if (!empty($detail->getHeight())) {
            $settingsCustomFields[self::CUSTOM_FIELD_HEIGHT] = $detail->getHeight();
        }
        if (!empty($detail->getLen())) {
            $settingsCustomFields[self::CUSTOM_FIELD_LENGTH] = $detail->getLen();
        }

        return $settingsCustomFields;
    }

    /**
     * Returns an array with defined properties in the free text field
     * panel of the variant/main product
     * @param Detail $detail
     * @return array
     */
    public function getFreeTextCustomFields(Detail $detail)
    {
        /** @var Detail $detail */
        $propertiesAndValues = Nosto\Helper\SerializationHelper::getProperties(
            $detail->getAttribute()
        );
        $customFields = array();
        foreach ($propertiesAndValues as $property => $value) {
            if ($value !== ''
                && $value !== null
                && !in_array($property, self::$ignoredCustomFieldsProperties, true)
            ) {
                $customFields[$property] = $value;
            }
        }
        return $customFields;
    }
}
