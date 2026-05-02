<?php

declare(strict_types=1);

namespace Mariusz\Logger;

/**
 * Safely serializes arbitrary log context values to JSON-encodable arrays.
 *
 * Handles: scalars, null, arrays (recursive), Throwable, objects with __toString,
 * objects with toArray/jsonSerialize, generic objects (public properties), resources.
 */
final class LogContextSerializer
{
    /**
     * Recursively serialize all values in a context array.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function serialize(array $context): array
    {
        foreach ($context as $key => $value) {
            $context[$key] = $this->serializeValue($value);
        }

        return $context;
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $this->serialize($value);
        }

        if ($value instanceof \Throwable) {
            return $this->serializeThrowable($value);
        }

        if (is_resource($value)) {
            return get_resource_type($value) . ' resource';
        }

        if (is_object($value)) {
            return $this->serializeObject($value);
        }

        return (string) $value;
    }

    /** @return array<string, mixed> */
    private function serializeThrowable(\Throwable $e): array
    {
        $data = [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => basename(dirname($e->getFile())) . '/' . basename($e->getFile()) . ':' . $e->getLine(),
        ];

        if ($e->getPrevious() !== null) {
            $data['previous'] = $this->serializeThrowable($e->getPrevious());
        }

        return $data;
    }

    private function serializeObject(object $value): mixed
    {
        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (method_exists($value, '__toString')) {
            return (string) $value;
        }

        $vars = get_object_vars($value);

        return $vars !== [] ? array_merge(['class' => $value::class], $vars) : $value::class;
    }
}
