<?php

declare(strict_types=1);

/** @var string $name */

echo $this->view->render('layout.php', [
    'content' => 'Hello, ' . $name,
]);
