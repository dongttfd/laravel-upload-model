<?php

use Illuminate\Support\Str;

/**
 * Helper functions
 */

if (!function_exists('toPasscalCase')) {
    /**
     * Return str to PasscalCase
     * Eg: 'bcs_ok' => 'BcsOk'
     *
     * @param string $str
     * @return string
     */
    function toPasscalCase(string $str)
    {
        return Str::ucfirst(Str::camel($str));
    }
}

if (!function_exists('hasSubfix')) {
    /**
     * Check str has sufix
     *
     * @param string $str
     * @param string $needle
     * @return bool
     */
    function hasSubfix(string $str, string $needle)
    {
        return ($length = strlen($needle))
            ? substr($str, -$length) === $needle
            : true;
    }
}
