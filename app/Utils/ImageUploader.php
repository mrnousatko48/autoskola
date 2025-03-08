<?php
namespace App\Utils;

use Nette\Http\FileUpload;

class ImageUploader
{
    /**
     * Uploads and converts an image to WebP format.
     *
     * @param FileUpload $file The uploaded file.
     * @param string $uploadDir The target directory (e.g., 'uploads/about').
     * @param string|null $default Default image path if upload fails.
     * @return string|null The path of the uploaded (converted) image, or the default.
     */
    public static function uploadImage(FileUpload $file, string $uploadDir, ?string $default = null): ?string
    {
        // Validate the file type
        if ($file->isOk() && $file->isImage() && in_array($file->getContentType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            // Generate a unique name (without extension, as we'll convert to webp)
            $uniqueName = uniqid() . '_' . pathinfo($file->getSanitizedName(), PATHINFO_FILENAME);
            // Temporary destination: preserve original file extension for now
            $tempPath = rtrim($uploadDir, '/') . '/' . $uniqueName . '.' . pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
            // Move the uploaded file to the temporary path
            $file->move($tempPath);
            
            // Load the image using GD based on its type
            $imageInfo = getimagesize($tempPath);
            if ($imageInfo === false) {
                return $default;
            }
            $type = $imageInfo[2];
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $imageResource = imagecreatefromjpeg($tempPath);
                    break;
                case IMAGETYPE_PNG:
                    $imageResource = imagecreatefrompng($tempPath);
                    break;
                case IMAGETYPE_GIF:
                    $imageResource = imagecreatefromgif($tempPath);
                    break;
                default:
                    return $default;
            }
            
            // Define new filename with .webp extension
            $newFileName = $uniqueName . '.webp';
            $newRelativePath = rtrim($uploadDir, '/') . '/' . $newFileName;
            $newDbPath = '/' . $newRelativePath;
            
            // Convert and save image as WebP (quality set to 80)
            if (!imagewebp($imageResource, $newRelativePath, 80)) {
                imagedestroy($imageResource);
                return $default;
            }
            imagedestroy($imageResource);
            
            // Remove the original file
            unlink($tempPath);
            
            return $newDbPath;
        }
        return $default;
    }
}
