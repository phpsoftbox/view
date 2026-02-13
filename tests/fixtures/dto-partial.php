<?php

declare(strict_types=1);
use PhpSoftBox\View\Tests\DtoView;

/** @var DtoView $this */

echo '<h1>' . html($this->title) . '</h1>';
echo $this->renderPartial('dto-partial-message.php', ['message' => $this->message]);
