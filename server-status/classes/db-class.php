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

class SSDB
{
    public function execute($conn, $sql)
    {
        if (true === $conn->query($sql)) {
            return true;
        }

        return $conn->error;
    }

    public function getSetting($conn, string $setting)
    {
        $sql = "SELECT value FROM settings WHERE setting='".$setting."'";
        $result = $conn->query($sql);

        if (1 === $result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                return $row['value'];
            }
        } else {
            return 'null';
        }
    }

    public function setSetting($conn, string $settingname, string $settingvalue)
    {
        $sql = "INSERT INTO settings (setting,value) VALUES ('".$settingname."','".$settingvalue."');";
        if (true === $conn->query($sql)) {
            return true;
        }

        return $conn->error;
    }

    public function deleteSetting($conn, string $settingname)
    {
        $sql = 'DELETE FROM settings WHERE setting="'.$settingname.'";';
        if (true === $conn->query($sql)) {
            return true;
        }

        return $conn->error;
    }

    public function updateSetting($conn, string $settingname, string $settingvalue): bool
    {
        $this->deleteSetting($conn, $settingname);
        $this->setSetting($conn, $settingname, $settingvalue);

        return true;
    }

    public function getBooleanSetting($conn, string $setting): bool
    {
        return 'yes' === trim($this->getSetting($conn, $setting));
    }
}
