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
            $fileRow->nombre_archivo_evidencia = "avatar_$id.jpg";
            $fileRow->mime_tipo_evidencia = "image/jpeg";
        }
        return $this->serveFile($fileRow, 'avatar_' . $id);
    }

    private function serveFile($fileRow, $defaultName)
    {
        if (!$fileRow || !$fileRow->archivo_evidencia) {
            abort(404, 'Archivo no encontrado');
        }

        $content = $fileRow->archivo_evidencia;
        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        // Si PostgreSQL lo devuelve en hexadecimal estilizamos \x...
        if (strpos($content, '\x') === 0) {
            $content = hex2bin(substr($content, 2));
        }

        $headers = [
            'Content-Type' => $fileRow->mime_tipo_evidencia ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($fileRow->nombre_archivo_evidencia ?? $defaultName) . '"',
            'Cache-Control' => 'max-age=86400, public',
        ];

        return response($content, 200, $headers);
    }
}
