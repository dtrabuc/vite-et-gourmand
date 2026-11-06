<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\FileException;

/**
 * Enregistrement sécurisé d'une image envoyée par formulaire dans public/uploads/.
 */
final class UploadFile
{
    private const MAX_SIZE = 5 * 1024 * 1024;

    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return string Chemin public de l'image, par exemple /uploads/menus/abc.jpg
     */
    public static function upload(string $field = 'file', string $subDirectory = ''): string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            throw new FileException('Aucun fichier n’a été envoyé.');
        }

        $file = $_FILES[$field];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new FileException(match ($error) {
                UPLOAD_ERR_PARTIAL => 'Le fichier n’a été envoyé que partiellement.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n’a été envoyé.',
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille autorisée (5 Mo).',
                default => 'L’envoi du fichier a échoué.',
            });
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new FileException('Fichier envoyé invalide.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new FileException('Le fichier dépasse la taille autorisée (5 Mo).');
        }

        // Le type est déterminé par le contenu du fichier, jamais par son nom.
        $info = @getimagesize($tmpName);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';

        if (!isset(self::ALLOWED[$mime])) {
            throw new FileException('Format d’image non accepté (JPEG, PNG ou WebP).');
        }

        $subDirectory = trim(preg_replace('/[^a-z0-9_-]/i', '', $subDirectory) ?? '');
        $directory = self::baseDirectory() . ($subDirectory !== '' ? DIRECTORY_SEPARATOR . $subDirectory : '');

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new FileException('Impossible de créer le dossier d’envoi.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];

        if (!move_uploaded_file($tmpName, $directory . DIRECTORY_SEPARATOR . $fileName)) {
            throw new FileException('Impossible d’enregistrer le fichier.');
        }

        return '/uploads/' . ($subDirectory !== '' ? $subDirectory . '/' : '') . $fileName;
    }

    /**
     * Supprime un fichier précédemment envoyé. Les URL externes sont ignorées.
     */
    public static function remove(string $publicPath): void
    {
        if (!preg_match('#^/uploads/(?:([a-z0-9_-]+)/)?([a-f0-9]{32}\.(?:jpg|png|webp))$#i', $publicPath, $matches)) {
            return;
        }

        $path = self::baseDirectory()
            . ($matches[1] !== '' ? DIRECTORY_SEPARATOR . $matches[1] : '')
            . DIRECTORY_SEPARATOR . $matches[2];

        if (is_file($path) && !unlink($path)) {
            throw new FileException('Impossible de supprimer l’image.');
        }
    }

    private static function baseDirectory(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
