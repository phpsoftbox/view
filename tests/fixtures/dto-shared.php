<?php

declare(strict_types=1);

use PhpSoftBox\View\Tests\EmailConfirmView;

/** @var EmailConfirmView $this */
/** @var string $brand */
?>
<p><?= html($brand) ?>: <?= html($this->message) ?></p>
