<?php
namespace App\Core;

use App\Core\Exception\FileException;
use Exception;

class UploadFile
{


  // @return name of file when uploaded
  public static function upload(): string|null
  {
    $fileName = $_FILES['file']['name'];
    $file_tmp_name = $_FILES['file']['tmp_name'];
    $fileMimeType = mime_content_type($file_tmp_name);
    $fileSize = $_FILES['file']['size'];
    $extension = explode('.', $fileName);
    $extension = end($extension);
    $extension = strtolower($extension);

    $fileName = rtrim($fileName, ". .$extension");
    $image_error = $_FILES['file']['error'];
    debugPrint($_FILES['file']);
    if ($image_error === 0) {
      // Enregistrer l'image dans notre dossier uploads
      if ($fileSize > MAX_FILE_SIZE) {
        throw new FileException("File exceeds upload_max_filesize");
      } elseif (
        !in_array($extension, ALLOWED_EXTENSIONS_FILE)
        && !in_array($fileMimeType, ALLOWED_EXTENSIONS_FILE)
      ) {
        throw new FileException("Invalid format file");
      }
      $fileName = $fileName . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
      $destination = "uploads/" . $fileName;
      if (!file_exists($destination)) {
        move_uploaded_file($file_tmp_name, $destination);

        echo " L'image a bien été enregistrée";

        return $fileName;

      } else {
        throw new FileException("File already exist");
      }


    } else {
      if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
        switch ($_FILES["file"]["error"]) {
          case UPLOAD_ERR_PARTIAL:
            throw new FileException("File only partially uploaded");

          case UPLOAD_ERR_NO_FILE:
            throw new FileException("No file was uploaded");
          case UPLOAD_ERR_EXTENSION:
            throw new FileException("File upload stopped by a PHP extension");
          case UPLOAD_ERR_FORM_SIZE:
            throw new FileException("File exceeds MAX_FILE_SIZE in the HTML form");
          case UPLOAD_ERR_INI_SIZE:
            throw new FileException("File exceeds upload_max_filesize");
          case UPLOAD_ERR_NO_TMP_DIR:
            throw new FileException("Temporary folder not found");
          case UPLOAD_ERR_CANT_WRITE:
            throw new FileException("Failed to write file");
          default:
            throw new FileException('Unknown upload error');

        }
      }
    }

    return null;
  }

  public static function remove(string $fileName)
  {
    try {
      unlink("uploads/" . $fileName);
    } catch (Exception $e) {
      throw new FileException("Unable to delete image : " . $e->getMessage());

    }
  }
}