<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var string $content */
/** @var ViewContext $viewContext */

echo '<head>';
echo $viewContext->renderMeta();
echo $viewContext->renderStyles();
echo $viewContext->renderScripts();
echo '</head>';
echo '<main>' . $content . '</main>';
