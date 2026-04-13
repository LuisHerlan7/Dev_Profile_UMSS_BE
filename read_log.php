<?php
$lines = file(__DIR__ . '/storage/logs/laravel.log');
for ($i = count($lines) - 1; $i >= max(0, count($lines) - 200); $i--) {
    echo $lines[$i];
}
