<?php
namespace Shopware\CustomModels\Nosto\Setting;

use Symfony\Component\Validator\Constraints as Assert,
    Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="Repository")
 * @ORM\Table(name="s_nostotagging_setting")
 */
class Setting extends ModelEntity {
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string $value
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Setting
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->name;
    }

    /**
     * @param string $value
     * @return Setting
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
