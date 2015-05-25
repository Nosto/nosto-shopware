<?php
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
