<?php

declare(strict_types=1);

namespace App\Infrastructure\Notebook;

use Anthropic\Beta\Messages\BetaBase64ImageSource;
use Anthropic\Beta\Messages\BetaBase64ImageSource\MediaType;
use Anthropic\Beta\Messages\BetaImageBlockParam;
use Anthropic\Beta\Messages\BetaMessageParam;
use Anthropic\Beta\Messages\BetaTextBlock;
use Anthropic\Beta\Messages\BetaTextBlockParam;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Application\Notebook\Exception\NotebookReaderNotConfigured;
use App\Application\Notebook\Exception\UnreadableNotebook;
use App\Application\Notebook\NotebookCatalogue;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\NotebookReader;
use App\Infrastructure\Http\Json;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ClaudeNotebookReader implements NotebookReader
{
    private const string MODEL = 'claude-opus-5-5';
    private const int MAX_TOKENS = 32000;
    private const int TIME_LIMIT_SECONDS = 330;

    private const string INSTRUCTIONS = <<<'TEXT'
        You transcribe the handwritten sales notebook of a small creative business that sells at markets and conventions (prints, stickers, badges…).
        Each photo is one notebook page; photos are given in page order. Sales are written one after another; a sale usually starts with an order number (1, 2, 3…, restarting at each event) followed by what was sold, sometimes with prices, a total or a payment method.

        List every sale in the order it is written, with each item sold, and match the items to the shop's catalogue (JSON, grouped by product type).

        Rules:
        - Order numbers only help you tell sales apart: never output them and never read them as quantities. They may be wrong, skipped or repeated, so rely on the layout too.
        - Ignore prices, totals, payment methods, dates, crossed-out sales or items and any note that is not an item sold.
        - quantity: units of that item (1 when no quantity is written; "2x", "x2", "2 dragons" mean 2).
        - written: the item exactly as written, abbreviations included.
        - productId: the id of the catalogue product the item designates when you are reasonably confident, otherwise null.
        - variant: when the product has variants and the note names one, that variant spelled as in the catalogue, otherwise null.
        - typeId: the id of the product type the item belongs to (the product's type when productId is set, or the kind of item named, e.g. "sticker", "print"), otherwise null.
        - page: the 1-based number of the photo where the sale starts; a sale continued on the next page is reported once.
        - Skip photos that are not notebook pages or cannot be read.
        TEXT;

    public function __construct(
        private Client $client,
        #[Autowire(env: 'ANTHROPIC_API_KEY')]
        private string $apiKey,
    ) {
    }

    public function read(array $pages, NotebookCatalogue $catalogue): array
    {
        if ('' === $this->apiKey) {
            throw new NotebookReaderNotConfigured();
        }
        set_time_limit(self::TIME_LIMIT_SECONDS);

        try {
            $message = $this->client->beta->messages->create(
                maxTokens: self::MAX_TOKENS,
                messages: [BetaMessageParam::with(content: $this->content($pages, $catalogue), role: 'user')],
                model: self::MODEL,
                fallbacks: 'default',
                outputConfig: ['effort' => 'high', 'format' => ['type' => 'json_schema', 'schema' => NotebookAnswer::schema()]],
                system: self::INSTRUCTIONS,
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APIException $exception) {
            throw new UnreadableNotebook($exception->getMessage());
        }

        if ('end_turn' !== $message->stopReason) {
            throw new UnreadableNotebook((string) $message->stopReason);
        }

        foreach ($message->content as $block) {
            if ($block instanceof BetaTextBlock) {
                return NotebookAnswer::entries(Json::object(json_decode($block->text, true)), $catalogue);
            }
        }

        throw new UnreadableNotebook('empty answer');
    }

    /**
     * @param list<NotebookPage> $pages
     *
     * @return list<BetaTextBlockParam|BetaImageBlockParam>
     */
    private function content(array $pages, NotebookCatalogue $catalogue): array
    {
        $content = [BetaTextBlockParam::with("Catalogue:\n".json_encode($catalogue->types(), \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR))];
        foreach ($pages as $index => $page) {
            $content[] = BetaTextBlockParam::with(\sprintf('Page %d:', $index + 1));
            $content[] = BetaImageBlockParam::with(BetaBase64ImageSource::with(base64_encode($page->content), MediaType::from($page->mediaType)));
        }
        $content[] = BetaTextBlockParam::with('Transcribe the sales written on these pages.');

        return $content;
    }
}
