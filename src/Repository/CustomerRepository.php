<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 *
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findByIndexSearchFields(string $search): array
    {
        return $this->createQueryBuilder('customer')
            ->where('customer.name LIKE :search')
            ->orWhere('customer.phone LIKE :search')
            ->orWhere('customer.location LIKE :search')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('customer.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}