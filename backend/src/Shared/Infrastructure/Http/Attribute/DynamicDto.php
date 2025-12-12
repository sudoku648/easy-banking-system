<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Attribute;

/**
 * Marks a controller argument as a dynamic DTO that should be:
 * 1. Deserialized from request into a shadow object with mixed properties
 * 2. Validated using Symfony validator
 * 3. Mapped to strict-typed DTO
 *
 * Example:
 * ```php
 * public function create(#[DynamicDto] CreateUserDto $dto): Response
 * {
 *     // $dto is fully validated and strictly typed
 * }
 * ```
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class DynamicDto
{
}
