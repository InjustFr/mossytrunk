<?php

declare(strict_types=1);

namespace App\Infrastructure\SumUp;

use App\Application\SumUp\SumUpCredentials;
use App\Application\SumUp\SumUpGateway;
use App\Application\SumUp\SumUpTransaction;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SumUp stand-in for the test env (functional tests and Playwright): serves transactions from a
 * JSON fixture in the SumUp API format, or the ones given with {@see self::willReturn()}.
 */
final class FakeSumUpGateway implements SumUpGateway
{
    /** @var list<SumUpTransaction>|null */
    private ?array $transactions = null;

    public function __construct(
        private readonly SumUpPayloadMapper $mapper,
        #[Autowire('%kernel.project_dir%/tests/Fixtures/sumup/transactions.json')]
        private readonly string $fixture,
    ) {
    }

    /**
     * @param list<SumUpTransaction> $transactions
     */
    public function willReturn(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function successfulPayments(SumUpCredentials $credentials): iterable
    {
        if (null !== $this->transactions) {
            return $this->transactions;
        }

        $payload = json_decode((string) file_get_contents($this->fixture), true, flags: \JSON_THROW_ON_ERROR);

        return array_map($this->mapper->transaction(...), SumUpJson::objects(SumUpJson::object($payload)['items'] ?? []));
    }
}
