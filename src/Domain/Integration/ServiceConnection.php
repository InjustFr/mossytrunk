<?php

declare(strict_types=1);

namespace App\Domain\Integration;

use App\Domain\Identity\Workspace;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'service_connection')]
#[ORM\UniqueConstraint(name: 'service_connection_workspace_service', columns: ['workspace_id', 'service'])]
class ServiceConnection
{
    public const string SERVICE_PATTERN = '/^[a-z0-9]{2,32}$/';

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 32)]
    private string $service;

    /** @var array<string, string> */
    #[ORM\Column(type: Types::JSON)]
    private array $settings = [];

    #[ORM\Column(length: 16, enumType: SalesContext::class)]
    private SalesContext $salesContext;

    #[ORM\Column(length: 16, enumType: UnknownItems::class)]
    private UnknownItems $unknownItems;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $accountId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountName = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, string> $settings
     */
    private function __construct(Workspace $workspace, string $service, array $settings, SalesContext $salesContext, UnknownItems $unknownItems)
    {
        if (1 !== preg_match(self::SERVICE_PATTERN, $service) || 'manual' === $service) {
            throw InvalidConnection::invalidService($service);
        }

        $this->id = new Ulid();
        $this->workspace = $workspace;
        $this->service = $service;
        $this->createdAt = new \DateTimeImmutable();
        $this->configure($settings);
        $this->choose($salesContext, $unknownItems);
    }

    /**
     * @param array<string, string> $settings
     */
    public static function create(Workspace $workspace, string $service, array $settings, SalesContext $salesContext, UnknownItems $unknownItems): self
    {
        return new self($workspace, $service, $settings, $salesContext, $unknownItems);
    }

    /**
     * @param array<string, string> $settings
     */
    public function configure(array $settings): bool
    {
        $cleaned = [];
        foreach ($settings as $name => $value) {
            if (1 !== preg_match('/^[a-z0-9_]{1,40}$/', $name)) {
                throw InvalidConnection::invalidSetting($name);
            }
            $value = trim($value);
            if ('' !== $value) {
                $cleaned[$name] = $value;
            }
        }
        ksort($cleaned);

        $changed = $cleaned !== $this->settings;
        $this->settings = $cleaned;

        return $changed;
    }

    public function choose(SalesContext $salesContext, UnknownItems $unknownItems): void
    {
        $this->salesContext = $salesContext;
        $this->unknownItems = $unknownItems;
    }

    public function authorize(string $accountId, string $accountName, \DateTimeImmutable $tokenExpiresAt): void
    {
        $this->accountId = $accountId;
        $this->accountName = mb_substr($accountName, 0, 255);
        $this->tokenExpiresAt = $tokenExpiresAt;
    }

    public function renewToken(\DateTimeImmutable $tokenExpiresAt): void
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
    }

    public function revoke(): void
    {
        $this->accountId = null;
        $this->accountName = null;
        $this->tokenExpiresAt = null;
    }

    public function isAuthorized(): bool
    {
        return null !== $this->accountId;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function workspace(): Workspace
    {
        return $this->workspace;
    }

    public function service(): string
    {
        return $this->service;
    }

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        return $this->settings;
    }

    public function setting(string $name): ?string
    {
        return $this->settings[$name] ?? null;
    }

    public function salesContext(): SalesContext
    {
        return $this->salesContext;
    }

    public function unknownItems(): UnknownItems
    {
        return $this->unknownItems;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
    }

    public function accountName(): ?string
    {
        return $this->accountName;
    }

    public function tokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }
}
