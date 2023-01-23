<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\PlanetFieldTypeRepository")
 * @Table(
 *     name="stu_colony_fieldtype",
 *     indexes={@Index(name="field_id_idx", columns={"field_id"})}
 * )
 **/
class PlanetFieldType implements PlanetFieldTypeInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $field_id = 0;

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $description = '';

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $normal_id = 0;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $category;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFieldType(): int
    {
        return $this->field_id;
    }

    public function setFieldType(int $fieldType): PlanetFieldTypeInterface
    {
        $this->field_id = $fieldType;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): PlanetFieldTypeInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getBaseFieldType(): int
    {
        return $this->normal_id;
    }

    public function setBaseFieldType(int $baseFieldType): PlanetFieldTypeInterface
    {
        $this->normal_id = $baseFieldType;

        return $this;
    }

    public function getCategory(): int
    {
        return $this->category;
    }
}
