<?php

declare(strict_types=1);

namespace Hestia\WebApp\Installers\NodeJs\NodeJsUtils;

use RuntimeException;

use function file_get_contents;
use function str_replace;

class NodeJsUtil
{
    public function parseTemplate(string $template, array $search, array $replace): string
    {
        $contents = file_get_contents($template);

        if ($contents === false) {
            throw new RuntimeException('Unable to read template: ' . $template);
        }

        return str_replace($search, $replace, $contents);
    }
}
