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
     *
     * @return array<string, mixed>
     */
    private function getRequestData(Request $request): array
    {
        if ('json' === $request->getContentTypeFormat()) {
            $content = $request->getContent();
            try {
                $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return throw new ValidationException(
                    errors: [],
                    message: 'Invalid JSON payload',
                );
            }

            return \is_array($data) ? $data : [];
        }

        return $request->request->all();
    }
}
