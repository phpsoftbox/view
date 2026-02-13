# View

`phpsoftbox/view` — PHP/PHTML renderer с поддержкой:

- array payload
- DTO payload через `ViewDataInterface`
- `ViewContext` (layout, partial, meta/css/js)

## Contents

- [Quick Start](#quick-start)
- [Payload Types](#payload-types)
- [render()](#render)
- [partialRender()](#partialrender)
- [renderWithContext()](#renderwithcontext)
- [partialRenderWithContext()](#partialrenderwithcontext)
- [ViewContext API](#viewcontext-api)
- [Helpers](#helpers)

## Quick Start

```php
use PhpSoftBox\View\PhpViewRenderer;

$renderer = new PhpViewRenderer(
    basePath: __DIR__ . '/resources/views',
    sharedData: [
        'appName' => 'Demo',
    ],
);

echo $renderer->render('error.phtml', [
    'status' => 500,
    'message' => 'Internal error',
]);
```

## Payload Types

`ViewRendererInterface` принимает:

- `array<string, mixed>`
- `ViewDataInterface`

Для DTO payload объект должен реализовывать `ViewDataInterface`.

### DTO example

```php
use PhpSoftBox\View\ViewDataInterface;

final readonly class UserCardView implements ViewDataInterface
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}
```

## render()

`render(string $template, array|ViewDataInterface $data = []): string`

Назначение:

- full render
- сначала рендерит template
- если в `ViewContext` установлен layout, оборачивает content в layout

### render() with array payload

```php
echo $renderer->render('user/show.phtml', [
    'name' => 'Anton',
]);
```

`user/show.phtml`:

```php
<?php
/** @var string $name */
/** @var \PhpSoftBox\View\ViewContext $viewContext */

$viewContext->setLayout('layouts/app.phtml', [
    'title' => 'User page',
]);
?>
<h1><?= html($name) ?></h1>
```

`layouts/app.phtml`:

```php
<?php
/** @var string $content */
/** @var string|null $title */
?>
<!doctype html>
<html lang="en">
<head>
    <title><?= html($title ?? 'App') ?></title>
</head>
<body>
<?= raw($content) ?>
</body>
</html>
```

### render() with DTO payload

```php
$view = new UserCardView(name: 'Anton', email: 'anton@example.com');

echo $renderer->render('user/card.phtml', $view);
```

`user/card.phtml`:

```php
<?php
/** @var UserCardView $this */
?>
<div><?= html($this->name) ?> (<?= html($this->email) ?>)</div>
```

## partialRender()

`partialRender(string $template, array|ViewDataInterface $data = []): string`

Назначение:

- рендерит только указанный template
- layout не применяется

### partialRender() with array

```php
$badge = $renderer->partialRender('partials/badge.phtml', [
    'label' => 'New',
]);
```

### partialRender() with DTO

```php
final readonly class BadgeView implements ViewDataInterface
{
    public function __construct(public string $label) {}
}

$badge = $renderer->partialRender('partials/badge.phtml', new BadgeView('Hot'));
```

## renderWithContext()

`renderWithContext(string $template, array|ViewDataInterface $data, ViewContext $context): string`

Назначение:

- advanced API
- позволяет переиспользовать один и тот же `ViewContext` между несколькими рендерами
- полезно, когда контекст создаётся/настраивается снаружи

### renderWithContext() with array

```php
use PhpSoftBox\View\ViewContext;

$context = new ViewContext($renderer);
$context->addMeta('description', 'Admin page');

echo $renderer->renderWithContext('admin/index.phtml', [
    'title' => 'Admin',
], $context);
```

### renderWithContext() with DTO

```php
$context = new ViewContext($renderer);
$context->addStyle('/assets/admin.css');

echo $renderer->renderWithContext('admin/profile.phtml', new UserCardView(
    name: 'Anton',
    email: 'anton@example.com',
), $context);
```

## partialRenderWithContext()

`partialRenderWithContext(string $template, array|ViewDataInterface $data, ViewContext $context): string`

Назначение:

- advanced partial render
- рендерит только template
- использует переданный `ViewContext` (meta/css/js/layout state)

### partialRenderWithContext() with array

```php
$context = new ViewContext($renderer);
$context->addMeta('robots', 'noindex');

$html = $renderer->partialRenderWithContext('partials/header.phtml', [
    'title' => 'Settings',
], $context);
```

### partialRenderWithContext() with DTO

```php
$context = new ViewContext($renderer);

$html = $renderer->partialRenderWithContext('partials/user-row.phtml', new UserCardView(
    name: 'Anton',
    email: 'anton@example.com',
), $context);
```

## ViewContext API

`ViewContext` доступен в шаблоне как `$viewContext`.

Основные методы:

- `setLayout(string $template, array|LayoutTemplateDataInterface $data = [])`
- `clearLayout()`
- `partialRender(string $template, array|ViewDataInterface $data = [])`
- `addMeta(string $name, string $content, array $attributes = [])`
- `addStyle(string $href, array $attributes = [])`
- `addScript(string $src, array $attributes = [])`
- `renderMeta(): string`
- `renderStyles(): string`
- `renderScripts(): string`

### DTO access to context

Если DTO должен вызывать partial/layout API, реализуйте `ViewContextAwareInterface`.

```php
use PhpSoftBox\View\ViewContext;
use PhpSoftBox\View\ViewContextAwareInterface;
use PhpSoftBox\View\ViewDataInterface;

final readonly class LayoutView implements ViewDataInterface, ViewContextAwareInterface
{
    public function __construct(
        public string $content,
        private ?ViewContext $viewContext = null,
    ) {
    }

    public function withViewContext(ViewContext $context): object
    {
        return new self($this->content, $context);
    }

    public function renderPartial(string $template, array|ViewDataInterface $data = []): string
    {
        return $this->viewContext?->partialRender($template, $data) ?? '';
    }
}
```

## Helpers

В шаблонах используйте helper-функции:

- `html(mixed $value): string` — escaped output
- `raw(mixed $value): string` — trusted raw output

Пример:

```php
<h1><?= html($title) ?></h1>
<div><?= raw($trustedHtml) ?></div>
```

