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

/**
 * Class for creating and deleting tokens.
 */
class token
{
    /**
     * Generates a new token from user id and randomly generated salt.
     *
     * @param int       $id     user ID
     * @param string    $data   associated with token that are important
     * @param timestamp $expire expiration time
     *
     * @return string token
     */
    public static function add($id, $data, $expire): string
    {
        global $mysqli;
        $salt = uniqid(random_int(0, mt_getrandmax()), true);
        $token = hash('sha256', $id.$salt);
        $stmt = $mysqli->prepare('INSERT INTO tokens VALUES(?, ?, ?, ?)');
        $stmt->bind_param('siis', $token, $id, $expire, $data);
        $stmt->execute();
        $stmt->get_result();

        return $token;
    }

    /**
     * Checks whether token exists in the database and has not expired.
     *
     * @param string $token
     * @param int    $id    user ID
     * @param string $data
     *
     * @return int count of results in database
     */
    public static function validate($token, $id, $data)
    {
        global $mysqli;
        $time = time();
        $stmt = $mysqli->prepare('SELECT count(*) as count FROM tokens WHERE token = ? AND user = ? AND expire>=? AND data LIKE ?');
        $stmt->bind_param('siis', $token, $id, $time, $data);
        $stmt->execute();

        $query = $stmt->get_result();

        return $query->fetch_assoc()['count'];
    }

    /**
     * Returns token data.
     *
     * @param string $token
     * @param int    $id    user ID
     *
     * @return string data
     */
    public static function get_data($token, $id)
    {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT data as count FROM tokens WHERE token = ? AND user = ?');
        $stmt->bind_param('si', $token, $id);
        $stmt->execute();

        $query = $stmt->get_result();

        return $query->fetch_assoc()['data'];
    }

    /**
     * Deletes token.
     *
     * @param string $token
     */
    public static function delete($token): void
    {
        global $mysqli;
        $time = time();
        $stmt = $mysqli->prepare('DELETE FROM tokens WHERE token = ? OR expire<?');
        $stmt->bind_param('sd', $token, $time);
        $stmt->execute();
        $stmt->get_result();
    }
}
