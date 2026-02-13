<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

interface LayoutTemplateDataInterface extends ViewDataInterface
{
    public function withLayoutContent(string $content, ?string $defaultTitle = null): object;
}
