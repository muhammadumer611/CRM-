<?php
namespace Helpers;

use Exception;
use Utils\Logger;

class UploadHelper {
    public static function handleUpload($fileArray, $destinationFolder, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = null) {
        $config = require __DIR__ . '/../config/app.php';
        $maxSize = $maxSize ?? $config['upload_max_size'];

        if (!isset($fileArray['error']) || is_array($fileArray['error'])) {
            throw new Exception('Invalid parameters.');
        }

        switch ($fileArray['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Exceeded filesize limit.');
            default:
                throw new Exception('Unknown errors.');
        }

        if ($fileArray['size'] > $maxSize) {
            throw new Exception('Exceeded filesize limit.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $ext = array_search(
            $finfo->file($fileArray['tmp_name']),
            $allowedTypes,
            true
        );

        if ($ext === false) {
            throw new Exception('Invalid file format.');
        }

        // Prevent path traversal
        $safeName = preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($fileArray['name']));
        $uniqueName = sprintf('%s_%s', uniqid(), $safeName);
        
        $uploadDir = __DIR__ . '/../uploads/' . $destinationFolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $uniqueName;

        if (!move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
            Logger::error('Failed to move uploaded file: ' . $fileArray['tmp_name']);
            throw new Exception('Failed to move uploaded file.');
        }

        return $destinationFolder . '/' . $uniqueName;
    }
}
