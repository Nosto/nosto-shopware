<?php

namespace Shopware\CustomModels\Nosto\Account;

use Symfony\Component\Validator\Constraints as Assert,
	Shopware\Components\Model\ModelEntity,
	Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_nostotagging_account")
 */
class Account extends ModelEntity {
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
	 * @var integer $shop_id
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="shop_id", type="integer", nullable=false)
	 */
	private $shop_id;

	/**
	 * @var string $name
	 *
	 * @Assert\NotBlank
	 *
	 * @ORM\Column(name="name", type="string", length=255, nullable=false)
	 */
	private $name;

	/**
	 * @var string $data
	 *
	 * @ORM\Column(name="data", type="text", nullable=true)
	 */
	private $data;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $shop_id
	 * @return Account
	 */
	public function setShopId($shop_id)
	{
		$this->shop_id = $shop_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getShopId()
	{
		return $this->shop_id;
	}

	/**
	 * @param string $name
	 * @return Account
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param array $data
	 * @return Account
	 */
	public function setData($data)
	{
		$this->data = json_encode($data);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return json_decode($this->data, true);
	}
}
