<?php
/**
 * Configuración de Colegio8.
 *
 * 1) Copiar este archivo como `config.php` (misma carpeta).
 * 2) Ajustar los datos de conexión a MySQL/MariaDB.
 * 3) Dejar BASE_URL vacío para autodetección, o fijarlo si la app se
 *    sirve desde una URL distinta (p. ej. un dominio propio).
 *
 * NUNCA subir `config.php` con credenciales reales a un repositorio.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'colegio8');
define('DB_USER', 'root');
define('DB_PASS', '');

// Vacío: se calcula solo a partir de la URL. Fijar solo si hace falta.
define('BASE_URL', '');

// Límite por archivo subido (MB). El valor de la BD puede sobrescribirlo.
define('LIMITE_ARCHIVO_MB', 5);