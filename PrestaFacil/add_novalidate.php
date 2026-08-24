<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);
$count = 0;
foreach($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $newContent = preg_replace('/<form(?![^>]*\bnovalidate\b)/i', '<form novalidate', $content);
    if ($newContent !== $content) {
        file_put_contents($path, $newContent);
        $count++;
    }
}
echo "Modified $count files.\n";
