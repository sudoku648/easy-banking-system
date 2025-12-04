<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    protected function jsonSuccess(mixed $data = null, int $status = 200): JsonResponse
    {
        return $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function jsonError(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
