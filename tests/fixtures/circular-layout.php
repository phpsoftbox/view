<?php

declare(strict_types=1);
use PhpSoftBox\View\ViewContext;

/** @var ViewContext $viewContext */

// Layout ссылается сам на себя.
$viewContext->setLayout('circular-layout.php');

echo 'x';
