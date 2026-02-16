<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findByUuid(string $uuid): ?Country
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Returns all country UUIDs currently in the database.
     *
     * @return string[]
     */
    public function findAllUuids(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.uuid')
            ->getQuery()
            ->getSingleColumnResult();

        return $result;
    }

    /**
     * @return array<string, Country>
     */
    public function findAllIndexedByUuid(): array
    {
        $countries = $this->createQueryBuilder('c')
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($countries as $country) {
            $indexed[$country->getUuid()] = $country;
        }

        return $indexed;
    }

    public function deleteByUuids(array $uuids): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.uuid IN (:uuids)')
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->execute();
    }

    public function findPaginated(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
    }
}
