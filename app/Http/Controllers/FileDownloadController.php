<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FileDownloadController extends Controller
{
    public function getAvatar(int $id): Response
    {
        $row = DB::selectOne('SELECT fotografia FROM "Usuario" WHERE id_usuario = ? LIMIT 1', [$id]);
        if (! $row || empty($row->fotografia)) {
            abort(404, 'Avatar no encontrado');
        }

        return $this->binaryResponse($row->fotografia, 'image/jpeg');
    }

    public function downloadExperiencia(int $id): Response
    {
        $row = DB::selectOne('
            SELECT archivo_evidencia, mime_tipo_evidencia AS tipo_mime_evidencia, nombre_archivo_evidencia
            FROM "Experiencia_Laboral"
            WHERE id_experiencia = ?
            LIMIT 1
        ', [$id]);

        if (! $row || empty($row->archivo_evidencia)) {
            abort(404, 'Archivo no encontrado');
        }

        return $this->binaryResponse(
            $row->archivo_evidencia,
            (string) ($row->tipo_mime_evidencia ?? 'application/octet-stream'),
            (string) ($row->nombre_archivo_evidencia ?? ('experiencia-' . $id))
        );
    }

    public function downloadFormacion(int $id): Response
    {
        $row = DB::selectOne('
            SELECT archivo_evidencia, mime_tipo_evidencia AS tipo_mime_evidencia, nombre_archivo_evidencia
            FROM "Formacion_Academica"
            WHERE id_formacion = ?
            LIMIT 1
        ', [$id]);

        if (! $row || empty($row->archivo_evidencia)) {
            abort(404, 'Archivo no encontrado');
        }

        return $this->binaryResponse(
            $row->archivo_evidencia,
            (string) ($row->tipo_mime_evidencia ?? 'application/octet-stream'),
            (string) ($row->nombre_archivo_evidencia ?? ('formacion-' . $id))
        );
    }

    public function downloadProyecto(int $id): Response
    {
        $row = DB::selectOne('
            SELECT archivo, tipo_mime, nombre_archivo
            FROM "Evidencia_Digital"
            WHERE id_proyecto = ?
              AND archivo IS NOT NULL
            ORDER BY id_evidencia DESC
            LIMIT 1
        ', [$id]);

        if (! $row || empty($row->archivo)) {
            abort(404, 'Archivo no encontrado');
        }

        return $this->binaryResponse(
            $row->archivo,
            (string) ($row->tipo_mime ?? 'application/octet-stream'),
            (string) ($row->nombre_archivo ?? ('proyecto-' . $id))
        );
    }

    public function downloadEvidencia(int $id): Response
    {
        $row = DB::selectOne('
            SELECT archivo, tipo_mime, nombre_archivo, url_enlace
            FROM "Evidencia_Digital"
            WHERE id_evidencia = ?
            LIMIT 1
        ', [$id]);

        if (! $row) {
            abort(404, 'Evidencia no encontrada');
        }

        if (empty($row->archivo)) {
            if (!empty($row->url_enlace)) {
                $parsedUrl = parse_url($row->url_enlace);
                $path = $parsedUrl['path'] ?? '';
                if (str_contains($path, '/storage/')) {
                    $storagePath = str_replace('/storage/', 'public/', $path);
                    if (\Illuminate\Support\Facades\Storage::disk('local')->exists($storagePath)) {
                        $binary = \Illuminate\Support\Facades\Storage::disk('local')->get($storagePath);
                        return $this->binaryResponse(
                            $binary,
                            (string) ($row->tipo_mime ?? 'application/octet-stream'),
                            (string) ($row->nombre_archivo ?? ('evidencia-' . $id))
                        );
                    }
                }
            }
            abort(404, 'El archivo físico de la evidencia no se encuentra disponible.');
        }

        return $this->binaryResponse(
            $row->archivo,
            (string) ($row->tipo_mime ?? 'application/octet-stream'),
            (string) ($row->nombre_archivo ?? ('evidencia-' . $id))
        );
    }

    private function binaryResponse(mixed $bytea, string $contentType, ?string $downloadName = null): Response
    {
        $binary = $this->decodePostgresBytea($bytea);

        $headers = [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($downloadName) {
            $headers['Content-Disposition'] = 'inline; filename="' . addslashes($downloadName) . '"';
        }

        return response($binary, 200, $headers);
    }

    private function decodePostgresBytea(mixed $value): string
    {
        if (is_resource($value)) {
            return (string) stream_get_contents($value);
        }

        $raw = (string) $value;

        if (str_starts_with($raw, '\\x')) {
            $hex = substr($raw, 2);
            $decoded = @hex2bin($hex);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        $unescaped = @pg_unescape_bytea($raw);
        if ($unescaped !== false) {
            return $unescaped;
        }

        return $raw;
    }
}
