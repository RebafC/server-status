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
 * Subscriber class.
 */
class subscriber
{
    public $id;

    public $firstname;

    public $lastname;

    public $userID = '';

    // Holds email, telegram id etc
    public $token;

    public $active = 0;

    public $typeID;

    /**
     * Gets authentcation token for specified subscriberID.
     *
     * @param int $subscriberID - specifies which subscriber we are looking up
     * @param int $typeID       - specifies which type of subscription we are refering (1 = telegram, 2 = email)
     *
     * @return string $token - 32 bytes HEX string
     */
    public function get_token($subscriberID, $typeID)
    {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT token FROM subscribers WHERE subscriberID = ? and typeID=? and active = 1 LIMIT 1');
        $stmt->bind_param('ii', $subscriberID, $typeID);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->token = $row['token'];

            // $this->get_subscriber_by_token($this->token);
            return $row['token'];
        }

        return false;
    }

    public function get_subscriber_by_token($token): bool
    {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT subscriberID FROM subscribers WHERE token=? and typeID=?');
        $stmt->bind_param('si', $token, $this->typeID);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['subscriberID'];
            $this->populate();

            return true;
        }

        return false;
    }

    public function get_subscriber_by_userid($create = false)
    {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT subscriberID FROM subscribers WHERE userID LIKE ? AND typeID = ? LIMIT 1');
        $stmt->bind_param('si', $this->userID, $this->typeID);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['subscriberID'];
            $this->populate();

            return $row['subscriberID'];
        }

        // User is not registered in DB, so add if $create = true
        if ($create) {
            return $this->add($this->typeID, $this->userID, $this->active, $this->firstname, $this->lastname);
        }

        return false;
    }

    public function populate(): bool
    {
        global $mysqli;
        $stmt = $mysqli->prepare('SELECT typeID, userID, firstname, lastname, token, active FROM subscribers WHERE subscriberID = ?');
        $stmt->bind_param('i', $this->id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->userID = $row['userID'];
            $this->typeID = $row['typeID'];
            $this->firstname = $row['firstname'];
            $this->lastname = $row['lastname'];
            $this->token = $row['token'];
            $this->active = $row['active'];

            return true;
        }

        return false;
    }

    public function add($typeID, $userID, $active = null, $firstname = null, $lastname = null)
    {
        global $mysqli;
        $expireTime = strtotime('+2 hours');
        $updateTime = strtotime('now');
        $token = $this->generate_token();

        $stmt = $mysqli->prepare('INSERT INTO subscribers (typeID, userID, firstname, lastname, token, active, expires, create_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssiii', $typeID, $userID, $firstname, $lastname, $token, $active, $expireTime, $updateTime);
        $stmt->execute();
        // $query = $stmt->get_result();

        $this->id = $mysqli->insert_id;
        $this->typeID = $typeID;
        $this->userID = $userID;
        $this->token = $token;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->active = $active;

        return $this->id;
    }

    public function update($subscriberID): bool
    {
        global $mysqli;
        $updateTime = strtotime('now');
        $stmt = $mysqli->prepare('UPDATE subscribers SET update_time = ? WHERE subscriberID=?');
        $stmt->bind_param('ii', $updateTime, $subscriberID);
        $stmt->execute();

        return true;
    }

    public function activate($subscriberID): bool
    {
        global $mysqli;
        $updateTime = strtotime('now');

        $stmt = $mysqli->prepare('UPDATE subscribers SET update_time = ?, expires = ? WHERE subscriberID = ?');
        $tmp = null;
        $stmt->bind_param('iii', $updateTime, $tmp, $subscriberID);
        $stmt->execute();

        return true;
    }

    public function delete($subscriberID): bool
    {
        global $mysqli;

        $stmt = $mysqli->prepare('DELETE FROM services_subscriber WHERE subscriberIDFK = ?');
        $stmt->bind_param('i', $subscriberID);
        $stmt->execute();
        // $query = $stmt->get_result();

        $stmt = $mysqli->prepare('DELETE FROM subscribers WHERE subscriberID = ?');
        $stmt->bind_param('i', $subscriberID);
        $stmt->execute();

        // $query = $stmt->get_result();
        return true;
    }

    public function check_userid_exist(): bool
    {
        global $mysqli;

        $stmt = $mysqli->prepare('SELECT subscriberID, userID, token, active FROM subscribers WHERE typeID=? AND userID=? LIMIT 1');

        $stmt->bind_param('is', $this->typeID, $this->userID);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['subscriberID'];
            $this->populate();

            return true;
        }

        return false;
    }

    public function is_active_subscriber($token): bool
    {
        global $mysqli;

        $stmt = $mysqli->prepare('SELECT subscriberID, token, userID, active, expires FROM subscribers WHERE token LIKE ? LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        } else {
            // No data found, fail gently...
            return false;
        }

        // If account is not already active, check if we are within timeframe of exipre +2h
        // and active if so, otherwise,delete account and return falsev
        if (1 !== $row['active']) {
            // Calculate time range for when subscription need to be validated
            $time_end = $row['expires'];
            $time_start = $time_end - (3600 * 2); // TODO - make this interval configurable via a config option
            $time_now = time();

            if (($time_now > $time_start) && ($time_now < $time_end)) {
                // Timefram is within range, active user..
                $stmt2 = $mysqli->prepare('UPDATE subscribers SET active=1, expires=null WHERE subscriberID = ?');
                $stmt2->bind_param('i', $row['subscriberID']);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $this->active = 1;
                $this->id = $row['subscriberID'];
                $this->userID = $row['userID'];
                $this->token = $row['token'];

                return true;
            }

            // Timeframe outside of given scope -> delete account
            $stmt2 = $mysqli->prepare('DELETE FROM subscribers WHERE subscriberID = ?');
            $stmt2->bind_param('i', $row['subscriberID']);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $this->active = 0;

            return false;
        }

        // if we get here, account should already be active
        $this->active = 1;
        $this->id = $row['subscriberID'];
        $this->userID = $row['userID'];
        $this->token = $row['token'];

        return true;
    }

    /**
     * Generate a new 64 byte token (32 bytes converted from bin2hex = 64 bytes).
     *
     * @return string token
     */
    public function generate_token()
    {
        global $mysqli;

        if (function_exists('openssl_random_pseudo_bytes')) {
            $token = openssl_random_pseudo_bytes(32);   // Generate a random string.
            $token = bin2hex($token);         // Convert the binary data into hexadecimal representation.
        } else {
            // Use alternative token generator if openssl isn't available...
            $token = make_alt_token(32, 32);
        }

        // Make sure token doesn't already exist in db
        $stmt = $mysqli->prepare('SELECT subscriberID FROM subscribers WHERE token LIKE ?');
        echo $mysqli->error;
        $stmt->bind_param('s', $token);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // token already exists, call self again
            return $this->generate_token();
        }

        return $token;
    }

    /**
     * Alternative token generator if openssl_random_pseudo_bytes is not available
     * Original code by jsheets at shadonet dot com from http://php.net/manual/en/function.mt-rand.php.
     *
     * @params int min_length Minimum length of token
     * @params int max_length Maximum length of token
     *
     * @param mixed $min_length
     * @param mixed $max_length
     *
     * @return string token
     */
    public function make_alt_token($min_length = 32, $max_length = 64)
    {
        $key = '';

        // build range and shuffle range using ASCII table
        for ($i = 0; $i <= 255; ++$i) {
            $range[] = chr($i);
        }

        // shuffle our range 3 times
        for ($i = 0; $i <= 3; ++$i) {
            shuffle($range);
        }

        // loop for random number generation
        for ($i = 0; $i < random_int($min_length, $max_length); ++$i) {
            $key .= $range[random_int(0, count($range) - 1)];
        }

        $return = bin2hex($key);

        if ('' !== $return) {
            return $return;
        }

        return 0;
    }

    public function set_logged_in(): void
    {
        $_SESSION['subscriber_valid'] = true;
        $_SESSION['subscriber_id'] = $this->id;
        $_SESSION['subscriber_userid'] = $this->userID;
        $_SESSION['subscriber_typeid'] = $this->typeID; // email
        $_SESSION['subscriber_token'] = $this->token;
    }

    public function set_logged_off(): void
    {
        unset($_SESSION['subscriber_valid'], $_SESSION['subscriber_userid'], $_SESSION['subscriber_typeid'], $_SESSION['subscriber_id'], $_SESSION['subscriber_token']);
    }
}
