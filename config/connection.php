<?php
/**
 * Database Connection File
 * 
 * TODO: Move credentials to config.php for better security
 * For production, use environment variables or a secure config file
 */

// Try to load config file if it exists
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    $config = require $config_file;
    $server = $config['database']['host'] ?? 'localhost';
    $username = $config['database']['username'] ?? 'root';
    $password = $config['database']['password'] ?? '';
    $databasename = $config['database']['database'] ?? 'vms_db';
} else {
    // Fallback to hardcoded values (not recommended for production)
    $server = "localhost";
    $username = "root";
    $password = "Sowmith@0707";
    $databasename = "vms_db";
}

// Try socket (localhost) first. If it fails because the MySQL socket
// isn't available, retry using TCP to 127.0.0.1 which avoids socket use.
$conn = new mysqli($server, $username, $password, $databasename);

if ($conn->connect_errno) {
    $first_errno = $conn->connect_errno;
    $first_err = $conn->connect_error;

    // If the requested host was 'localhost', it's possible the client
    // attempted a socket connection. Retry using TCP to 127.0.0.1.
    if ($server === 'localhost') {
        $tcp = new mysqli('127.0.0.1', $username, $password, $databasename);

        if ($tcp->connect_errno) {
            $tcp_errno = $tcp->connect_errno;
            $tcp_err = $tcp->connect_error;
            die("Connection failed. localhost: (" . $first_errno . ") " . $first_err . " ; 127.0.0.1: (" . $tcp_errno . ") " . $tcp_err);
        }

        // TCP succeeded, use that connection
        $conn = $tcp;
    } else {
        die("Connection failed: (" . $first_errno . ") " . $first_err);
    }
}

// Final connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($base_url)) {
    if (!function_exists('vms_detect_base_url')) {
        /**
         * Build the absolute base URL for the application by combining the host
         * and the project subdirectory (if the project does not live at the web root).
         */
        function vms_detect_base_url(): string
        {
            $default = '';
            if (empty($_SERVER['HTTP_HOST'])) {
                return $default;
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
            $project_root = dirname(__DIR__);

            $document_root = $document_root ? rtrim(str_replace('\\', '/', $document_root), '/') : '';
            $project_root = str_replace('\\', '/', $project_root);

            $sub_dir = '';
            if ($document_root && strpos($project_root, $document_root) === 0) {
                $sub_dir = trim(substr($project_root, strlen($document_root)), '/');
            }

            $base_path = $sub_dir ? '/' . $sub_dir : '';
            return sprintf('%s://%s%s', $scheme, $host, $base_path);
        }
    }

    $base_url = $config['app']['base_url'] ?? vms_detect_base_url();
}
?>
