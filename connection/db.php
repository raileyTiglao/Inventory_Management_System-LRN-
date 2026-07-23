<?php

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $serverName = "10.2.0.9";
        $database = "LRNPH_OJT";  

        try {
            $conn = new PDO(
                "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true"
            );

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $pdo = $conn;
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    return $pdo;
}