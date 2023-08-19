<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (!file_exists('../config.php')) {
    header('Location: ../');
} else {
    require_once '../config.php';

    require_once '../classes/constellation.php';

    $limit = ($_GET['limit'] ?? 5);
    $offset = ($_GET['offset'] ?? 0);
    $timestamp = (isset($_GET['timestamp'])) ? $_GET['timestamp'] : time();

    $result = $constellation->get_incidents($_GET['future'] ?? false, $offset, $limit, $timestamp);
    header('Cache-Control: no-cache');
    header('Content-type: application/json');
    echo json_encode($result);
}
