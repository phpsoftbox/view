<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

use RuntimeException;

use function array_merge;
use function extract;
use function is_file;
use function is_string;
use function ltrim;
use function ob_get_clean;
use function ob_start;
use function rtrim;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;
use const EXTR_SKIP;

final readonly class PhpViewRenderer implements ViewRendererInterface, TemplateExistsInterface
{
    /**
     * @param array<string, mixed> $sharedData
     */
    public function __construct(
        private ?string $basePath = null,
        private array $sharedData = [],
    ) {
    }

    public function render(string $template, array|ViewDataInterface $data = []): string
    {
        $context = new ViewContext($this);

        return $this->renderWithContext($template, $data, $context);
    }

    public function partialRender(string $template, array|ViewDataInterface $data = []): string
    {
        $context = new ViewContext($this);

        return $this->partialRenderWithContext($template, $data, $context);
    }

    public function exists(string $template): bool
    {
        return is_file($this->resolvePath($template));
    }

    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function renderWithContext(string $template, array|ViewDataInterface $data, ViewContext $context): string
    {
        $content = $this->partialRenderWithContext($template, $data, $context);

        $layoutTemplate = $context->layoutTemplate();
        if (!is_string($layoutTemplate) || $layoutTemplate === '') {
            return $content;
        }

        $layoutPayload = $this->prepareLayoutPayload($context->layoutData(), $content, $context);

        return $this->partialRenderWithContext($layoutTemplate, $layoutPayload, $context);
    }

    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function partialRenderWithContext(string $template, array|ViewDataInterface $data, ViewContext $context): string
    {
        $path = $this->resolvePath($template);

        if (!is_file($path)) {
            throw new RuntimeException('View file not found: ' . $path);
        }

        ob_start();
        if ($data instanceof ViewDataInterface) {
            $this->renderObjectPayload($path, $data, $context);
        } else {
            $this->renderArrayPayload($path, $data, $context);
        }

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderArrayPayload(string $path, array $data, ViewContext $context): void
    {
        $payload = array_merge($this->sharedData, $data, [
            'viewContext' => $context,
        ]);

        $scope = new class ($this, $context, $path, $payload) {
            /**
             * @param array<string, mixed> $data
             */
            public function __construct(
                public PhpViewRenderer $view,
                public ViewContext $viewContext,
                private string $path,
                private array $data,
            ) {
            }

            public function render(): void
            {
                extract($this->data, EXTR_SKIP);
                require $this->path;
            }
        };

        $scope->render();
    }

    private function renderObjectPayload(string $path, ViewDataInterface $data, ViewContext $context): void
    {
        if ($data instanceof ViewContextAwareInterface) {
            $data = $data->withViewContext($context);
        }

        $sharedData = array_merge($this->sharedData, [
            'viewContext' => $context,
        ]);

        $render = function () use ($path, $sharedData): void {
            extract($sharedData, EXTR_SKIP);
            require $path;
        };

        $bound = $render->bindTo($data, $data::class);
        if ($bound === null) {
            throw new RuntimeException('Unable to bind view DTO for template rendering.');
        }

        $bound();
    }

    /**
     * @param array<string, mixed>|LayoutTemplateDataInterface $layoutData
     * @return array<string, mixed>|LayoutTemplateDataInterface
     */
    private function prepareLayoutPayload(array|LayoutTemplateDataInterface $layoutData, string $content, ViewContext $context): array|LayoutTemplateDataInterface
    {
        if ($layoutData instanceof LayoutTemplateDataInterface) {
            return $layoutData->withLayoutContent($content);
        }

        return array_merge($layoutData, [
            'content'     => $content,
            'viewContext' => $context,
        ]);
    }

    private function resolvePath(string $template): string
    {
        if ($this->basePath === null || $this->basePath === '') {
            return $template;
        }

        if (str_starts_with($template, DIRECTORY_SEPARATOR)) {
            return $template;
        }

        $base     = rtrim($this->basePath, DIRECTORY_SEPARATOR);
        $template = ltrim($template, DIRECTORY_SEPARATOR);

        return $base . DIRECTORY_SEPARATOR . $template;
    }
}
