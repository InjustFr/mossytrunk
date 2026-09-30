<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use App\Application\Identity\AccountMailer;
use App\Domain\Identity\PasswordToken;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SymfonyAccountMailer implements AccountMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urls,
        private TranslatorInterface $translator,
        private LocaleSwitcher $locales,
        #[Autowire(env: 'MAILER_FROM')]
        private string $from,
    ) {
    }

    public function sendInvitation(PasswordToken $passwordToken, string $token): void
    {
        $this->send($passwordToken, $token, 'invitation.subject', 'email/invitation.html.twig');
    }

    public function sendPasswordReset(PasswordToken $passwordToken, string $token): void
    {
        $this->send($passwordToken, $token, 'password_reset.subject', 'email/password_reset.html.twig');
    }

    private function send(PasswordToken $passwordToken, string $token, string $subjectKey, string $template): void
    {
        $user = $passwordToken->user();
        $locale = $user->language()->value ?? $this->locales->getLocale();

        $this->mailer->send((new TemplatedEmail())
            ->from(new Address($this->from, 'MossyTrunk'))
            ->to($user->email())
            ->locale($locale)
            ->subject($this->translator->trans($subjectKey, domain: 'emails', locale: $locale))
            ->htmlTemplate($template)
            ->context([
                'url' => $this->urls->generate('password_set_link', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                'workspace' => $user->workspace()->name(),
                'expiresAt' => $passwordToken->expiresAt(),
                'locale' => $locale,
            ]));
    }
}
