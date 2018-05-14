<?php
/**
 * Copyright (c) 2018, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2018 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Shopware_Plugins_Frontend_NostoTagging_Components_Account as NostoComponentAccount;
use Nosto\Object\Signup\Account as NostoAccount;
use Nosto\Operation\DeleteProduct;
use Nosto\Operation\UpsertProduct;
use Shopware\Models\Article\Article;
use Shopware\Models\Shop\Shop;
use Shopware_Plugins_Frontend_NostoTagging_Components_Model_Product as Product;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Category\Category;
use Nosto\NostoException;

/**
 * Product operation component. Used for communicating create/update/delete
 * events for products to Nosto.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Operation_Product
{
    /**
     * Sends info to Nosto about a newly created product.
     *
     * @param Article $article the product.
     * @throws Exception
     * @suppress PhanDeprecatedFunction
     */
    public function create(Article $article)
    {
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        foreach ($this->getAccounts($article) as $shopId => $account) {
            $shop = $repository->getActiveById($shopId);
            if ($shop instanceof Shop === false) {
                continue;
            }
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            $model = new Product();
            $model->loadData($article, $shop);
            if ($model->getProductId()) {
                try {
                    $op = new UpsertProduct($account);
                    $op->addProduct($model);
                    $op->upsert();
                } catch (\Exception $e) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
                }
            }
        }
    }

    /**
     * Returns the Nosto accounts for the product mapped on the shop ID to which
     * they belong.
     *
     * The shops the product belongs to is determined by analyzing the products
     * categories and checking if the shop root category is present.
     *
     * @param Article $article the article model.
     * @param boolean $allStores if true Nosto accounts from all stores will be returned
     * @return NostoAccount[] the accounts mapped in the shop IDs.
     */
    protected function getAccounts(Article $article, $allStores = false)
    {
        $data = array();

        /** @var Shop[] $inShops */
        $inShops = array();
        $allShops = Shopware()
            ->Models()
            ->getRepository(Shop::class)
            ->findAll();

        if ($allStores === true) {
            $inShops = $allShops;
        } else {
            /** @var Category $cat */
            foreach ($article->getCategories() as $cat) {
                /** @var Shop $shop */
                foreach ($allShops as $shop) {
                    if (isset($inShops[$shop->getId()])) {
                        continue;
                    }

                    $shopCatId = $shop->getCategory()->getId();
                    if ($cat->getId() === $shopCatId
                        || strpos($cat->getPath(), '|' . $shopCatId . '|') !== false
                    ) {
                        $inShops[$shop->getId()] = $shop;
                    }
                }
            }
        }

        foreach ($inShops as $shop) {
            $account = NostoComponentAccount::findAccount($shop);
            if ($account !== null) {
                $nostoAccount = NostoComponentAccount::convertToNostoAccount($account);
                if ($nostoAccount->isConnectedToNosto()) {
                    $data[$shop->getId()] = $nostoAccount;
                }
            }
        }

        return $data;
    }

    /**
     * Sends info to Nosto about a newly updated product.
     *
     * @param \Shopware\Models\Article\Article $article the product.
     * @throws Exception
     * @suppress PhanDeprecatedFunction
     */
    public function update(Article $article)
    {
        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        foreach ($this->getAccounts($article) as $shopId => $account) {
            $shop = $repository->getActiveById($shopId);
            if ($shop instanceof Shop === false) {
                continue;
            }
            /** @noinspection PhpDeprecationInspection */
            $shop->registerResources(Shopware()->Bootstrap());
            $model = new Product();
            $model->loadData($article, $shop);
            if ($model->getProductId()) {
                try {
                    $op = new UpsertProduct($account);
                    $op->addProduct($model);
                    $op->upsert();
                } catch (\Exception $e) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
                }
            }
        }
    }

    /**
     * Sends info to Nosto about a deleted product.
     *
     * @param Article $article the product.
     * @throws NostoException
     */
    public function delete(Article $article)
    {
        foreach ($this->getAccounts($article, true) as $account) {
            $model = new Product();
            $model->assignId($article);
            if ($model->getProductId()) {
                $op = new DeleteProduct($account);
                $products = array($model->getProductId());
                $op->setProductIds($products);
                try {
                    $op->delete();
                } catch (\Exception $e) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    Shopware()->Plugins()->Frontend()->NostoTagging()->getLogger()->error($e->getMessage());
                }
            }
        }
    }
}
