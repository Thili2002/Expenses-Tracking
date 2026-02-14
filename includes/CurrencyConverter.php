<?php

class CurrencyConverter
{
    private static $api_key = 'YOUR_API_KEY'; // User can replace this
    private static $cache_dir = __DIR__ . '/../cache';

    public static $supported_currencies = ['USD', 'LKR', 'EUR'];
    public static $currency_symbols = [
        'USD' => '$',
        'LKR' => 'Rs.',
        'EUR' => '€'
    ];

    public static function convert($amount, $from, $to)
    {
        if ($from === $to)
            return $amount;

        $rates = self::getRates($from);
        if (isset($rates[$to])) {
            return $amount * $rates[$to];
        }

        return $amount; // Fallback
    }

    private static function getRates($base)
    {
        $cache_file = self::$cache_dir . "/rates_{$base}.json";

        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0777, true);
        }

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
            return json_decode(file_get_contents($cache_file), true);
        }

        // Try to fetch from API (using a free tier or fallback)
        // For demonstration, we'll use realistic static rates if API fails or isn't configured
        $rates = self::fetchFromApi($base);

        if (!$rates) {
            $rates = self::getFallbackRates($base);
        }

        file_put_contents($cache_file, json_encode($rates));
        return $rates;
    }

    private static function fetchFromApi($base)
    {
        // Using a public API that doesn't strictly need a key for some requests or a placeholder
        // For this demo, let's use the static fallback to ensure it works immediately
        return null;
    }

    private static function getFallbackRates($base)
    {
        // Realistic approximate rates for USD, LKR, EUR
        $all_rates = [
            'USD' => ['USD' => 1, 'LKR' => 300, 'EUR' => 0.92],
            'LKR' => ['LKR' => 1, 'USD' => 0.0033, 'EUR' => 0.0031],
            'EUR' => ['EUR' => 1, 'USD' => 1.09, 'LKR' => 326],
        ];

        return $all_rates[$base] ?? ['USD' => 1];
    }

    public static function getSymbol($currency)
    {
        return self::$currency_symbols[$currency] ?? '$';
    }
}
?>