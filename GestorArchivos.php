<?php

class GestorArchivos
{
    /** @var string Ruta absoluta al directorio de subidas */
    private string $directorio;

    /** @var array Extensiones permitidas => MIME types válidos */
    private array $tiposPermitidos = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /** @var int Tamaño máximo permitido en bytes (por defecto 5 MB) */
    private int $tamanoMaximo;

    public function __construct(string $directorio, int $tamanoMaximoBytes = 5242880)
    {
        // Nos aseguramos de trabajar siempre con una ruta absoluta y normalizada
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $this->directorio = rtrim(realpath($directorio), DIRECTORY_SEPARATOR);
        $this->tamanoMaximo = $tamanoMaximoBytes;
    }

    /**
     * Procesa la subida de un archivo ($_FILES['archivo']) de forma segura.
     *
     * @param array  $archivo Elemento de $_FILES
     * @param string $titulo  Título ingresado por el usuario para reconocer el archivo
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function subir(array $archivo, string $titulo): array
    {
        // 0. Validar el título proporcionado por el usuario.
        //    Se limpia de etiquetas HTML y se limita su longitud; nunca se
        //    usa para construir rutas de archivo, solo como texto de exhibición.
        $titulo = trim(strip_tags($titulo));
        if ($titulo === '') {
            return $this->error('Debes ingresar un título para el archivo.');
        }
        $longitudTitulo = function_exists('mb_strlen') ? mb_strlen($titulo) : strlen($titulo);
        if ($longitudTitulo > 100) {
            return $this->error('El título no puede superar los 100 caracteres.');
        }

        // 1. Verificar que no haya errores de subida de PHP
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return $this->error('Ocurrió un error al subir el archivo.');
        }

        // 2. Verificar que el archivo llegó realmente vía HTTP POST
        //    (protección básica contra manipulación de rutas locales)
        if (!is_uploaded_file($archivo['tmp_name'])) {
            return $this->error('Solicitud de subida inválida.');
        }

        // 3. Validar tamaño
        if ($archivo['size'] <= 0 || $archivo['size'] > $this->tamanoMaximo) {
            $maxMB = round($this->tamanoMaximo / 1048576, 1);
            return $this->error("El archivo supera el tamaño máximo permitido ({$maxMB} MB).");
        }

        // 4. Validar extensión (lista blanca)
        $nombreOriginal = $archivo['name'];
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (!array_key_exists($extension, $this->tiposPermitidos)) {
            return $this->error('Tipo de archivo no permitido. Solo se aceptan PDF, JPG y PNG.');
        }

        // 5. Validar el tipo MIME real inspeccionando el contenido del archivo,
        //    NO el Content-Type reportado por el navegador (fácilmente falsificable)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, $this->tiposPermitidos[$extension], true)) {
            return $this->error('El contenido del archivo no coincide con su extensión.');
        }

        // 6. Generar un nombre de archivo aleatorio y seguro.
        //    Esto evita: sobrescritura, path traversal en el nombre,
        //    y ejecución de scripts subidos con doble extensión (ej. foto.php.jpg)
        $nombreSeguro = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaDestino = $this->directorio . DIRECTORY_SEPARATOR . $nombreSeguro;

        // 7. Mover el archivo fuera de la carpeta temporal
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return $this->error('No se pudo guardar el archivo en el servidor.');
        }

        // 8. Asegurar permisos no ejecutables sobre el archivo guardado
        chmod($rutaDestino, 0644);

        // Guardamos el título ingresado por el usuario como metadato,
        // para mostrarlo en el listado en lugar del nombre técnico interno.
        $this->guardarTitulo($nombreSeguro, $titulo, $nombreOriginal, $extension);

        return $this->exito("«{$titulo}» se subió correctamente.");
    }

    /**
     * Devuelve un listado de los archivos existentes en el directorio,
     * con nombre original, tamaño y fecha de modificación.
     */
    public function listar(): array
    {
        $archivos = [];
        $items = scandir($this->directorio);

        foreach ($items as $item) {
            // Se ignoran '.', '..', el archivo de metadatos y cualquier
            // archivo oculto de configuración (como .htaccess)
            if ($item === '.' || $item === '..' || $item === 'metadatos.json' || str_starts_with($item, '.')) {
                continue;
            }

            $rutaCompleta = $this->directorio . DIRECTORY_SEPARATOR . $item;

            if (is_file($rutaCompleta)) {
                $meta = $this->obtenerMetadato($item);
                $archivos[] = [
                    'nombre_sistema'   => $item,
                    'titulo'           => $meta['titulo'],
                    'nombre_original'  => $meta['original'],
                    'extension'        => strtoupper(pathinfo($item, PATHINFO_EXTENSION)),
                    'tamano'           => $this->formatearTamano(filesize($rutaCompleta)),
                    'fecha'            => date('d/m/Y H:i', filemtime($rutaCompleta)),
                    'timestamp'        => filemtime($rutaCompleta),
                ];
            }
        }

        // Orden descendente por fecha de subida (más reciente primero)
        usort($archivos, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $archivos;
    }

    /**
     * Elimina un archivo del directorio de forma segura, validando
     * el nombre para prevenir ataques de Path Traversal.
     */
    public function eliminar(string $nombre): array
    {
        // 1. Rechazar cualquier nombre que contenga separadores de ruta
        //    o secuencias de "subir de directorio"
        if (
            $nombre === '' ||
            str_contains($nombre, '/') ||
            str_contains($nombre, '\\') ||
            str_contains($nombre, '..') ||
            str_contains($nombre, "\0")
        ) {
            return $this->error('Nombre de archivo inválido.');
        }

        // 2. Aceptar solo el patrón exacto que la propia clase genera
        //    al subir archivos: 32 caracteres hexadecimales + extensión permitida
        $extensiones = implode('|', array_keys($this->tiposPermitidos));
        if (!preg_match('/^[a-f0-9]{32}\.(' . $extensiones . ')$/i', $nombre)) {
            return $this->error('Nombre de archivo inválido.');
        }

        // 3. Construir la ruta y verificar con realpath que sigue
        //    perteneciendo al directorio de subidas (defensa en profundidad)
        $rutaPropuesta = $this->directorio . DIRECTORY_SEPARATOR . $nombre;
        $rutaReal = realpath($rutaPropuesta);

        if ($rutaReal === false || !str_starts_with($rutaReal, $this->directorio)) {
            return $this->error('Archivo no encontrado.');
        }

        if (!is_file($rutaReal)) {
            return $this->error('El archivo no existe.');
        }

        if (!unlink($rutaReal)) {
            return $this->error('No se pudo eliminar el archivo.');
        }

        $this->eliminarNombreOriginal($nombre);

        return $this->exito('Archivo eliminado correctamente.');
    }

    /**
     * Devuelve la ruta absoluta de un archivo ya validado, para
     * ser usada por un script de descarga controlada.
     * Reutiliza la misma validación estricta que eliminar().
     */
    public function rutaDescarga(string $nombre): ?string
    {
        $extensiones = implode('|', array_keys($this->tiposPermitidos));
        if (!preg_match('/^[a-f0-9]{32}\.(' . $extensiones . ')$/i', $nombre)) {
            return null;
        }

        $rutaReal = realpath($this->directorio . DIRECTORY_SEPARATOR . $nombre);

        if ($rutaReal === false || !str_starts_with($rutaReal, $this->directorio) || !is_file($rutaReal)) {
            return null;
        }

        return $rutaReal;
    }

    /**
     * Devuelve un nombre de archivo amigable para la descarga, basado en el
     * título que el usuario le dio (en vez del nombre técnico aleatorio).
     */
    public function nombreDescarga(string $nombreSistema): string
    {
        $meta = $this->obtenerMetadato($nombreSistema);
        $extension = pathinfo($nombreSistema, PATHINFO_EXTENSION);

        // Convertimos el título en un nombre de archivo seguro para el
        // encabezado Content-Disposition (sin caracteres especiales ni acentos raros)
        $base = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $meta['titulo']);
        $base = trim($base) !== '' ? trim($base) : 'archivo';

        return $base . '.' . $extension;
    }

    // ---------------------------------------------------------------
    // Métodos auxiliares privados
    // ---------------------------------------------------------------

    private function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }

    private function exito(string $mensaje): array
    {
        return ['exito' => true, 'mensaje' => $mensaje];
    }

    private function formatearTamano(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $unidades[$i];
    }

    /**
     * Los nombres originales se guardan en un pequeño archivo JSON
     * de metadatos (fuera de lo crítico para la seguridad, solo
     * para mostrar información amigable en el listado).
     */
    private function rutaMetadatos(): string
    {
        return $this->directorio . DIRECTORY_SEPARATOR . 'metadatos.json';
    }

    private function guardarTitulo(string $nombreSeguro, string $titulo, string $nombreOriginal, string $extension): void
    {
        $meta = $this->cargarMetadatos();
        $meta[$nombreSeguro] = [
            'titulo'   => htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'),
            'original' => htmlspecialchars($nombreOriginal, ENT_QUOTES, 'UTF-8'),
        ];
        file_put_contents($this->rutaMetadatos(), json_encode($meta), LOCK_EX);
    }

    /**
     * Devuelve ['titulo' => ..., 'original' => ...] para un archivo dado,
     * con valores de respaldo si no hay metadato (compatibilidad hacia atrás).
     */
    private function obtenerMetadato(string $nombreSeguro): array
    {
        $meta = $this->cargarMetadatos();
        $registro = $meta[$nombreSeguro] ?? null;

        if (is_array($registro)) {
            return [
                'titulo'   => $registro['titulo'] ?? $nombreSeguro,
                'original' => $registro['original'] ?? $nombreSeguro,
            ];
        }

        // Formato antiguo (versión previa solo guardaba el nombre original como string)
        if (is_string($registro)) {
            return ['titulo' => $registro, 'original' => $registro];
        }

        return ['titulo' => $nombreSeguro, 'original' => $nombreSeguro];
    }

    private function eliminarNombreOriginal(string $nombreSeguro): void
    {
        $meta = $this->cargarMetadatos();
        unset($meta[$nombreSeguro]);
        file_put_contents($this->rutaMetadatos(), json_encode($meta), LOCK_EX);
    }

    private function cargarMetadatos(): array
    {
        $ruta = $this->rutaMetadatos();
        if (!is_file($ruta)) {
            return [];
        }
        $contenido = file_get_contents($ruta);
        $data = json_decode($contenido, true);
        return is_array($data) ? $data : [];
    }
}
