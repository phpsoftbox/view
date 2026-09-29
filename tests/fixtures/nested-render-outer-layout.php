<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var ViewContext $viewContext */

$viewContext->setLayout('layout.php');

echo '[' . $viewContext->render('view.php', ['name' => 'Inner']) . ']';
