<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class PluginDownloadController extends Controller
{
    /**
     * NeuralCart WordPress Plugin টা zip করে download দাও।
     * শুধু logged-in admin/seller দেখতে পাবে।
     */
    public function download(Request $request)
    {
        // Auth check
        if (!Auth::check()) {
            abort(401);
        }

        $pluginDir  = base_path('wordpress-plugin/neuralcart');
        $zipName    = 'neuralcart-plugin-v1.0.zip';
        $tmpZip     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

        if (!is_dir($pluginDir)) {
            abort(404, 'Plugin files not found on server.');
        }

        // ZIP তৈরি করো
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        $this->addDirToZip($zip, $pluginDir, 'neuralcart');
        $zip->close();

        return response()->download($tmpZip, $zipName, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zipName . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Directory কে recursively zip এ add করো
     */
    private function addDirToZip(ZipArchive $zip, string $dir, string $zipBase): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath    = $file->getRealPath();
            $relativePath = $zipBase . DIRECTORY_SEPARATOR . substr($filePath, strlen($dir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
