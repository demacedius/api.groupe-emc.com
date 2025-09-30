<?php

namespace App\Service;

class PostalCodeExtractor
{
    /**
     * Extrait le code postal français d'une adresse
     * Pattern pour codes postaux français: 5 chiffres
     */
    public function extractFromAddress(string $address): ?string
    {
        // Pattern pour codes postaux français: 5 chiffres consécutifs
        $pattern = '/\b(\d{5})\b/';

        if (preg_match($pattern, $address, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Valide si un code postal est valide pour la France
     */
    public function isValidFrenchPostalCode(string $postalCode): bool
    {
        return preg_match('/^\d{5}$/', $postalCode) === 1;
    }
}