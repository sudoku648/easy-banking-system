<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Creates dynamic shadow classes with mixed-type properties for validation,
 * then maps validated data to strict-typed DTOs.
 */
final readonly class DynamicValidator
{
    private const string SHADOW_NAMESPACE = 'App\\Generated\\Shadow\\';

    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Deserialize JSON data into a shadow object, validate it, then map to strict DTO.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param array<string, mixed> $data
     * @throws ValidationException
     * @return T
     */
    public function validateAndHydrate(string $dtoClass, array $data): object
    {
        // Coerce types based on DTO constructor parameter types
        $data = $this->coerceTypes($dtoClass, $data);

        // Create shadow class with mixed properties
        $shadowClass = $this->createShadowClass($dtoClass);

        // Hydrate shadow object with data
        $shadowObject = $this->hydrateShadowObject($shadowClass, $data);

        // Validate shadow object
        $violations = $this->validator->validate($shadowObject);

        if ($violations->count() > 0) {
            throw ValidationException::fromConstraintViolationList($violations);
        }

        // Map to strict DTO
        return $this->mapToStrictDto($dtoClass, $shadowObject);
    }

    /**
     * Creates a dynamic shadow class with all properties as mixed type.
     *
     * @param class-string $dtoClass
     * @return class-string
     */
    private function createShadowClass(string $dtoClass): string
    {
        $reflection = new \ReflectionClass($dtoClass);
        $shortName = $reflection->getShortName();
        $shadowClassName = self::SHADOW_NAMESPACE . $shortName . 'Shadow';

        // If shadow class already exists, return it
        if (class_exists($shadowClassName, false)) {
            return $shadowClassName;
        }

        // Build class code
        $classCode = $this->buildShadowClassCode($reflection);

        // Evaluate and create the class
        eval($classCode);

        return $shadowClassName;
    }

    /**
     * Builds PHP code for the shadow class.
     */
    private function buildShadowClassCode(\ReflectionClass $reflection): string
    {
        $originalClass = $reflection->getName();
        $shortName = $reflection->getShortName() . 'Shadow';

        $properties = [];
        $constructorParams = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            // Skip static properties
            if ($property->isStatic()) {
                continue;
            }

            $propertyName = $property->getName();

            // Copy validation attributes from original property
            $attributes = $this->getAttributesCode($property);

            // Create property with mixed type
            $properties[] = \sprintf(
                "    %s\n    public mixed \$%s = null;",
                $attributes,
                $propertyName,
            );
        }

        // Get constructor if exists
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $constructorParams[] = \sprintf('mixed $%s = null', $param->getName());
            }
        }

        $propertiesCode = implode("\n\n", $properties);
        $constructorParamsCode = implode(', ', $constructorParams);

        // Build constructor body - assign all parameters to properties
        $constructorBody = '';
        if ($constructor !== null) {
            $assignments = [];
            foreach ($constructor->getParameters() as $param) {
                $paramName = $param->getName();
                $assignments[] = \sprintf('        $this->%s = $%s;', $paramName, $paramName);
            }
            $constructorBody = implode("\n", $assignments);
        }

        return \sprintf(
            <<<'PHP'
namespace %s {
    use Symfony\Component\Validator\Constraints as Assert;

    /**
     * Shadow class for %s
     * Auto-generated for validation purposes.
     * All properties are mixed type to accept any JSON data.
     */
    final class %s
    {
%s

        public function __construct(%s)
        {
%s
        }
    }
}
PHP,
            rtrim(self::SHADOW_NAMESPACE, '\\'),
            $originalClass,
            $shortName,
            $propertiesCode,
            $constructorParamsCode,
            $constructorBody,
        );
    }

    /**
     * Extracts validation attributes from a property and returns PHP code.
     */
    private function getAttributesCode(\ReflectionProperty $property): string
    {
        $attributes = [];

        foreach ($property->getAttributes() as $attribute) {
            $name = $attribute->getName();
            $args = $attribute->getArguments();

            // Format attribute with arguments
            $attributes[] = empty($args)
                ? \sprintf('#[%s]', $this->getShortAttributeName($name))
                : \sprintf('#[%s(%s)]', $this->getShortAttributeName($name), $this->formatAttributeArguments($args));
        }

        return implode("\n    ", $attributes);
    }

    /**
     * Gets short name for attribute (e.g., Assert\NotBlank instead of full namespace).
     */
    private function getShortAttributeName(string $fullName): string
    {
        if (str_starts_with($fullName, 'Symfony\\Component\\Validator\\Constraints\\')) {
            return 'Assert\\' . substr($fullName, \strlen('Symfony\\Component\\Validator\\Constraints\\'));
        }

        return $fullName;
    }

    /**
     * Formats attribute arguments for code generation.
     *
     * @param array<int|string, mixed> $args
     */
    private function formatAttributeArguments(array $args): string
    {
        $formatted = [];

        foreach ($args as $key => $value) {
            $formatted[] = \is_string($key) ? \sprintf('%s: %s', $key, $this->formatValue($value)) : $this->formatValue($value);
        }

        return implode(', ', $formatted);
    }

    /**
     * Formats a value for PHP code generation.
     */
    private function formatValue(mixed $value): string
    {
        if (\is_string($value)) {
            return \sprintf("'%s'", addslashes($value));
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if (\is_array($value)) {
            $items = array_map(fn (mixed $v): string => $this->formatValue($v), $value);
            return '[' . implode(', ', $items) . ']';
        }

        return (string) $value;
    }

    /**
     * Hydrates shadow object with data.
     *
     * @param class-string $shadowClass
     * @param array<string, mixed> $data
     */
    private function hydrateShadowObject(string $shadowClass, array $data): object
    {
        $reflection = new \ReflectionClass($shadowClass);
        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            if (isset($data[$property->getName()])) {
                $property->setValue($object, $data[$property->getName()]);
            }
        }

        return $object;
    }

    /**
     * Maps validated shadow object to strict-typed DTO.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T
     */
    private function mapToStrictDto(string $dtoClass, object $shadowObject): object
    {
        $reflection = new \ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw new \RuntimeException(
                \sprintf('DTO class %s must have a constructor', $dtoClass),
            );
        }

        $args = [];
        $shadowReflection = new \ReflectionClass($shadowObject);

        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();
            $shadowProperty = $shadowReflection->getProperty($paramName);
            $value = $shadowProperty->getValue($shadowObject);

            // Use the value from shadow object
            $args[] = $value;
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Coerces data types based on DTO constructor parameter types.
     * Handles conversion from query parameter strings to appropriate types.
     *
     * @param class-string $dtoClass
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function coerceTypes(string $dtoClass, array $data): array
    {
        $reflection = new \ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $data;
        }

        $coercedData = [];

        foreach ($data as $key => $value) {
            // Find matching parameter
            $param = null;
            foreach ($constructor->getParameters() as $p) {
                if ($p->getName() === $key) {
                    $param = $p;
                    break;
                }
            }

            // If no matching parameter, keep original value
            if (null === $param) {
                $coercedData[$key] = $value;
                continue;
            }

            // Coerce based on parameter type
            $coercedData[$key] = $this->coerceValue($value, $param);
        }

        return $coercedData;
    }

    /**
     * Coerces a single value to match the parameter type.
     */
    private function coerceValue(mixed $value, \ReflectionParameter $param): mixed
    {
        // If value is null or not a string, return as-is
        if (null === $value || !\is_string($value)) {
            return $value;
        }

        $type = $param->getType();

        // If no type hint or union type, return as-is
        if (null === $type || $type instanceof \ReflectionUnionType) {
            return $value;
        }

        // Handle named types
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            return match ($typeName) {
                'int' => $this->coerceToInt($value),
                'float' => $this->coerceToFloat($value),
                'bool' => $this->coerceToBool($value),
                'array' => $this->coerceToArray($value),
                default => $value, // Keep as string for other types
            };
        }

        return $value;
    }

    /**
     * Coerces string to integer.
     * Only coerces if the value is a valid integer string (no decimals).
     */
    private function coerceToInt(string $value): int|string
    {
        // Return original if not numeric (let validator handle it)
        if (!is_numeric($value)) {
            return $value;
        }

        // Check if value has decimal point - if so, don't coerce
        // This prevents '3.41' from becoming 3
        if (str_contains($value, '.') || str_contains($value, ',')) {
            return $value; // Return as string, let validator handle it
        }

        return (int) $value;
    }

    /**
     * Coerces string to float.
     */
    private function coerceToFloat(string $value): float|string
    {
        // Return original if not numeric (let validator handle it)
        if (!is_numeric($value)) {
            return $value;
        }

        return (float) $value;
    }

    /**
     * Coerces string to boolean.
     * Handles common string representations: 'true', 'false'.
     */
    private function coerceToBool(string $value): bool|string
    {
        $lowercaseValue = strtolower($value);

        return match ($lowercaseValue) {
            'true' => true,
            'false' => false,
            default => $value, // Return original (let validator handle it)
        };
    }

    /**
     * Coerces string to array.
     * Handles comma-separated values.
     */
    private function coerceToArray(string $value): array
    {
        // If empty string, return empty array
        if ('' === $value) {
            return [];
        }

        // Split by comma and trim
        return array_map('trim', explode(',', $value));
    }
}
