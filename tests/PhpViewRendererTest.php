<?php

declare(strict_types=1);

namespace PhpSoftBox\View\Tests;

use PhpSoftBox\View\PhpViewRenderer;
use PhpSoftBox\View\ViewContext;
use PhpSoftBox\View\ViewContextAwareInterface;
use PhpSoftBox\View\ViewDataInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function trim;

#[CoversClass(PhpViewRenderer::class)]
final class PhpViewRendererTest extends TestCase
{
    #[Test]
    /**
     * Проверяем, что шаблон рендерится и получает данные.
     */
    public function testRendersTemplateWithData(): void
    {
        $renderer = new PhpViewRenderer();
        $template = __DIR__ . '/fixtures/view.php';

        $output = $renderer->render($template, ['name' => 'World']);

        $this->assertSame('Hello, World', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что базовый путь применяется к относительным шаблонам.
     */
    public function testResolvesBasePath(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('view.php', ['name' => 'Base']);

        $this->assertSame('Hello, Base', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что в шаблоне доступен renderer через $this->view.
     */
    public function testTemplateCanRenderNestedLayout(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-layout.php', ['name' => 'Nested']);

        $this->assertSame('<main>Hello, Nested</main>', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что full render применяет layout из ViewContext.
     */
    public function testRenderAppliesLayoutFromViewContext(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-auto-layout.php', ['name' => 'Layout']);

        $this->assertSame('<main>Hello, Layout</main>', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что partialRender рендерит только шаблон без layout-обертки.
     */
    public function testPartialRenderSkipsLayoutWrapping(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->partialRender('with-auto-layout.php', ['name' => 'Layout']);

        $this->assertSame('Hello, Layout', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что DTO получает ViewContext и может рендерить partial.
     */
    public function testDtoCanRenderPartialThroughViewContext(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');
        $dto      = new DtoView(
            title: 'OK',
            message: '<b>Safe</b>',
        );

        $output = $renderer->render('dto-partial.php', $dto);

        $this->assertSame('<h1>OK</h1><p>&lt;b&gt;Safe&lt;/b&gt;</p>', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что стили/скрипты/meta регистрируются в контексте и попадают в layout.
     */
    public function testLayoutCanRenderAssetsAndMetaFromViewContext(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-assets-layout.php', [
            'name' => 'Assets',
        ]);
        $normalized = trim($output);

        $this->assertStringContainsString('<meta name="description" content="Mail preview">', $normalized);
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/app.css">', $normalized);
        $this->assertStringContainsString('<script src="/assets/app.js" defer></script>', $normalized);
        $this->assertStringContainsString('<main>Hello, Assets</main>', $normalized);
    }

    #[Test]
    /**
     * Проверяем, что в шаблонах доступен helper html().
     */
    public function testTemplateCanUseHtmlHelper(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('html-helper.php', ['value' => '<b>Unsafe</b>']);

        $this->assertSame('&lt;b&gt;Unsafe&lt;/b&gt;', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что helper raw() выводит строку без экранирования.
     */
    public function testTemplateCanUseRawHelper(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('raw-helper.php', ['value' => '<b>Safe</b>']);

        $this->assertSame('<b>Safe</b>', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что при передаче DTO в шаблоне доступен $this с публичными полями DTO.
     */
    public function testTemplateCanUseDtoAsThis(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');
        $dto      = new EmailConfirmView(200, '<b>OK</b>');

        $output = $renderer->render('dto-view.php', $dto);

        $this->assertSame('<h1>200</h1>' . "\n" . '<p>&lt;b&gt;OK&lt;/b&gt;</p>', trim($output));
    }

    #[Test]
    /**
     * Проверяем, что sharedData доступен и в DTO-режиме.
     */
    public function testTemplateCanUseSharedDataWithDto(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures', [
            'brand' => 'GetStash',
        ]);
        $dto = new EmailConfirmView(200, 'Ready');

        $output = $renderer->render('dto-shared.php', $dto);

        $this->assertSame('<p>GetStash: Ready</p>', trim($output));
    }
}

final readonly class EmailConfirmView implements ViewDataInterface
{
    public function __construct(
        public int $status,
        public string $message,
    ) {
    }
}

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
