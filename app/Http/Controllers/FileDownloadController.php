<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function downloadExperiencia($id)
    {
        $fileRow = DB::selectOne('SELECT archivo_evidencia, nombre_archivo_evidencia, mime_tipo_evidencia FROM "Experiencia_Laboral" WHERE id_experiencia = ?', [$id]);
        return $this->serveFile($fileRow, 'experiencia_' . $id);
    }

    public function downloadFormacion($id)
    {
        $fileRow = DB::selectOne('SELECT archivo_evidencia, nombre_archivo_evidencia, mime_tipo_evidencia FROM "Formacion_Academica" WHERE id_formacion = ?', [$id]);
        return $this->serveFile($fileRow, 'formacion_' . $id);
    }

    public function downloadProyecto($id)
    {
        $fileRow = DB::selectOne('SELECT archivo, nombre_archivo, tipo_mime, url_enlace FROM "Evidencia_Digital" WHERE id_proyecto = ? LIMIT 1', [$id]);
        if ($fileRow) {
            $fileRow->archivo_evidencia = $fileRow->archivo;
            $fileRow->nombre_archivo_evidencia = $fileRow->nombre_archivo;
            $fileRow->mime_tipo_evidencia = $fileRow->tipo_mime;
        }
        return $this->serveFile($fileRow, 'proyecto_' . $id);
    }

    public function downloadEvidencia($id)
    {
        $fileRow = DB::selectOne('SELECT archivo as archivo_evidencia, nombre_archivo as nombre_archivo_evidencia, tipo_mime as mime_tipo_evidencia, url_enlace FROM "Evidencia_Digital" WHERE id_evidencia = ?', [$id]);
        return $this->serveFile($fileRow, 'evidencia_' . $id);
    }

    public function getAvatar($id)
    {
        $fileRow = DB::selectOne('SELECT fotografia as archivo_evidencia FROM "Usuario" WHERE id_usuario = ?', [$id]);
        if ($fileRow && $fileRow->archivo_evidencia) {
            $fileRow->nombre_archivo_evidencia = "avatar_$id";
            // El tipo MIME se detectará automáticamente en serveFile
        }
        return $this->serveFile($fileRow, 'avatar_' . $id);
    }

    private function serveFile($fileRow, $defaultName)
    {
        if (!$fileRow) {
            abort(404, 'Archivo no encontrado');
        }

        $content = $fileRow->archivo_evidencia ?? null;
        
        // Si no hay blob en DB, intentar recuperarlo del disco si hay una URL/Path
        if (!$content && !empty($fileRow->url_enlace)) {
            $url = $fileRow->url_enlace;
            // Extraer la parte relativa después de /storage/
            $parts = explode('/storage/', $url);
            $relativePath = end($parts);
            
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
                $content = \Illuminate\Support\Facades\Storage::disk('public')->get($relativePath);
            }
        }

        if (!$content) {
            abort(404, 'No se pudo recuperar el contenido del archivo.');
        }
        
        // Manejar recursos de Postgres
        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        // Manejar formato hexadecimal de Postgres (\x...)
        if (is_string($content) && strpos($content, '\x') === 0) {
            $hex = substr($content, 2);
            if (strlen($hex) % 2 === 0) {
                $content = hex2bin($hex);
            }
        }

        // Detectar MIME tipo si no está presente o forzar detección para mayor seguridad
        $mime = $fileRow->mime_tipo_evidencia ?? null;
        if (!$mime && is_string($content)) {
            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($content);
            } catch (\Throwable $e) {
                $mime = 'application/octet-stream';
            }
        }

        $headers = [
            'Content-Type' => $mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($fileRow->nombre_archivo_evidencia ?? $defaultName) . '"',
            'Cache-Control' => 'max-age=86400, public',
        ];

        return response($content, 200, $headers);
    }
}
