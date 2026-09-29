<?php

declare(strict_types=1);

namespace App\Application\Integration\ConfigureConnection;

use App\Application\Integration\ServiceDescription;
use App\Application\Integration\ServiceUnavailable;
use App\Application\Workspace\WorkspaceSecrets;
use App\Domain\Identity\SecretName;
use App\Domain\Integration\ServiceConnection;

final readonly class ConnectionCredentials
{
    public function __construct(private WorkspaceSecrets $secrets)
    {
    }

    /**
     * @param array<string, string|null> $fields
     */
    public function write(ServiceConnection $connection, ServiceDescription $description, array $fields): bool
    {
        $settings = [];
        foreach ($description->plainFields() as $field) {
            $value = $field->normalize((string) ($fields[$field->name] ?? ''));
            if ('' !== $value) {
                $settings[$field->name] = $value;
            }
        }
        $changed = $connection->configure($settings);

        foreach ($description->fields as $field) {
            $value = $field->normalize((string) ($fields[$field->name] ?? ''));
            $known = '' !== $value;
            if ($field->secret) {
                $name = SecretName::of($connection->service(), $field->name);
                $current = $this->secrets->reveal($connection->workspace(), $name);
                if ($known && $value !== $current) {
                    $this->secrets->keep($connection->workspace(), $name, $value);
                    $changed = true;
                }
                $known = $known || null !== $current;
            }
            if ($field->required && !$known) {
                throw ServiceUnavailable::missingSetting($field->label);
            }
        }

        return $changed;
    }
}
