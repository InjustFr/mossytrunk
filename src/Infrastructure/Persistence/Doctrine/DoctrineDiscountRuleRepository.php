<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineDiscountRuleRepository implements DiscountRuleRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
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
        return $this->entityManager->getRepository(DiscountRule::class)->findOneBy(['id' => $id, 'workspace' => $this->workspace->current()])
            ?? throw new NotFound('discount', (string) $id);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(DiscountRule::class)->findBy(['workspace' => $this->workspace->current()], ['name' => 'ASC']);
    }
}
