<?php

namespace Fujiba\Notification\Services;

class TemplateRenderer
{
    public function render(string $template, array $vars): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function ($m) use ($vars) {
                $key   = $m[1];
                $value = data_get($vars, $key);

                if (is_scalar($value) || $value === null) {
                    return (string) $value;
                }

                return '';
            },
            $template
        );
    }
}
