<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileUploadSecurityService
{
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    private array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
    ];

    private int $maxFileSize = 2 * 1024 * 1024; // 2MB
    private int $maxImageWidth = 2048;
    private int $maxImageHeight = 2048;

    /**
     * Validate and secure file upload
     */
    public function validateUpload(UploadedFile $file, string $type = 'image'): array
    {
        try {
            // Basic validation
            $validationResult = $this->basicValidation($file, $type);
            if (!$validationResult['valid']) {
                return $validationResult;
            }

            // MIME type validation
            $mimeValidation = $this->validateMimeType($file);
            if (!$mimeValidation['valid']) {
                return $mimeValidation;
            }

            // Extension validation
            $extensionValidation = $this->validateExtension($file);
            if (!$extensionValidation['valid']) {
                return $extensionValidation;
            }

            // File size validation
            $sizeValidation = $this->validateFileSize($file);
            if (!$sizeValidation['valid']) {
                return $sizeValidation;
            }

            // Image-specific validation
            if ($type === 'image') {
                $imageValidation = $this->validateImage($file);
                if (!$imageValidation['valid']) {
                    return $imageValidation;
                }
            }

            // Virus scanning (if available)
            $virusScan = $this->scanForViruses($file);
            if (!$virusScan['valid']) {
                return $virusScan;
            }

            return [
                'valid' => true,
                'message' => 'File validation passed',
            ];

        } catch (\Exception $e) {
            Log::error('File validation error', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'type' => $type,
            ]);

            return [
                'valid' => false,
                'message' => 'File validation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Basic file validation
     */
    private function basicValidation(UploadedFile $file, string $type): array
    {
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'message' => 'File upload failed',
            ];
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'message' => 'File upload error: ' . $this->getUploadErrorMessage($file->getError()),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate MIME type
     */
    private function validateMimeType(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $clientMimeType = $file->getClientMimeType();

        // Check if MIME type is allowed
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedMimeTypes),
            ];
        }

        // Verify MIME type matches client-reported type
        if ($mimeType !== $clientMimeType) {
            Log::warning('MIME type mismatch detected', [
                'server_mime' => $mimeType,
                'client_mime' => $clientMimeType,
                'filename' => $file->getClientOriginalName(),
            ]);
        }

        return ['valid' => true];
    }

    /**
     * Validate file extension
     */
    private function validateExtension(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $guessedExtension = strtolower($file->guessExtension());

        // Check if extension is allowed
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'message' => 'File extension not allowed. Allowed extensions: ' . implode(', ', $this->allowedExtensions),
            ];
        }

        // Verify extension matches guessed extension
        if ($extension !== $guessedExtension) {
            Log::warning('Extension mismatch detected', [
                'client_extension' => $extension,
                'guessed_extension' => $guessedExtension,
                'filename' => $file->getClientOriginalName(),
            ]);
        }

        return ['valid' => true];
    }

    /**
     * Validate file size
     */
    private function validateFileSize(UploadedFile $file): array
    {
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'message' => 'File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate image dimensions and content
     */
    private function validateImage(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'message' => 'Invalid image file',
                ];
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Check dimensions
            if ($width > $this->maxImageWidth || $height > $this->maxImageHeight) {
                return [
                    'valid' => false,
                    'message' => 'Image dimensions exceed maximum allowed size (' . $this->maxImageWidth . 'x' . $this->maxImageHeight . ')',
                ];
            }

            // Check aspect ratio (prevent extremely wide or tall images)
            $aspectRatio = $width / $height;
            if ($aspectRatio > 3 || $aspectRatio < 0.33) {
                return [
                    'valid' => false,
                    'message' => 'Image aspect ratio is not acceptable',
                ];
            }

            // Verify image content matches MIME type
            $imageType = $imageInfo[2];
            $expectedTypes = [
                IMAGETYPE_JPEG => 'image/jpeg',
                IMAGETYPE_PNG => 'image/png',
                IMAGETYPE_GIF => 'image/gif',
            ];

            if (!isset($expectedTypes[$imageType])) {
                return [
                    'valid' => false,
                    'message' => 'Image type not supported',
                ];
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            Log::error('Image validation error', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
            ]);

            return [
                'valid' => false,
                'message' => 'Image validation failed',
            ];
        }
    }

    /**
     * Basic virus scanning (placeholder for integration with actual antivirus)
     */
    private function scanForViruses(UploadedFile $file): array
    {
        // This is a placeholder. In a real implementation, you would integrate
        // with an antivirus service like ClamAV or a cloud-based scanning service.
        
        // Check for obvious malicious patterns in file content
        try {
            $content = file_get_contents($file->getPathname());
            
            // Check for PHP code
            if (preg_match('/<\?php/i', $content)) {
                return [
                    'valid' => false,
                    'message' => 'File contains potentially malicious PHP code',
                ];
            }

            // Check for JavaScript code in images
            if (preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', $content)) {
                return [
                    'valid' => false,
                    'message' => 'File contains potentially malicious JavaScript code',
                ];
            }

            // Check for embedded executables
            if (preg_match('/MZ/i', substr($content, 0, 2))) {
                return [
                    'valid' => false,
                    'message' => 'File appears to contain executable code',
                ];
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            Log::error('Virus scan error', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
            ]);

            return [
                'valid' => false,
                'message' => 'Security scan failed',
            ];
        }
    }

    /**
     * Store file securely
     */
    public function storeSecurely(UploadedFile $file, string $path = 'uploads'): ?string
    {
        try {
            // Generate secure filename
            $extension = $file->getClientOriginalExtension();
            $filename = $this->generateSecureFilename($extension);
            
            // Store file
            $storedPath = $file->storeAs($path, $filename, 'public');
            
            if (!$storedPath) {
                return null;
            }

            // Set file permissions (read-only for owner)
            $fullPath = Storage::disk('public')->path($storedPath);
            chmod($fullPath, 0644);

            return $storedPath;

        } catch (\Exception $e) {
            Log::error('File storage error', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
            ]);

            return null;
        }
    }

    /**
     * Generate secure filename
     */
    private function generateSecureFilename(string $extension): string
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        $safeExtension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        
        return $timestamp . '_' . $random . '.' . $safeExtension;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
    }
}