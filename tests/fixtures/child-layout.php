<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var string $content */
/** @var ViewContext $viewContext */

// Дочерний layout оборачивается в родительский.
$viewContext->setLayout('layout.php');

echo '<section>' . $content . '</section>';
