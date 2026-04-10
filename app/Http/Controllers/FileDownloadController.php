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
        $fileRow = DB::selectOne('SELECT archivo, nombre_archivo, tipo_mime FROM "Evidencia_Digital" WHERE id_proyecto = ? LIMIT 1', [$id]);
        if ($fileRow) {
            $fileRow->archivo_evidencia = $fileRow->archivo;
            $fileRow->nombre_archivo_evidencia = $fileRow->nombre_archivo;
            $fileRow->mime_tipo_evidencia = $fileRow->tipo_mime;
        }
        return $this->serveFile($fileRow, 'proyecto_' . $id);
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
        if (!$fileRow || !$fileRow->archivo_evidencia) {
            abort(404, 'Archivo no encontrado');
        }

        $content = $fileRow->archivo_evidencia;
        
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
