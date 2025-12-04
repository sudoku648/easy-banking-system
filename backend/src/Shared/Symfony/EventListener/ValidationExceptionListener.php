<?php

declare(strict_types=1);

namespace App\Shared\Symfony\EventListener;

use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catches ValidationException thrown during request processing (e.g., in argument resolvers)
 * and converts it to a proper ValidationErrorResponse.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final readonly class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof ValidationException) {
            return;
        }

        $response = BadRequestResponse::fromException($throwable)->toJsonResponse();

        $event->setResponse($response);
    }
}
