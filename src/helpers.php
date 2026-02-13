<?php

declare(strict_types=1);

if (!function_exists('html')) {
    /**
     * HTML-escape helper for PHP templates.
     */
    function html(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('raw')) {
    /**
     * Explicit raw output helper for trusted HTML strings.
     */
    function raw(mixed $value): string
    {
        return (string) $value;
    }
}
