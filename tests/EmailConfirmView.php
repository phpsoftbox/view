<?php

declare(strict_types=1);

namespace PhpSoftBox\View\Tests;

use PhpSoftBox\View\ViewDataInterface;

/**
 * DTO-payload для тестов рендера шаблонов с `$this`.
 */
final readonly class EmailConfirmView implements ViewDataInterface
{
    public function __construct(
        public int $status,
        public string $message,
    ) {
    }
}
