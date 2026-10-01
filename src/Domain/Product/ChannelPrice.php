<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Sales\SalesChannel;
use App\Domain\Shared\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'product_channel_price')]
#[ORM\UniqueConstraint(name: 'product_channel_price_unique', columns: ['product_id', 'channel_id'])]
class ChannelPrice
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'channelPrices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: SalesChannel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SalesChannel $channel;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'price_')]
    private Money $price;

    public function __construct(Product $product, SalesChannel $channel, Money $price)
    {
        $this->id = new Ulid();
        $this->product = $product;
        $this->channel = $channel;
        $this->price = $price;
    }

    public function change(Money $price): void
    {
        $this->price = $price;
    }

    public function isOn(SalesChannel $channel): bool
    {
        return $this->channel->id()->equals($channel->id());
    }

    public function channel(): SalesChannel
    {
        return $this->channel;
    }

    public function price(): Money
    {
        return $this->price;
    }
}
