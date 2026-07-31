<?php

namespace Hengebytes\SettingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hengebytes\SettingBundle\Entity\Setting;

/**
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends EntityRepository
{
    /**
     * @param string $startName
     * @return array<Setting>
     */
    public function getSettingsStartWith(string $startName): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.name LIKE :startName')
            ->setParameter('startName', $startName . '%');

        return $qb->getQuery()->getResult();
    }
}
