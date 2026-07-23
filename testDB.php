<?php
   require_once __DIR__ . '/connection/db.php';
   try {
       $db = get_db();
       echo "✓ Connected to LRN_OJT successfully!";
   } catch (Exception $e) {
       echo "✗ Connection failed: " . $e->getMessage();
   }
   ?> 