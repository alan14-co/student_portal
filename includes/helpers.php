<?php
/**
 * Save a base64 data URL (from the Cropper.js image editor) as an image file.
 * Returns the new filename on success, or false on failure/invalid input.
 *
 * @param string $dataUrl  e.g. "data:image/jpeg;base64,/9j/4AAQ..."
 * @param string $destDir  Directory to save into (with trailing slash)
 * @return string|false
 */
function save_base64_image($dataUrl, $destDir) {
    if (empty($dataUrl) || strpos($dataUrl, 'data:image/') !== 0) {
        return false;
    }

    // Extract mime type and base64 payload
    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $dataUrl, $matches)) {
        return false;
    }

    $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
    $base64Data = $matches[2];

    $imageData = base64_decode($base64Data, true);
    if ($imageData === false) {
        return false;
    }

    // Size check (2MB limit)
    if (strlen($imageData) > 2 * 1024 * 1024) {
        return false;
    }

    $newName = 'student_' . time() . '_' . uniqid() . '.' . $ext;
    $fullPath = rtrim($destDir, '/') . '/' . $newName;

    if (file_put_contents($fullPath, $imageData) === false) {
        return false;
    }

    return $newName;
}
