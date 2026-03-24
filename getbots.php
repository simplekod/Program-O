<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.11
* FILE: getbots.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: MAY 17TH 2014
* DETAILS: Searches the database for all active chatbots, returning a JSON encoded array of ID/name pairs
***************************************/

declare(strict_types=1);

$time_start = microtime(true);
$script_start = $time_start;
$last_timestamp = $time_start;

// Load config and shared files
require_once "config/global_config.php";
require_once _LIB_PATH_ . 'PDO_functions.php';
require_once _LIB_PATH_ . 'error_functions.php';
require_once _LIB_PATH_ . 'misc_functions.php';

ini_set('error_log', _LOG_PATH_ . 'getbots.error.log');

$sql = "SELECT `bot_id`, `bot_name` FROM `$dbn`.`bots`;";
$result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);

// Defensive: $result may be false on error
$bots = ['bots' => []];
if (is_array($result)) {
    foreach ($result as $row) {
        $bot_id = $row['bot_id'];
        $bot_name = $row['bot_name'];
        $bots['bots'][$bot_id] = $bot_name;
    }
}

header('Content-Type: application/json');

try {
    $out = json_encode($bots, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'JSON encoding error', 'details' => $e->getMessage()]));
}

exit($out);
