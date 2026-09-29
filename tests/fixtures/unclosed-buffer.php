<?php

declare(strict_types=1);

echo 'A';

// Шаблон открывает буфер и не закрывает его.
ob_start();
echo 'B';
