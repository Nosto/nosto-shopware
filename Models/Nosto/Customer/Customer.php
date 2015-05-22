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
	 * @var string $name
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="session_id", type="string", length=255, nullable=false)
	 */
	private $session_id;

	/**
	 * @var string $name
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="nosto_id", type="string", length=255, nullable=false)
	 */
	private $nosto_id;

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
		return $this->session_id;
	}

	/**
	 * @param string $id
	 * @return Customer
	 */
	public function setSessionId($id)
	{
		$this->session_id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getNostoId()
	{
		return $this->nosto_id;
	}

	/**
	 * @param string $id
	 * @return Customer
	 */
	public function setNostoId($id)
	{
		$this->nosto_id = $id;
		return $this;
	}
}
