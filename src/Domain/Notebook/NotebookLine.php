<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

use App\Domain\Notebook\Exception\NonPositiveNotebookQuantity;
use Symfony\Component\Uid\Ulid;

final readonly class NotebookLine
{
    public function __construct(
        public string $written,
        public int $quantity,
        public string $label,
        public ?Ulid $productId = null,
        public ?string $variant = null,
        public ?Ulid $typeId = null,
    ) {
        if ($quantity < 1) {
            throw new NonPositiveNotebookQuantity($written);
        }
    }

    public function namesProduct(): bool
    {
        return null !== $this->productId;
    }

    public function namesOnlyType(): bool
    {
        return null === $this->productId && null !== $this->typeId;
    }

    public function accepts(RecordedLine $line): bool
    {
        if (null !== $this->productId) {
            return null !== $line->productId && $this->productId->equals($line->productId) && (null === $this->variant || $this->variant === $line->variant);
        }

        return null !== $this->typeId && null !== $line->typeId && $this->typeId->equals($line->typeId);
    }

    /**
     * @return array{written: string, quantity: int, label: string, productId: ?string, variant: ?string, typeId: ?string}
     */
    public function toArray(): array
    {
        return [
            'written' => $this->written,
            'quantity' => $this->quantity,
            'label' => $this->label,
            'productId' => $this->productId?->toRfc4122(),
            'variant' => $this->variant,
            'typeId' => $this->typeId?->toRfc4122(),
        ];
    }

    /**
     * @param array{written: string, quantity: int, label: string, productId: ?string, variant: ?string, typeId: ?string} $line
     */
    public static function fromArray(array $line): self
    {
        return new self(
            $line['written'],
            $line['quantity'],
            $line['label'],
            null === $line['productId'] ? null : Ulid::fromString($line['productId']),
            $line['variant'],
            null === $line['typeId'] ? null : Ulid::fromString($line['typeId']),
        );
    }
}
