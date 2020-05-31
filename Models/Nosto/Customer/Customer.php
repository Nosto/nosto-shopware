<?php /** @noinspection PhpUnusedAliasInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */

/**
 * Copyright (c) 2019, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2019 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

namespace Shopware\CustomModels\Nosto\Customer;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_nostotagging_customer",uniqueConstraints={@ORM\UniqueConstraint(name="session", columns={"session_id"})},indexes={@ORM\Index(name="customerIndex", columns={"nosto_id"})})
 */
class Customer extends ModelEntity
{
    /**
     * @var int The length of the restore cart attribute
     */
    const NOSTO_TAGGING_RESTORE_CART_ATTRIBUTE_LENGTH = 64;

    /**
     * @var integer $id
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id; //@codingStandardsIgnoreLine

    /**
     * @var string $sessionId
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(name="session_id", type="string", length=255, nullable=false)
     */
    private $sessionId; //@codingStandardsIgnoreLine

    /**
     * @var string $nostoId
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(name="nosto_id", type="string", length=255, nullable=false)
     */
    private $nostoId; //@codingStandardsIgnoreLine

    /**
     * @var string $restoreCartHash
     *
     * @ORM\Column(
     *     name="restore_cart_hash",
     *     type="string",
     *     length=Customer::NOSTO_TAGGING_RESTORE_CART_ATTRIBUTE_LENGTH,
     *     nullable=true
     * )
     */
    private $restoreCartHash;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $id
     * @return Customer
     */
    public function setSessionId($id)
    {
        $this->sessionId = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getNostoId()
    {
        return $this->nostoId;
    }

    /**
     * @param string $id
     * @return Customer
     */
    public function setNostoId($id)
    {
        $this->nostoId = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestoreCartHash()
    {
        return $this->restoreCartHash;
    }

    /**
     * @param string $restoreCartHash
     */
    public function setRestoreCartHash($restoreCartHash)
    {
        $this->restoreCartHash = $restoreCartHash;
    }
}
