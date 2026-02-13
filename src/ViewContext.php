<?php

declare(strict_types=1);

namespace PhpSoftBox\View;

use function array_key_exists;
use function htmlspecialchars;
use function implode;
use function is_bool;
use function is_scalar;
use function is_string;

use const ENT_QUOTES;

final class ViewContext
{
    private ?string $layoutTemplate = null;

    /** @var array<string, mixed>|LayoutTemplateDataInterface */
    private array|LayoutTemplateDataInterface $layoutData = [];

    /** @var list<array{href:string, attributes:array<string, scalar|null>}> */
    private array $styles = [];
    /** @var list<array{src:string, attributes:array<string, scalar|null>}> */
    private array $scripts = [];
    /** @var list<array{name:string, content:string, attributes:array<string, scalar|null>}> */
    private array $meta = [];

    public function __construct(
        private readonly PhpViewRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function render(string $template, array|ViewDataInterface $data = []): string
    {
        return $this->renderer->renderWithContext($template, $data, $this);
    }

    /**
     * @param array<string, mixed>|ViewDataInterface $data
     */
    public function partialRender(string $template, array|ViewDataInterface $data = []): string
    {
        return $this->renderer->partialRenderWithContext($template, $data, $this);
    }

    /**
     * @param array<string, mixed>|LayoutTemplateDataInterface $data
     */
    public function setLayout(string $template, array|LayoutTemplateDataInterface $data = []): void
    {
        $this->layoutTemplate = $template;
        $this->layoutData     = $data;
    }

    public function clearLayout(): void
    {
        $this->layoutTemplate = null;
        $this->layoutData     = [];
    }

    public function layoutTemplate(): ?string
    {
        return $this->layoutTemplate;
    }

    /**
     * @return array<string, mixed>|LayoutTemplateDataInterface
     */
    public function layoutData(): array|LayoutTemplateDataInterface
    {
        return $this->layoutData;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function addStyle(string $href, array $attributes = []): void
    {
        $this->styles[] = [
            'href'       => $href,
            'attributes' => $this->normalizeAttributes($attributes),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function addScript(string $src, array $attributes = []): void
    {
        $this->scripts[] = [
            'src'        => $src,
            'attributes' => $this->normalizeAttributes($attributes),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function addMeta(string $name, string $content, array $attributes = []): void
    {
        $this->meta[] = [
            'name'       => $name,
            'content'    => $content,
            'attributes' => $this->normalizeAttributes($attributes),
        ];
    }

    /**
     * @return list<array{href:string, attributes:array<string, scalar|null>}>
     */
    public function styles(): array
    {
        return $this->styles;
    }

    /**
     * @return list<array{src:string, attributes:array<string, scalar|null>}>
     */
    public function scripts(): array
    {
        return $this->scripts;
    }

    /**
     * @return list<array{name:string, content:string, attributes:array<string, scalar|null>}>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    public function renderStyles(): string
    {
        $chunks = [];
        foreach ($this->styles as $item) {
            $chunks[] = '<link rel="stylesheet" href="' . htmlspecialchars($item['href'], ENT_QUOTES) . '"' . $this->renderAttributes($item['attributes']) . '>';
        }

        return implode("\n", $chunks);
    }

    public function renderScripts(): string
    {
        $chunks = [];
        foreach ($this->scripts as $item) {
            $chunks[] = '<script src="' . htmlspecialchars($item['src'], ENT_QUOTES) . '"' . $this->renderAttributes($item['attributes']) . '></script>';
        }

        return implode("\n", $chunks);
    }

    public function renderMeta(): string
    {
        $chunks = [];
        foreach ($this->meta as $item) {
            $attrs = $item['attributes'];
            if (!array_key_exists('name', $attrs)) {
                $attrs['name'] = $item['name'];
            }
            if (!array_key_exists('content', $attrs)) {
                $attrs['content'] = $item['content'];
            }

            $chunks[] = '<meta' . $this->renderAttributes($attrs) . '>';
        }

        return implode("\n", $chunks);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, scalar|null>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $parts[] = htmlspecialchars($name, ENT_QUOTES);
                }
                continue;
            }

            $parts[] = htmlspecialchars($name, ENT_QUOTES) . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
        }

        return $parts !== [] ? ' ' . implode(' ', $parts) : '';
    }
}
