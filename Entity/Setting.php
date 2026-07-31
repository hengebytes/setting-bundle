<?php

namespace Hengebytes\SettingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Hengebytes\SettingBundle\Repository\SettingRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[UniqueEntity("name")]
#[ORM\Table(name: 'hb_settings')]
class Setting
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(unique: true)]
    public string $name;

    #[ORM\Column(type: Types::TEXT)]
    public string $value;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isSensitive = false;
}
