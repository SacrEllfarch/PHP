<?php

$path = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'];

if (is_file($path)) {
    return false;
}

require __DIR__ . '/index.php';
