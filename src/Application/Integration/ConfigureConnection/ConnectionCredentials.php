<?php

declare(strict_types=1);

namespace App\Application\Integration\ConfigureConnection;

use App\Application\Integration\ConnectionSecrets;
use App\Application\Integration\Exception\MissingSetting;
use App\Application\Integration\ServiceDescription;
use App\Domain\Integration\ServiceConnection;

final readonly class ConnectionCredentials
{
    public function __construct(private ConnectionSecrets $secrets)
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
                $current = $this->secrets->reveal($connection, $field->name);
                if ($known && $value !== $current) {
                    $this->secrets->keep($connection, $field->name, $value);
                    $changed = true;
                }
                $known = $known || null !== $current;
            }
            if ($field->required && !$known) {
                throw new MissingSetting($field->name);
            }
        }

        return $changed;
    }
}
