<?php
/**
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
 */

namespace Shopware\CustomModels\Nosto\Customer;

use Symfony\Component\Validator\Constraints as Assert,
	Shopware\Components\Model\ModelEntity,
	Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_nostotagging_customer")
 */
class Customer extends ModelEntity
{
	/**
	 * @var integer $id
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var string $sessionId
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="session_id", type="string", length=255, nullable=false)
	 */
	private $sessionId;

	/**
	 * @var string $nostoId
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="nosto_id", type="string", length=255, nullable=false)
	 */
	private $nostoId;

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
}
