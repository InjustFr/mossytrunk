<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Identity\CreateUser\CreateUser;
use App\Application\Identity\CreateUser\CreateUserHandler;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:create', description: 'Creates a user and emails them a link to choose their password')]
final readonly class CreateUserCommand
{
    public function __construct(private CreateUserHandler $createUser)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email address (login)')]
        string $email,
        #[Option(description: 'Workspace to join, created if it does not exist')]
        string $workspace = '',
    ): int {
        if ('' === trim($workspace)) {
            $io->error('The --workspace option is required.');

            return Command::INVALID;
        }

        try {
            $user = ($this->createUser)(new CreateUser($email, $workspace));
        } catch (DomainException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('User %s created in workspace "%s"; invitation sent.', $user->email(), $user->workspace()->name()));

        return Command::SUCCESS;
    }
}
