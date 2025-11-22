<?php
$server = "localhost:3306";
$username = "root";
$password = "";
$databasename = "vms_db";

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
?>
