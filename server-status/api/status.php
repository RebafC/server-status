<?php

declare(strict_types=1);

if (!file_exists('../config.php')) {
    header('Location: ../');
} else {
    require_once __DIR__.'/../config.php';

    require_once __DIR__.'/../classes/constellation.php';
    header('Cache-Control: no-cache');
    header('Content-type: application/json');

    if (!isset($_GET['id'])) {
        $array = $constellation->render_status(true, false);
        echo json_encode($array, JSON_THROW_ON_ERROR);
    } else {
        // get id of service, check if service exists
        $queryId = $mysqli->prepare('SELECT id from services where id = ?;');
        $queryId->bind_param('i', $_GET['id']);
        $queryId->execute();
        $result = $queryId->get_result()->fetch_assoc();
        if ((is_countable($result) ? count($result) : 0) === 0) {
            exit(json_encode(['error' => _('Service does not exist!')], JSON_THROW_ON_ERROR));
        }

        // get name, description and status.type (status of service) by id
        $query = $mysqli->prepare('select services.id, name, description, status.type from services inner join status on status.id = services.id where services.id = ?;');
        $query->bind_param('i', $_GET['id']);
        $query->execute();
        $result = $query->get_result()->fetch_assoc();
        // if type is a number then return it, else just return the service name/desc
        if (is_numeric($result['type'])) {
            $service = new Service($_GET['id'], $result['name'], $result['description'], '', $result['type']);
        } else {
            $service = new Service($_GET['id'], $result['name'], $result['description']);
        }

        echo json_encode($service, JSON_THROW_ON_ERROR);
    }
}
