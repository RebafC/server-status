<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/classes/telegram.php';

require_once __DIR__ . '/classes/subscriber.php';

require_once __DIR__ . '/classes/db-class.php';

$db = new SSDB();
define('NAME', $db->getSetting($mysqli, 'name'));
define('TITLE', $db->getSetting($mysqli, 'title'));
define('WEB_URL', $db->getSetting($mysqli, 'url'));
define('MAILER_NAME', $db->getSetting($mysqli, 'mailer'));
define('MAILER_ADDRESS', $db->getSetting($mysqli, 'mailer_email'));
define('SUBSCRIBE_TELEGRAM', $db->getBooleanSetting($mysqli, 'subscribe_telegram'));
define('SUBSCRIBE_TELEGRAM', $db->getBooleanSetting($mysqli, 'subscribe_telegram'));
define('TG_BOT_API_TOKEN', $db->getSetting($mysqli, 'tg_bot_api_token'));
define('TG_BOT_USERNAME', $db->getSetting($mysqli, 'tg_bot_username'));

$telegram = new Telegram();
$subscriber = new Subscriber();

try {
    $auth_data = $telegram->checkTelegramAuthorization($_GET);
    $telegram->saveTelegramUserData($auth_data);
} catch (Exception $exception) {
    exit($exception->getMessage());
}

// Check if user is registered in DB
$subscriber->firstname = $auth_data['first_name'];
$subscriber->lastname = $auth_data['last_name'];
$subscriber->typeID = 1;
$subscriber->userID = $auth_data['id'];
$subscriber->active = 1; // Telegram user should always be active if they can be validated

$subscriber_id = $subscriber->get_subscriber_by_userid(true); // If user does not exists, create it
$subscriber->id = $subscriber_id;

// make sure we don't have a logged in email subscriber
$subscriber->set_logged_in();

header('Location: subscriptions.php');
