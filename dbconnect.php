<?php
$host     = "localhost";
$user     = "root";
$password = "";           // Default XAMPP — change if needed
$database = "food_ordering";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("
    <div style='font-family:sans-serif;text-align:center;padding:80px;'>
        <h2 style='color:#e74c3c;'>&#10060; Database Connection Failed</h2>
        <p style='color:#555;margin-top:10px;'>" . mysqli_connect_error() . "</p>
        <p style='color:#999;font-size:.9rem;margin-top:6px;'>
            Make sure XAMPP MySQL is running and the database <b>food_ordering</b> exists.
        </p>
    </div>");
}

mysqli_set_charset($conn, "utf8");
?>
