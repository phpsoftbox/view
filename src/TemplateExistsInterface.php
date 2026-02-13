<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

interface TemplateExistsInterface
{
    public function exists(string $template): bool;
}
