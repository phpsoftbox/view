<?php

declare(strict_types=1);

echo '<p>partial html';

// Шаблон открывает собственный буфер и падает, не закрыв его.
ob_start();
echo 'inner';

throw new RuntimeException('Template failed');
