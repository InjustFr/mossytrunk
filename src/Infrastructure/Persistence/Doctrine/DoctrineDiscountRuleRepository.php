<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Discount\DiscountRule;
use App\Domain\Discount\DiscountRuleRepository;
use App\Domain\Discount\ProductTarget;
use App\Domain\Discount\TypeTarget;
use App\Domain\Product\Product;
use App\Domain\Product\ProductType;
use App\Domain\Shared\Exception\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
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
        $rules = $this->entityManager->createQueryBuilder()
            ->select('r', 'c', 't')
            ->from(DiscountRule::class, 'r')
            ->leftJoin('r.conditions', 'c')
            ->leftJoin('c.targets', 't')
            ->where('r.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->orderBy('r.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();

        $this->loadTargetedProducts();
        $this->loadTargetedTypes();

        return $rules;
    }

    private function loadTargetedProducts(): void
    {
        $this->entityManager->createQueryBuilder()
            ->select('p', 'pt')
            ->from(Product::class, 'p')
            ->join('p.type', 'pt')
            ->where('p.workspace = :workspace')
            ->andWhere('p.id IN (SELECT IDENTITY(target.product) FROM '.ProductTarget::class.' target)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    private function loadTargetedTypes(): void
    {
        $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(ProductType::class, 't')
            ->where('t.workspace = :workspace')
            ->andWhere('t.id IN (SELECT IDENTITY(target.type) FROM '.TypeTarget::class.' target)')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->getQuery()
            ->getResult();
    }
}
