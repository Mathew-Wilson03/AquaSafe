<?php
$dir = __DIR__;
$files = glob($dir . "/*.php");

echo "SCANNING FOR ILLEGAL WHITESPACE/BOM...\n";

foreach ($files as $file) {
    if (basename($file) === "clean_php_files.php") continue;
    
    $content = file_get_contents($file);
    if ($content === false) continue;
    $filename = basename($file);
    $changed = false;

    // Check for BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo "[BOM FOUND] $filename\n";
        $content = substr($content, 3);
        $changed = true;
    }
    
    // Check for whitespace before <?php
    if (preg_match('/^\s+<\?php/i', $content)) {
        echo "[WHITESPACE BEFORE PHP FOUND] $filename\n";
        $content = preg_replace('/^\s+<\?php/i', '<?php', $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "[FIXED] $filename\n";
    }
}

echo "SCAN COMPLETE.\n";
?>
