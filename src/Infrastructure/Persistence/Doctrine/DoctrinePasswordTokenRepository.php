<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Identity\PasswordToken;
use App\Domain\Identity\PasswordTokenRepository;
use App\Domain\Identity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePasswordTokenRepository implements PasswordTokenRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(PasswordToken $token): void
    {
        $this->entityManager->persist($token);
    }

    public function findBySelector(string $selector): ?PasswordToken
    {
        return $this->entityManager->getRepository(PasswordToken::class)->findOneBy(['selector' => $selector]);
    }

    public function removeAllFor(User $user): void
    {
        foreach ($this->entityManager->getRepository(PasswordToken::class)->findBy(['user' => $user]) as $token) {
            $this->entityManager->remove($token);
        }
    }
}
