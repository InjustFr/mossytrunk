<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProductType;

use App\Application\Product\Variants\VariantUsage;
use App\Application\Transaction;
use App\Domain\Product\Exception\TypeAlreadyExists;
use App\Domain\Product\Exception\TypeCodeAlreadyUsed;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Product\VariantLabel;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private VariantUsage $usage,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param list<string>|null $variants
     */
    public function __invoke(string $typeId, string $name, string $color, ?string $code = null, ?array $variants = null, ?bool $prefixesNames = null): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $sameName = $this->types->findByName($name);
        if (null !== $sameName && !$sameName->id()->equals($type->id())) {
            throw new TypeAlreadyExists(trim($name));
        }

        $type->rename($name);
        $type->recolor($color);
        if (null !== $code) {
            $this->recode($type, $code);
        }
        if (null !== $variants) {
            $this->redefineVariants($type, $variants);
        }
        if (null !== $prefixesNames) {
            $type->prefixNames($prefixesNames);
        }
        $this->transaction->commit();
    }

    /**
     * @param list<string> $variants
     */
    private function redefineVariants(ProductType $type, array $variants): void
    {
        foreach ($type->variants() as $variant) {
            if (null === VariantLabel::find($variants, $variant)) {
                $this->usage->assertUnused($type, $variant);
            }
        }
        $type->defineVariants($variants);
    }

    private function recode(ProductType $type, string $code): void
    {
        $code = strtoupper(trim($code));
        if ($code !== $type->code() && $this->types->codeExists($code)) {
            throw new TypeCodeAlreadyUsed($code);
        }

        $type->recode($code);
    }
}
