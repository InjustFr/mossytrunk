<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Crypto\SodiumSecretCipher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:encryption:generate-key', description: 'Prints a new APP_ENCRYPTION_KEY value')]
final readonly class GenerateEncryptionKeyCommand
{
    public function __invoke(OutputInterface $output): int
    {
        $output->writeln(SodiumSecretCipher::generateKey());

        return Command::SUCCESS;
    }
}
