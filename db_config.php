<?php
// db_config.php - local lab database connection for testing the provided files
$conn = new mysqli("localhost", "root", "", "medic_vault_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>