<?php

declare(strict_types=1);

namespace App\Infrastructure\Notebook;

use App\Application\Notebook\Exception\UnreadableNotebook;
use App\Application\Notebook\HandwritingRecognizer;
use App\Application\Notebook\NotebookPage;
use App\Domain\Notebook\RecognizedLine;
use App\Domain\Notebook\RecognizedPage;
use App\Infrastructure\Http\Json;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class KrakenRecognizer implements HandwritingRecognizer
{
    private const int TIMEOUT_SECONDS = 170;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'HTR_URL')]
        private string $url,
    ) {
    }

    public function recognize(NotebookPage $page): RecognizedPage
    {
        set_time_limit(self::TIMEOUT_SECONDS + 10);

        try {
            $response = $this->httpClient->request('POST', rtrim($this->url, '/').'/recognize', [
                'headers' => ['Content-Type' => $page->mediaType],
                'body' => $page->content,
                'timeout' => self::TIMEOUT_SECONDS,
                'max_duration' => self::TIMEOUT_SECONDS,
            ]);
            $payload = Json::object($response->toArray(false));
            if (200 !== $response->getStatusCode()) {
                throw new UnreadableNotebook(Json::string($payload['error'] ?? $response->getStatusCode()));
            }
        } catch (ExceptionInterface $exception) {
            throw new UnreadableNotebook($exception->getMessage());
        }

        return new RecognizedPage(array_map(
            static fn (array $line): RecognizedLine => new RecognizedLine(Json::string($line['text'] ?? ''), (float) Json::number($line['top'] ?? 0)),
            Json::objects($payload['lines'] ?? []),
        ));
    }
}
