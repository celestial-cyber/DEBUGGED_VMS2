<?php
include 'connection.php'; // your DB connection file

$sql = "CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    event VARCHAR(100) NOT NULL,
    event_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if($conn->query($sql) === TRUE){
    echo "Table 'event_registrations' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
