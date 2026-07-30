<?php

date_default_timezone_set('Asia/Manila');

    class Connection {
        private static $instance = null;
        private $pdo;

        private function __construct() 
        {
            $dsn = "sqlsrv:Server=" . '10.2.0.9'
                . ";Database=" . 'LRNPH_OJT'
                . ";TrustServerCertificate=1";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
            ];

            $this->pdo = new PDO($dsn, 'rtiglao', 'Admin@007', $options);
        }

        public static function get_connecton(): PDO 
        {
            if (self::$instance === null){
                self::$instance = new self();
            }
            return self::$instance->pdo;
        }


    }

?>
