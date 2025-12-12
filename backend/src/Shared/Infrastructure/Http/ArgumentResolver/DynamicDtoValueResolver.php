<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\ArgumentResolver;

use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\DynamicValidator;
use App\Shared\Infrastructure\Http\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Resolves controller arguments marked with #[DynamicDto] attribute.
 * Automatically deserializes, validates, and hydrates strict DTOs from request data.
 */
final readonly class DynamicDtoValueResolver implements ValueResolverInterface
{
    public function __construct(
        private DynamicValidator $dynamicValidator,
    ) {
    }

    /**
     * @return iterable<object>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check if argument has DynamicDto attribute
        $attributes = $argument->getAttributesOfType(DynamicDto::class);

        if (empty($attributes)) {
            return [];
        }

        $type = $argument->getType();

        if ($type === null || !class_exists($type)) {
            return [];
        }

        // Get request data
        $data = $this->getRequestData($request);

        // Validate and hydrate
        $dto = $this->dynamicValidator->validateAndHydrate($type, $data);

        yield $dto;
    }

    /**
     * Extracts data from request based on content type.
     * Merges query parameters with body data (query params have lower priority).
     *
     * @return array<string, mixed>
     */
    private function getRequestData(Request $request): array
    {
        $bodyData = [];

        if ('json' === $request->getContentTypeFormat()) {
            $content = $request->getContent();
            try {
                $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
                $bodyData = \is_array($data) ? $data : [];
            } catch (\JsonException) {
                return throw new ValidationException(
                    errors: [],
                    message: 'Invalid JSON payload',
                );
            }
        } else {
            $bodyData = $request->request->all();
        }

        // Merge query parameters with body data
        // Body data takes precedence over query parameters
        return array_merge($request->query->all(), $bodyData);
    }
}
