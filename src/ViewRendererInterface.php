<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

interface ViewRendererInterface
{
    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function render(string $template, array|ViewDataInterface $data = []): string;

    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function partialRender(string $template, array|ViewDataInterface $data = []): string;
}
