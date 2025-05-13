<?php 
namespace App\Utils;

use Nette\Http\FileUpload;

class PdfUploader
{
    public static function uploadPdf(FileUpload $file, string $uploadDir): ?string
    {
        if ($file->isOk() && $file->getContentType() === 'application/pdf') {
            $uniqueName = uniqid() . '_' . $file->getSanitizedName();
            $newPath = rtrim($uploadDir, '/') . '/' . $uniqueName;
            $file->move($newPath);
            // Vrátí relativní cestu od kořene www
            return '/uploads/pdf/' . $uniqueName;
        }
        return null;
    }

    public static function uploadMultiplePdfs(array $files, string $uploadDir): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof FileUpload) {
                $path = self::uploadPdf($file, $uploadDir);
                if ($path) {
                    $paths[] = $path;
                }
            }
        }
        return $paths;
    }
}