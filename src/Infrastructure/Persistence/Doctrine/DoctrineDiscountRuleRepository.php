<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDiscountRuleRepository implements DiscountRuleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(DiscountRule $rule): void
    {
        $this->entityManager->persist($rule);
    }

    public function remove(DiscountRule $rule): void
    {
        $this->entityManager->remove($rule);
    }

    public function get(Ulid $id): DiscountRule
    {
        return $this->entityManager->find(DiscountRule::class, $id) ?? throw NotFound::entity('Remise', (string) $id);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(DiscountRule::class)->findBy([], ['name' => 'ASC']);
    }

    public function active(): array
    {
        return $this->entityManager->getRepository(DiscountRule::class)->findBy(['active' => true], ['name' => 'ASC']);
    }
}
