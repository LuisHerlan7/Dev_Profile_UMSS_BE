<?php
$lines = file(__DIR__ . '/storage/logs/laravel.log');
for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (strpos($lines[$i], 'local.ERROR') !== false) {
        $out = "LATEST ERROR:\n";
        for ($j = $i; $j < min(count($lines), $i + 60); $j++) {
            $out .= $lines[$j];
        }
        file_put_contents(__DIR__ . '/scratch/latest.txt', $out);
        break;
    }
}
echo "Done\n";
