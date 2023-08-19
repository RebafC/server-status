<?php

declare(strict_types=1);

if (!file_exists('../config.php')) {
    header('Location: ../');
} else {
    require_once __DIR__ . '/../config.php';

    require_once __DIR__ . '/../classes/constellation.php';

    $limit = ($_GET['limit'] ?? 5);
    $offset = ($_GET['offset'] ?? 0);
    $timestamp = $_GET['timestamp'] ?? time();

    $result = $constellation->get_incidents($_GET['future'] ?? false, $offset, $limit, $timestamp);
    header('Cache-Control: no-cache');
    header('Content-type: application/json');
    echo json_encode($result, JSON_THROW_ON_ERROR);
}
