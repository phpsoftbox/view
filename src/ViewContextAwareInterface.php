<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

interface ViewContextAwareInterface extends ViewDataInterface
{
    public function withViewContext(ViewContext $context): object;
}
