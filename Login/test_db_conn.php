<?php
echo "Testing DB connection...\n";
$start = microtime(true);
require_once 'config.php';
$end = microtime(true);
echo "Connection successful in " . ($end - $start) . "s\n";
?>
