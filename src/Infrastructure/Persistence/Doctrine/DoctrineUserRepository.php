<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\NotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(User $user): void
    {
        $this->entityManager->persist($user);
    }

    public function get(Ulid $id): User
    {
        return $this->entityManager->find(User::class, $id) ?? throw NotFound::entity('Utilisateur', (string) $id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower(trim($email))]);
    }
}
