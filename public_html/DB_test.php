<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

global $wpdb;
try {
    $wpdb->get_results("SELECT 1");
    echo "Connected to the database.";
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage();
}
