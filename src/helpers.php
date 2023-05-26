<?php

use Illuminate\Support\Str;

/**
 * Helper functions
 */

if (!function_exists('toPascalCase')) {
    /**
     * Return str to PascalCase
     * Eg: 'bcs_ok' => 'BcsOk'
     *
     * @param string $str
     * @return string
     */
    function toPascalCase(string $str)
    {
        return Str::ucfirst(
            Str::camel(preg_replace('/[^!(a-zA-Z0-9_)]/', '_', $str))
        );
    }
}

if (!function_exists('hasPrefix')) {
    /**
     * Check str has prefix
     *
     * @param string $str
     * @param string $needle
     * @return bool
     */
    function hasPrefix(string $str, string $needle)
    {
        return substr($str, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('hasSuffix')) {
    /**
     * Check str has suffix
     *
     * @param string $str
     * @param string $needle
     * @return bool
     */
    function hasSuffix(string $str, string $needle)
    {
        return ($length = strlen($needle))
            ? substr($str, -$length) === $needle
            : true;
    }
}
