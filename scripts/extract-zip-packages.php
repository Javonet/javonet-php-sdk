<?php

echo "📦 Extracting Javonet component ZIP archives...\n\n";

$baseDir = dirname(__DIR__);
$vendorDir = $baseDir . '/vendor';
$zipMap = [
    'javonet-sdk.zip' => 'javonet/sdk',
    'javonet-core.zip' => 'javonet/core',
    'javonet-utils.zip' => 'javonet/utils',
    'javonet-binaries.zip' => 'Binaries'
];

if (!class_exists('ZipArchive')) {
    echo "❌ ZIP extension is not available in PHP!\n";
    echo "   Install php-zip extension: apt-get install php-zip\n";
    exit(1);
}

if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0755, true);
    echo "✅ Created directory: $vendorDir\n";
}

$javonetDir = $vendorDir . '/javonet';
if (!is_dir($javonetDir)) {
    mkdir($javonetDir, 0755, true);
    echo "✅ Created directory: $javonetDir\n";
}

$extractedCount = 0;
$totalCount = count($zipMap);

echo "🔍 Looking for ZIP archives in: $baseDir/packages/\n\n";

foreach ($zipMap as $zipFile => $path) {
    $zipPath = $baseDir . '/packages/' . $zipFile;

    if ($path === 'Binaries') {
        $targetDir =  $baseDir. '/' . $path;
    } else {
        $targetDir = $vendorDir . '/' . $path;
    }

    echo "📦 Processing: $zipFile → $path\n";

    if (!file_exists($zipPath)) {
        echo "  ⚠️  Missing file: $zipFile \n";
        continue;
    }

    try {
        if (is_dir($targetDir)) {
            removeDirectory($targetDir);
        }

        mkdir($targetDir, 0755, true);

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== TRUE) {
            throw new Exception("Cannot open ZIP archive: $zipFile (error code: $result)");
        }

        if ($zip->numFiles === 0) {
            throw new Exception("Archive $zipFile is empty");
        }

        $zip->extractTo($targetDir);
        $zip->close();

        echo "  ✅ Extracted to: $targetDir\n";

        $fileCount = countFiles($targetDir);
        echo "  📊 Extracted: $fileCount files\n";
        $extractedCount++;

    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "📋 Summary:\n";
if ($extractedCount > 0) {
    echo "🎉 Extracted $extractedCount/$totalCount components!\n\n";
} else {
    echo "✅ All components already extracted - nothing to do.\n\n";
}

echo "📁 javonet structure:\n";
showJavonetVendorStructure($vendorDir);
shoJavonetBinariesStructure($baseDir);

echo "\n✅ Components are available through PSR-4 autoloader:\n";
echo "   - use sdk\\ClassName;\n";

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function countFiles($dir) {
    if (!is_dir($dir)) {
        return 0;
    }

    $count = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
    } catch (Exception $e) {
        return 0;
    }
    return $count;
}

function showJavonetVendorStructure($vendorDir)
{
    if (!is_dir($vendorDir)) {
        echo "  ❌ Vendor directory does not exist\n";
        return;
    }

    $javonetDir = $vendorDir . '/javonet';
    if (!is_dir($javonetDir)) {
        echo "  ❌ vendor/javonet directory does not exist\n";
        return;
    }

    $components = ['sdk', 'core', 'utils'];

    foreach ($components as $component) {
        $componentDir = $javonetDir . '/' . $component;
        if (is_dir($componentDir)) {
            $fileCount = countFiles($componentDir);
            echo "  📁 vendor/javonet/$component/ ($fileCount files)\n";
        } else {
            echo "  ⚪ vendor/javonet/$component/ (does not exist)\n";
        }
    }
}

function shoJavonetBinariesStructure($baseDir)
{
    $binaries = $baseDir . '\Binaries';
    if (is_dir($binaries)) {
        $fileCount = countFiles($binaries);
        echo "  📁 Binaries ($fileCount files)\n";
    } else {
        echo "  ⚪ Binaries (does not exist)\n";
    }
}
