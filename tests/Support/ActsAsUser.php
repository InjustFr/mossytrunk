<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Identity\User;
use App\Domain\Identity\Workspace;
use App\Domain\Sales\SalesChannel;
use App\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Ulid;

trait ActsAsUser
{
    protected static function createMember(string $workspaceName = 'Atelier', ?string $email = null): User
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $workspace = $entityManager->getRepository(Workspace::class)->findOneBy(['name' => $workspaceName]);
        if (null === $workspace) {
            $workspace = Workspace::create($workspaceName);
            $entityManager->persist(SalesChannel::main($workspace, 'Marchés'));
        }
        $id = strtolower((string) new Ulid());
        $user = User::join($id, $email ?? \sprintf('%s@mossytrunk.test', $id), $workspace);
        $entityManager->persist($workspace);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    protected static function createMemberFromBeforeAccounts(string $workspaceName, string $email): User
    {
        $user = self::createMember($workspaceName, $email);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('UPDATE app_user SET account_id = NULL WHERE id = ?', [$user->id()->toRfc4122()]);
        $entityManager->clear();

        return $user;
    }

    protected static function actAs(User $user): void
    {
        $securityUser = SecurityUser::fromUser($user);
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($securityUser, 'main', $securityUser->getRoles()));
    }

    protected static function actAsMemberOf(string $workspaceName = 'Atelier'): User
    {
        $user = self::createMember($workspaceName);
        self::actAs($user);

        return $user;
    }
}
