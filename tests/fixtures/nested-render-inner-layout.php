<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var ViewContext $viewContext */

echo '[' . $viewContext->render('with-auto-layout.php', ['name' => 'Inner']) . ']';
