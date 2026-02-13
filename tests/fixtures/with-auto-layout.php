<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var string $name */
/** @var ViewContext $viewContext */

$viewContext->setLayout('layout.php');

echo 'Hello, ' . $name;
