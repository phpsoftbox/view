<?php

declare(strict_types=1);

namespace PhpSoftBox\View\Tests;

use PhpSoftBox\View\PhpViewRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function ob_get_level;
use function trim;

#[CoversClass(PhpViewRenderer::class)]
#[CoversMethod(PhpViewRenderer::class, 'render')]
#[CoversMethod(PhpViewRenderer::class, 'partialRender')]
#[CoversMethod(PhpViewRenderer::class, 'renderWithContext')]
#[CoversMethod(PhpViewRenderer::class, 'partialRenderWithContext')]
final class PhpViewRendererTest extends TestCase
{
    /**
     * Проверим, что шаблон рендерится и получает данные.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testRendersTemplateWithData(): void
    {
        $renderer = new PhpViewRenderer();
        $template = __DIR__ . '/fixtures/view.php';

        $output = $renderer->render($template, ['name' => 'World']);

        $this->assertSame('Hello, World', trim($output));
    }

    /**
     * Проверим, что базовый путь применяется к относительным шаблонам.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testResolvesBasePath(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('view.php', ['name' => 'Base']);

        $this->assertSame('Hello, Base', trim($output));
    }

    /**
     * Проверим, что в шаблоне доступен renderer через $this->view.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testTemplateCanRenderNestedLayout(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-layout.php', ['name' => 'Nested']);

        $this->assertSame('<main>Hello, Nested</main>', trim($output));
    }

    /**
     * Проверим, что full render применяет layout из ViewContext.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testRenderAppliesLayoutFromViewContext(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-auto-layout.php', ['name' => 'Layout']);

        $this->assertSame('<main>Hello, Layout</main>', trim($output));
    }

    /**
     * Проверим, что partialRender рендерит только шаблон без layout-обертки.
     *
     * @see PhpViewRenderer::partialRender()
     */
    #[Test]
    public function testPartialRenderSkipsLayoutWrapping(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->partialRender('with-auto-layout.php', ['name' => 'Layout']);

        $this->assertSame('Hello, Layout', trim($output));
    }

    /**
     * Проверим, что DTO получает ViewContext и может рендерить partial.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
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

    /**
     * Проверим, что стили/скрипты/meta регистрируются в контексте и попадают в layout.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
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

    /**
     * Проверим, что в шаблонах доступен helper html().
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testTemplateCanUseHtmlHelper(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('html-helper.php', ['value' => '<b>Unsafe</b>']);

        $this->assertSame('&lt;b&gt;Unsafe&lt;/b&gt;', trim($output));
    }

    /**
     * Проверим, что helper raw() выводит строку без экранирования.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testTemplateCanUseRawHelper(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('raw-helper.php', ['value' => '<b>Safe</b>']);

        $this->assertSame('<b>Safe</b>', trim($output));
    }

    /**
     * Проверим, что при передаче DTO в шаблоне доступен $this с публичными полями DTO.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testTemplateCanUseDtoAsThis(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');
        $dto      = new EmailConfirmView(200, '<b>OK</b>');

        $output = $renderer->render('dto-view.php', $dto);

        $this->assertSame('<h1>200</h1>' . "\n" . '<p>&lt;b&gt;OK&lt;/b&gt;</p>', trim($output));
    }

    /**
     * Проверим, что sharedData доступен и в DTO-режиме.
     *
     * @see PhpViewRenderer::render()
     */
    #[Test]
    public function testTemplateCanUseSharedDataWithDto(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures', [
            'brand' => 'GetStash',
        ]);
        $dto = new EmailConfirmView(200, 'Ready');

        $output = $renderer->render('dto-shared.php', $dto);

        $this->assertSame('<p>GetStash: Ready</p>', trim($output));
    }

    /**
     * Проверим, что исключение в шаблоне пробрасывается, а частичный вывод и открытые шаблоном
     * буферы удаляются: уровень буферизации возвращается к исходному.
     *
     * @see PhpViewRenderer::partialRenderWithContext()
     */
    #[Test]
    public function exceptionInTemplateCleansOutputBuffers(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');
        $level    = ob_get_level();

        try {
            $renderer->render('throwing.php');
            self::fail('Ожидалось исключение из шаблона.');
        } catch (RuntimeException $exception) {
            self::assertSame('Template failed', $exception->getMessage());
        }

        self::assertSame($level, ob_get_level());
    }

    /**
     * Проверим, что незакрытый шаблоном буфер сливается в результат рендера и не остаётся открытым.
     *
     * @see PhpViewRenderer::partialRenderWithContext()
     */
    #[Test]
    public function unclosedTemplateBufferIsMergedIntoOutput(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');
        $level    = ob_get_level();

        $output = $renderer->render('unclosed-buffer.php');

        self::assertSame('AB', trim($output));
        self::assertSame($level, ob_get_level());
    }

    /**
     * Проверим, что вложенный render() из шаблона не наследует layout внешнего шаблона.
     *
     * @see PhpViewRenderer::renderWithContext()
     */
    #[Test]
    public function nestedRenderDoesNotInheritOuterLayout(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('nested-render-outer-layout.php');

        self::assertSame('<main>[Hello, Inner]</main>', trim($output));
    }

    /**
     * Проверим, что layout вложенного render() не применяется к внешнему шаблону.
     *
     * @see PhpViewRenderer::renderWithContext()
     */
    #[Test]
    public function nestedRenderLayoutDoesNotLeakToOuterTemplate(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('nested-render-inner-layout.php');

        self::assertSame('[<main>Hello, Inner</main>]', trim($output));
    }

    /**
     * Проверим, что layout может обернуться в родительский layout через setLayout().
     *
     * @see PhpViewRenderer::renderWithContext()
     */
    #[Test]
    public function renderSupportsNestedLayouts(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $output = $renderer->render('with-child-layout.php');

        self::assertSame('<main><section>Body</section></main>', trim($output));
    }

    /**
     * Проверим, что циклическая ссылка layout на самого себя прерывается исключением.
     *
     * @see PhpViewRenderer::renderWithContext()
     */
    #[Test]
    public function circularLayoutThrowsException(): void
    {
        $renderer = new PhpViewRenderer(__DIR__ . '/fixtures');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Layout nesting depth exceeded');

        $renderer->render('circular-layout.php');
    }
}
