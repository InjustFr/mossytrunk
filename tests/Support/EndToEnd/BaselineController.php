<?php

declare(strict_types=1);

namespace App\Tests\Support\EndToEnd;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_e2e/baseline', name: 'e2e_baseline', methods: ['POST'])]
final class BaselineController
{
    public function __invoke(): Response
    {
        StaticDriver::commit();
        StaticDriver::beginTransaction();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
