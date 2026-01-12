<?php
$content = file_get_contents('admin_dashboard.php');
$start = strpos($content, '<script>');
$end = strrpos($content, '</script>');

if ($start === false || $end === false) {
    die("Script tags not found.");
}

$js = substr($content, $start + 8, $end - $start - 8);
file_put_contents('admin_dashboard.js', $js);
echo "Extracted JS to admin_dashboard.js\n";
?>
