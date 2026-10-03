<?php

declare(strict_types=1);

namespace App\Infrastructure\Notebook;

use App\Application\Notebook\NotebookCatalogue;
use App\Application\Notebook\NotebookPage;
use App\Application\Notebook\NotebookReader;
use App\Domain\Notebook\NotebookEntry;
use App\Infrastructure\Http\Json;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FakeNotebookReader implements NotebookReader
{
    /** @var list<NotebookEntry>|null */
    private ?array $entries = null;

    /** @var list<NotebookPage> */
    private array $pagesRead = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/tests/Fixtures/notebook/entries.json')]
        private readonly string $fixture,
    ) {
    }

    /**
     * @param list<NotebookEntry> $entries
     */
    public function willRead(array $entries): void
    {
        $this->entries = $entries;
    }

    /**
     * @return list<NotebookPage>
     */
    public function pagesRead(): array
    {
        return $this->pagesRead;
    }

    public function read(array $pages, NotebookCatalogue $catalogue): array
    {
        $this->pagesRead = $pages;
        if (null !== $this->entries) {
            return $this->entries;
        }

        $fixture = Json::object(json_decode((string) file_get_contents($this->fixture), true, flags: \JSON_THROW_ON_ERROR));
        $entries = array_map(static fn (array $entry): array => [
            'page' => $entry['page'] ?? 1,
            'lines' => array_map(static fn (array $line): array => [
                ...$line,
                'productId' => $catalogue->productNamed(Json::string($line['product'] ?? '')),
                'typeId' => null,
            ], Json::objects($entry['lines'] ?? [])),
        ], Json::objects($fixture['entries'] ?? []));

        return NotebookAnswer::entries(['entries' => $entries], $catalogue);
    }
}
