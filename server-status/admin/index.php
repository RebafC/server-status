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

if (!file_exists('../config.php')) {
    header('Location: ../');
} else {
    require_once __DIR__.'/../config.php';

    require_once __DIR__.'/../classes/constellation.php';

    require_once __DIR__.'/../classes/mailer.php';

    require_once __DIR__.'/../classes/notification.php';

    require_once __DIR__.'/../template.php';

    require_once __DIR__.'/../libs/parsedown/Parsedown.php';

    require_once __DIR__.'/../classes/queue.php';

    require_once __DIR__.'/../classes/db-class.php';
    $db = new SSDB();
    define('NAME', $db->getSetting($mysqli, 'name'));
    define('TITLE', $db->getSetting($mysqli, 'title'));
    define('WEB_URL', $db->getSetting($mysqli, 'url'));
    define('MAILER_NAME', $db->getSetting($mysqli, 'mailer'));
    define('MAILER_ADDRESS', $db->getSetting($mysqli, 'mailer_email'));

    define('GOOGLE_RECAPTCHA', $db->getBooleanSetting($mysqli, 'google_recaptcha'));
    define('GOOGLE_RECAPTCHA_SECRET', $db->getSetting($mysqli, 'google_recaptcha_secret'));
    define('GOOGLE_RECAPTCHA_SITEKEY', $db->getSetting($mysqli, 'google_recaptcha_sitekey'));
    define('SUBSCRIBE_EMAIL', $db->getBooleanSetting($mysqli, 'subscribe_email'));
    define('SUBSCRIBE_TELEGRAM', $db->getBooleanSetting($mysqli, 'subscribe_telegram'));
    define('TG_BOT_USERNAME', $db->getSetting($mysqli, 'tg_bot_username'));
    define('TG_BOT_API_TOKEN', $db->getSetting($mysqli, 'tg_bot_api_token'));
    define('PHP_MAILER', $db->getBooleanSetting($mysqli, 'php_mailer'));
    define('PHP_MAILER_SMTP', $db->getBooleanSetting($mysqli, 'php_mailer_smtp'));
    define('PHP_MAILER_PATH', $db->getSetting($mysqli, 'php_mailer_path'));
    define('PHP_MAILER_HOST', $db->getSetting($mysqli, 'php_mailer_host'));
    define('PHP_MAILER_PORT', $db->getSetting($mysqli, 'php_mailer_port'));
    define('PHP_MAILER_SECURE', $db->getBooleanSetting($mysqli, 'php_mailer_secure'));
    define('PHP_MAILER_USER', $db->getSetting($mysqli, 'php_mailer_user'));
    define('PHP_MAILER_PASS', $db->getSetting($mysqli, 'php_mailer_pass'));
    define('CRON_SERVER_IP', $db->getSetting($mysqli, 'cron_server_ip'));

    // Process the subscriber notification queue
    // If CRON_SERVER_IP is not set, call notification once incident has been saved
    if (empty(CRON_SERVER_IP)) {
        if (isset($_GET['sent']) && true === $_GET['sent']) {
            (new Queue())->process_queue();
        }
    } elseif (isset($_GET['task']) && 'cron' === $_GET['task']) {
        // Else, base it on call to /admin?task=cron being called from IP defined by CRON_SERVER_IP
        if (!empty(CRON_SERVER_IP) && CRON_SERVER_IP === $_SERVER['REMOTE_ADDR']) {
            (new Queue())->process_queue();
            syslog(1, 'CRON server processed');
        } else {
            syslog(1, 'CRON called from unauthorised server');
        }
    }

    if (isset($_COOKIE['user']) && !isset($_SESSION['user'])) {
        User::restore_session();
    }

    if (!isset($_SESSION['user'])) {
        if (isset($_GET['do']) && 'lost-password' === $_GET['do']) {
            require_once __DIR__.'/lost-password.php';
        } elseif (isset($_GET['do']) && 'change-email' === $_GET['do']) {
            $user_pwd = new User($_GET['id']);
            $user_pwd->change_email();

            require_once __DIR__.'/login-form.php';
        } else {
            User::login();

            require_once __DIR__.'/login-form.php';
        }
    } else {
        $user = new User($_SESSION['user']);
        if (!$user->is_active()) {
            User::logout();
        }

        $do = $_GET['do'] ?? '';

        switch ($do) {
            case 'change-email':
                $user = new User($_GET['id']);
                $user->change_email();

                // no break
            case 'user':
                require_once __DIR__.'/user.php';

                break;

            case 'settings':
                require_once __DIR__.'/settings.php';

                break;

            case 'new-user':
                require_once __DIR__.'/new-user.php';

                break;

            case 'new-service':
            case 'edit-service':
                require_once __DIR__.'/service.php';

                break;

            case 'new-service-group':
            case 'edit-service-group':
                require_once __DIR__.'/service-group.php';

                break;

            case 'options':
                require_once __DIR__.'/options.php';

                break;

            case 'logout':
                User::logout();

                break;

            default:
                require_once __DIR__.'/dashboard.php';

                break;
        }

        Template::render_footer(true);
    }
}
