<?php

declare(strict_types=1);
use PhpSoftBox\View\Tests\EmailConfirmView;

/** @var EmailConfirmView $this */
?>
<h1><?= $this->status ?></h1>
<p><?= html($this->message) ?></p>
