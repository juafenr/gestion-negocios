<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Guarda fotos comprobadas fuera de las rutas públicas. */
class Imagen_catalogo
{
    private function directorio()
    {
        return APPPATH . 'uploads/catalogo/';
    }

    public function guardar($archivo)
    {
        if (!is_array($archivo) || !isset($archivo['error']) || is_array($archivo['error'])) {
            throw new InvalidArgumentException('Selecciona una imagen válida.');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo subir la imagen. Intenta nuevamente.');
        }
        if (!isset($archivo['size'], $archivo['tmp_name']) || $archivo['size'] < 1 || $archivo['size'] > 3 * 1024 * 1024
            || !is_uploaded_file($archivo['tmp_name'])) {
            throw new InvalidArgumentException('La imagen debe pesar como máximo 3 MB.');
        }
        $info = @getimagesize($archivo['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] > 4000 || $info[1] > 4000
            || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), TRUE)) {
            throw new InvalidArgumentException('Usa una imagen JPG, PNG o WebP de hasta 4000 × 4000 píxeles.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $formatos = array(IMAGETYPE_JPEG=>array('image/jpeg','jpg'), IMAGETYPE_PNG=>array('image/png','png'), IMAGETYPE_WEBP=>array('image/webp','webp'));
        if ($mime !== $formatos[$info[2]][0] || $info['mime'] !== $mime) {
            throw new InvalidArgumentException('El archivo no coincide con su formato de imagen.');
        }
        $directorio = $this->directorio();
        if (!is_dir($directorio) && !mkdir($directorio, 0700, TRUE)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de imágenes.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.' . $formatos[$info[2]][1];
        $ruta = $directorio . $nombre;
        $ok = move_uploaded_file($archivo['tmp_name'], $ruta);
        if (!$ok) { throw new RuntimeException('No se pudo guardar la imagen.'); }
        return $nombre;
    }

    public function eliminar($nombre)
    {
        if (is_string($nombre) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $nombre)) {
            $ruta = $this->directorio() . $nombre;
            if (is_file($ruta)) { @unlink($ruta); }
        }
    }

    public function servir($nombre)
    {
        if (!is_string($nombre) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $nombre)) { return FALSE; }
        $ruta = $this->directorio() . $nombre;
        if (!is_file($ruta)) { return FALSE; }
        $tipos = array('jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp');
        header('Content-Type: ' . $tipos[pathinfo($nombre, PATHINFO_EXTENSION)]);
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, max-age=300');
        readfile($ruta);
        return TRUE;
    }
}
