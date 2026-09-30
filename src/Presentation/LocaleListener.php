<?php

declare(strict_types=1);

namespace App\Presentation;

use App\Application\Identity\LanguagePreference;
use App\Domain\Identity\Language;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class LocaleListener
{
    public const string COOKIE = 'locale';

    public function __construct(
        private LanguagePreference $preference,
        private LocaleSwitcher $locales,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 30)]
    public function fromBrowser(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->apply($event->getRequest(), $this->browserLocaleOf($event->getRequest()));
        }
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 7)]
    public function fromSignedInUser(RequestEvent $event): void
    {
        $language = $this->preference->ofSignedInUser();
        if ($event->isMainRequest() && null !== $language) {
            $this->apply($event->getRequest(), $language->value);
        }
    }

    private function apply(Request $request, string $locale): void
    {
        $request->setLocale($locale);
        $this->locales->setLocale($locale);
    }

    private function browserLocaleOf(Request $request): string
    {
        return Language::tryFrom($request->cookies->getString(self::COOKIE))->value
            ?? $request->getPreferredLanguage(Language::codes())
            ?? Language::DEFAULT->value;
    }
}
