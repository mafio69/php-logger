<?php

declare(strict_types=1);

namespace Mariusz\Logger;

/**
 * Anonymizes sensitive fields in log context arrays.
 * Replaces the middle portion of sensitive values with **** to preserve
 * readability while protecting personal/sensitive data.
 */
final class LogAnonymizer
{
    /**
     * Field names (case-insensitive) whose values will be partially masked.
     * Covers Polish and international naming conventions.
     */
    private const SENSITIVE_FIELDS = [
        // Identyfikatory osobowe / Personal identifiers
        'pesel', 'nip', 'regon', 'dowod', 'dowod_osobisty', 'id_card',
        'passport', 'paszport', 'ssn', 'national_id',

        // Kontakt / Contact
        'email', 'e-mail', 'mail', 'telefon', 'phone', 'mobile',
        'tel', 'numer_telefonu', 'phone_number',

        // Uwierzytelnianie / Authentication
        'password', 'haslo', 'hasło', 'passwd', 'pass',
        'token', 'access_token', 'refresh_token', 'api_key', 'apikey',
        'secret', 'tajny', 'klucz', 'private_key',
        'authorization', 'auth', 'bearer',
        'session', 'session_id', 'cookie',

        // Płatności / Payment
        'karta', 'card', 'card_number', 'numer_karty', 'pan',
        'cvv', 'cvc', 'expiry', 'iban', 'konto', 'account_number',
        'numer_konta',

        // Adres / Address
        'adres', 'address', 'ulica', 'street',
    ];

    /**
     * Recursively anonymizes sensitive fields in a context array.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function anonymize(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->anonymize($value);
            } elseif (is_string($value) && $this->isSensitive($key)) {
                $context[$key] = $this->mask($value);
            }
        }

        return $context;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), self::SENSITIVE_FIELDS, true);
    }

    /**
     * Masks the middle of a string, keeping ~25% from each end visible.
     * Examples:
     *   "jan.kowalski@gmail.com" → "jan.****@gmail.com"  (not exact, illustrative)
     *   "12345678901"            → "123****901"
     *   "abc"                    → "****"
     */
    private function mask(string $value): string
    {
        $len = mb_strlen($value);

        if ($len <= 4) {
            return '****';
        }

        $visible = max(1, (int) round($len * 0.25));
        $start   = mb_substr($value, 0, $visible);
        $end     = mb_substr($value, -$visible);

        return $start . '****' . $end;
    }
}
