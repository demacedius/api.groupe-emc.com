<?php

namespace App\Service;

class NumberToWordsService
{
    private array $unites = [
        0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre',
        5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf',
        10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize', 14 => 'quatorze',
        15 => 'quinze', 16 => 'seize', 17 => 'dix-sept', 18 => 'dix-huit', 19 => 'dix-neuf'
    ];

    private array $dizaines = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante',
        6 => 'soixante', 7 => 'soixante-dix', 8 => 'quatre-vingt', 9 => 'quatre-vingt-dix'
    ];

    private array $scales = [
        1000000000 => 'milliard',
        1000000 => 'million',
        1000 => 'mille'
    ];

    public function convertToWords(float $number): string
    {
        if ($number == 0) {
            return 'zéro euro';
        }

        $euros = (int)$number;
        $centimes = round(($number - $euros) * 100);

        $result = '';

        if ($euros > 0) {
            $result .= $this->convertIntegerToWords($euros);
            $result .= $euros > 1 ? ' euros' : ' euro';
        }

        if ($centimes > 0) {
            if ($euros > 0) {
                $result .= ' et ';
            }
            $result .= $this->convertIntegerToWords($centimes);
            $result .= $centimes > 1 ? ' centimes' : ' centime';
        }

        return ucfirst($result);
    }

    private function convertIntegerToWords(int $number): string
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return $this->unites[$number];
        }

        if ($number < 100) {
            $dizaine = intval($number / 10);
            $unite = $number % 10;

            if ($dizaine == 7 || $dizaine == 9) {
                if ($dizaine == 7) {
                    $result = 'soixante';
                    if ($unite == 1) {
                        $result .= ' et onze';
                    } else {
                        $result .= '-' . $this->unites[$unite + 10];
                    }
                } else { // $dizaine == 9
                    $result = 'quatre-vingt';
                    if ($unite > 0) {
                        $result .= '-' . $this->unites[$unite + 10];
                    } else {
                        $result .= '-dix';
                    }
                }
                return $result;
            }

            $result = $this->dizaines[$dizaine];
            if ($unite > 0) {
                if ($unite == 1 && $dizaine != 8) {
                    $result .= ' et un';
                } else {
                    $result .= '-' . $this->unites[$unite];
                }
            } elseif ($dizaine == 8) {
                $result .= 's';
            }

            return $result;
        }

        if ($number < 1000) {
            $centaine = intval($number / 100);
            $reste = $number % 100;

            $result = '';
            if ($centaine > 1) {
                $result .= $this->unites[$centaine] . ' ';
            }
            $result .= 'cent';
            if ($centaine > 1 && $reste == 0) {
                $result .= 's';
            }

            if ($reste > 0) {
                $result .= ' ' . $this->convertIntegerToWords($reste);
            }

            return $result;
        }

        // Gestion des grandes échelles (milliers, millions, milliards)
        foreach ($this->scales as $scale => $word) {
            if ($number >= $scale) {
                $quotient = intval($number / $scale);
                $reste = $number % $scale;

                $result = '';
                if ($scale == 1000 && $quotient == 1) {
                    $result = 'mille';
                } else {
                    $result = $this->convertIntegerToWords($quotient) . ' ' . $word;
                    if ($quotient > 1 && $scale > 1000) {
                        $result .= 's';
                    }
                }

                if ($reste > 0) {
                    $result .= ' ' . $this->convertIntegerToWords($reste);
                }

                return $result;
            }
        }

        return '';
    }

    /**
     * Convertit un prix avec TVA en lettres (pour les devis/factures)
     */
    public function convertPriceToWords(array $financialData): array
    {
        $result = [];

        if (isset($financialData['price'])) {
            $result['priceInWords'] = $this->convertToWords(floatval($financialData['price']));
        }

        if (isset($financialData['totalWithTax'])) {
            $result['totalWithTaxInWords'] = $this->convertToWords(floatval($financialData['totalWithTax']));
        }

        if (isset($financialData['totalTax'])) {
            $result['taxInWords'] = $this->convertToWords(floatval($financialData['totalTax']));
        }

        return $result;
    }
}