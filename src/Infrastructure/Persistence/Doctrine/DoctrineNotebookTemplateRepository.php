<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\WorkspaceContext;
use App\Domain\Notebook\NotebookTemplate;
use App\Domain\Notebook\NotebookTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class DoctrineNotebookTemplateRepository implements NotebookTemplateRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkspaceContext $workspace,
    ) {
    }

    public function add(NotebookTemplate $template): void
    {
        $this->entityManager->persist($template);
    }

    public function current(): ?NotebookTemplate
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(NotebookTemplate::class, 't')
            ->where('t.workspace = :workspace')
            ->setParameter('workspace', $this->workspace->current()->id(), UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof NotebookTemplate ? $result : null;
    }
}
