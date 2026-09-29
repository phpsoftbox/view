<?php

declare(strict_types=1);

namespace PhpSoftBox\View\Tests;

use PhpSoftBox\View\ViewContext;
use PhpSoftBox\View\ViewContextAwareInterface;
use PhpSoftBox\View\ViewDataInterface;

/**
 * DTO-payload с доступом к ViewContext для тестов partial-рендера.
 */
final readonly class DtoView implements ViewContextAwareInterface
{
    public function __construct(
        public string $title,
        public string $message,
        private ?ViewContext $viewContext = null,
    ) {
    }

    public function withViewContext(ViewContext $context): object
    {
        return new self(
            title: $this->title,
            message: $this->message,
            viewContext: $context,
        );
    }

    public function renderPartial(string $template, array|ViewDataInterface $data = []): string
    {
        if ($this->viewContext === null) {
            return '';
        }

        return $this->viewContext->partialRender($template, $data);
    }
}
