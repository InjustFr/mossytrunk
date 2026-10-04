<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProductType;

use App\Application\Product\ProductTypeAvailability;
use App\Application\Product\Variants\VariantUsage;
use App\Application\Transaction;
use App\Domain\Product\ProductType;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Product\VariantLabel;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateProductTypeHandler
{
    public function __construct(
        private ProductTypeRepository $types,
        private ProductTypeAvailability $availability,
        private VariantUsage $usage,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param list<string>|null $variants
     * @param list<string>|null $archivedVariants
     */
    public function __invoke(string $typeId, string $name, string $color, ?string $code = null, ?array $variants = null, ?bool $prefixesNames = null, ?array $archivedVariants = null): void
    {
        $type = $this->types->get(Ulid::fromString($typeId));

        $this->availability->assertNameFree($name, $type);

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
        if (null !== $archivedVariants) {
            $type->archiveVariants($archivedVariants);
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
        $code = ProductType::normalizedCode($code);
        $this->availability->assertCodeFree($code, $type);
        $type->recode($code);
    }
}
