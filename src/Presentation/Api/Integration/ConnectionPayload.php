<?php

declare(strict_types=1);

namespace App\Presentation\Api\Integration;

use App\Application\Integration\ConfigureConnection\ConnectionSettings;
use App\Application\Integration\Connectors;
use App\Application\Integration\ServiceDescription;
use App\Application\Integration\ServiceField;
use App\Domain\Integration\SalesContext;
use App\Domain\Integration\UnknownItems;
use App\Domain\Shared\Exception\NotFound;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ConnectionPayload
{
    public function __construct(
        private ValidatorInterface $validator,
        private Connectors $connectors,
        private TranslatorInterface $translator,
    ) {
    }

    public function settings(Request $request, string $service, bool $adding): ConnectionSettings
    {
        $description = $this->description($service);
        $payload = $request->getPayload();
        $raw = $payload->all('fields');

        $fields = [];
        foreach ($description->fields as $field) {
            $value = $raw[$field->name] ?? null;
            $fields[$field->name] = \is_scalar($value) ? trim((string) $value) : null;
        }

        $violations = $this->validator->validate($fields, new Assert\Collection(
            fields: array_combine(
                array_map(static fn (ServiceField $field): string => $field->name, $description->fields),
                array_map(fn (ServiceField $field): array => $this->constraints($field, $adding), $description->fields),
            ),
        ));
        if (\count($violations) > 0) {
            throw new UnprocessableEntityHttpException($this->translator->trans('problem.check_fields'), new ValidationFailedException($fields, $violations));
        }

        return new ConnectionSettings(
            $description->key,
            $fields,
            SalesContext::tryFrom($payload->getString('salesContext')),
            UnknownItems::tryFrom($payload->getString('unknownItems')),
        );
    }

    private function description(string $service): ServiceDescription
    {
        if (!$this->connectors->has($service)) {
            throw new NotFound('service', $service);
        }

        return $this->connectors->get($service)->describe();
    }

    /**
     * @return list<\Symfony\Component\Validator\Constraint>
     */
    private function constraints(ServiceField $field, bool $adding): array
    {
        $constraints = [new Assert\Length(max: max(1, $field->maxLength))];
        if ($field->required && (!$field->secret || $adding)) {
            $constraints[] = new Assert\NotBlank(message: 'field.required');
        }
        if (null !== $field->pattern) {
            $constraints[] = new Assert\Regex(pattern: $field->pattern, message: $field->patternMessage ?? 'field.invalid');
        }

        return $constraints;
    }
}
