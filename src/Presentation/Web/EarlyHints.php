<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;

final readonly class EarlyHints
{
    private const array FONTS = [
        'build/fonts/inter-latin-wght-normal.woff2',
        'build/fonts/patua-one-latin-400-normal.woff2',
    ];

    public function __construct(
        private EntrypointLookupCollectionInterface $entrypoints,
        private Packages $assets,
        private HttpHeaderSerializer $serializer,
    ) {
    }

    public function send(): void
    {
        $hints = new Response(status: Response::HTTP_EARLY_HINTS);
        $hints->headers->set('Link', $this->serializer->serialize(new \ArrayIterator($this->links())));
        $hints->sendHeaders(Response::HTTP_EARLY_HINTS);
    }

    /**
     * @return list<Link>
     */
    private function links(): array
    {
        $entrypoint = $this->entrypoints->getEntrypointLookup();
        $files = ['style' => $entrypoint->getCssFiles('app'), 'script' => $entrypoint->getJavaScriptFiles('app')];
        $entrypoint->reset();

        $links = [];
        foreach ($files as $as => $paths) {
            foreach ($paths as $path) {
                if (\is_string($path)) {
                    $links[] = (new Link('preload', $path))->withAttribute('as', $as);
                }
            }
        }
        foreach (self::FONTS as $font) {
            $links[] = (new Link('preload', $this->assets->getUrl($font)))->withAttribute('as', 'font')->withAttribute('type', 'font/woff2')->withAttribute('crossorigin', true);
        }

        return $links;
    }
}
