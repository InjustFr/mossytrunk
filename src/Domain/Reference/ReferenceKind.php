<?php

declare(strict_types=1);

namespace App\Domain\Reference;

enum ReferenceKind: string
{
    case Order = 'order';
    case SupplierOrder = 'supplier_order';
    case Product = 'product';

    public function defaultTemplate(): string
    {
        return match ($this) {
            self::Order => 'CMD-{date}-{random}',
            self::SupplierOrder => 'CMF-{date}-{random}',
            self::Product => '{type}-{name}',
        };
    }

    /**
     * @return list<ReferenceToken>
     */
    public function tokens(): array
    {
        $calendar = [ReferenceToken::Date, ReferenceToken::Year, ReferenceToken::Month, ReferenceToken::Day];
        $unique = [ReferenceToken::Number, ReferenceToken::Random];

        return match ($this) {
            self::Order => [...$calendar, ReferenceToken::Time, ReferenceToken::Hour, ReferenceToken::Minute, ...$unique],
            self::SupplierOrder => [...$calendar, ...$unique],
            self::Product => [ReferenceToken::Type, ReferenceToken::Name, ...$calendar, ...$unique],
        };
    }

    public function offers(ReferenceToken $token): bool
    {
        return \in_array($token, $this->tokens(), true);
    }
}
