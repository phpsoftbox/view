<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var string $name */
/** @var ViewContext $viewContext */

$viewContext->addMeta('description', 'Mail preview');
$viewContext->addStyle('/assets/app.css');
$viewContext->addScript('/assets/app.js', ['defer' => true]);
$viewContext->setLayout('assets-layout.php');

echo 'Hello, ' . $name;
