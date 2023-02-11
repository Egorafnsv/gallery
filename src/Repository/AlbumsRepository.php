<?php

namespace App\Repository;

use App\Entity\Albums;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Albums>
 *
 * @method Albums|null find($id, $lockMode = null, $lockVersion = null)
 * @method Albums|null findOneBy(array $criteria, array $orderBy = null)
 * @method Albums[]    findAll()
 * @method Albums[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Albums::class);
    }

    public function add(Albums $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Albums $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findAllExceptOwner(int $id): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $psql = '
            SELECT * FROM albums a
            WHERE a.owner_id != :id
            ORDER BY a.created_at
            ';
        $stmt = $conn->prepare($psql);
        $resultSet = $stmt->executeQuery(['id' => $id]);

        return $resultSet->fetchAllAssociative();
    }

    public function findUserAlbumsExceptCurrent(int $owner_id, int $album_id): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $psql = '
            SELECT * FROM albums a
            WHERE a.owner_id = :owner_id AND a.id != :album_id
            ORDER BY a.created_at
            ';
            
        $stmt = $conn->prepare($psql);
        $resultSet = $stmt->executeQuery(['owner_id' => $owner_id, 'album_id' => $album_id]);

        return $resultSet->fetchAllAssociative();
    }

//    /**
//     * @return Albums[] Returns an array of Albums objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Albums
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
