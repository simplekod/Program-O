<?php

/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.11
* FILE: editSRAI.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the AIML table of the DB for desired categories
***************************************/

$thisFile = __FILE__;
/** @noinspection PhpIncludeInspection */
require_once('../config/global_config.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'error_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'misc_functions.php');

$e_all = defined('E_DEPRECATED') ? E_ALL & ~E_DEPRECATED : E_ALL;

error_reporting($e_all);
ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'editSRAI.error.log');
ini_set('html_errors', false);
ini_set('display_errors', false);

$session_name = 'PGO_Admin';
session_name($session_name);
session_start();

header('Content-Type: application/json; charset=UTF-8');

$form_vars = clean_inputs() ?? [];
$bot_id = $_SESSION['poadmin']['bot_id'] ?? 1;

if (
    empty($_SESSION)
    || empty($_SESSION['poadmin']['uid'])
) {
    error_log('Session vars: ' . print_r($_SESSION, true), 3, _LOG_PATH_ . 'session.txt');
    echo json_encode(['error' => "No session found"]);
    exit;
}

// Open the DB
$action = $form_vars['action'] ?? 'runSearch';

switch ($action) {
    case 'add':
        echo insertSRAI();
        exit;
    case 'update':
        echo updateSRAI();
        exit;
    case 'del':
        echo delSRAI($form_vars['id'] ?? null);
        exit;
    default:
        echo runSearch();
        exit;
}

/**
 * Function delSRAI
 *
 * @param string|null $id
 * @return string JSON
 */
function delSRAI(?string $id)
{
    if (!empty($id)) {
        $sql = "DELETE FROM `srai_lookup` WHERE `id` = :id LIMIT 1";
        $params = [':id' => $id];
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

        $msg = ($affectedRows == 0)
            ? 'Error SRAI couldn\'t be deleted - no changes made.'
            : 'Lookup entry has been deleted.';
    } else {
        $msg = 'Error: Lookup entry couldn\'t be deleted - no changes made.';
    }
    return json_encode(['result' => $msg]);
}

/**
 * Function runSearch
 *
 * @return string JSON
 */
function runSearch()
{
    global $bot_id, $form_vars;

    $columns = $form_vars['columns'] ?? [];
    $order = $form_vars['order'] ?? [];
    $start = isset($form_vars['start']) ? intval($form_vars['start']) : 0;
    $length = isset($form_vars['length']) ? intval($form_vars['length']) : 10;
    $draw = isset($form_vars['draw']) ? intval($form_vars['draw']) : 0;

    $search_fields = ['id', 'bot_id', 'pattern', 'template_id'];
    $searchParams = [':bot_id' => $bot_id];
    $where = [];

    // parse column searches
    foreach ($columns as $index => $column) {
        if (!is_array($column)) continue;
        if (($column['data'] ?? '') === 'Delete') $column['data'] = 'id';
        if (!empty($column['search']['value'] ?? null)) {
            $tmpSearch = $column['search']['value'];
            $tmpSearch = str_replace(['_', '%'], ['\\_', '\\$'], $tmpSearch);
            $tmpName = $column['data'];
            $addWhere = "`$tmpName` like :$tmpName";
            $searchParams[":$tmpName"] = "%$tmpSearch%";
            $where[] = $addWhere;
        }
    }
    $searchTerms = (!empty($where)) ? implode(' AND ', $where) : 'TRUE';

    // get search order
    $oBy = [];
    foreach ($order as $row) {
        $colIdx = $row['column'] ?? null;
        $dir = $row['dir'] ?? 'asc';
        $name = $columns[$colIdx]['data'] ?? 'id';
        if ($name === 'Delete') $name = 'id';
        $oBy[] = "$name $dir";
    }
    $orderBy = !empty($oBy) ? implode(', ', $oBy) : 'id';

    // Defensive: always use placeholders in SQL
    $countSQL = "SELECT count(id) as total FROM `srai_lookup` WHERE `bot_id` = :bot_id AND ($searchTerms);";
    $count = db_fetch($countSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__) ?? [];
    $total = $count['total'] ?? 0;

    $sql = "SELECT id, bot_id, pattern, template_id FROM `srai_lookup` WHERE `bot_id` = :bot_id AND ($searchTerms) ORDER BY $orderBy LIMIT $start, $length;";
    $result = db_fetchAll($sql, $searchParams, __FILE__, __FUNCTION__, __LINE__) ?? [];

    $out = [
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => []
    ];

    foreach ($result as $row) {
        $row['template_id'] = htmlentities($row['template_id'] ?? '', ENT_NOQUOTES, 'UTF-8');
        $row['DT_RowId'] = $row['id'] ?? '';
        $out['data'][] = $row;
    }

    return json_encode($out);
}

/**
 * Function updateSRAI
 *
 * @return string JSON
 */
function updateSRAI()
{
    global $form_vars;

    $id = trim($form_vars['id'] ?? '');
    $bot_id = trim($form_vars['bot_id'] ?? '');
    $pattern = function_exists('_strtoupper') ? _strtoupper(trim($form_vars['pattern'] ?? '')) : strtoupper(trim($form_vars['pattern'] ?? ''));
    $template_id = trim($form_vars['template_id'] ?? '');

    $fields = [];
    if (empty($bot_id))      $fields[] = 'bot_id';
    if (empty($pattern))     $fields[] = 'pattern';
    if (empty($template_id)) $fields[] = 'template_id';

    if (!empty($fields)) {
        $msg = 'Please make sure that no fields are empty.(' . implode(', ', $fields) . ')';
    } else {
        $params = [
            ':id' => $id,
            ':bot_id' => $bot_id,
            ':pattern' => $pattern,
            ':template_id' => $template_id
        ];
        $sql = "UPDATE `srai_lookup` SET `bot_id` = :bot_id, `pattern` = :pattern, `template_id` = :template_id WHERE `id` = :id;";
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
        $msg = ($affectedRows > 0)
            ? 'SRAI Updated.'
            : 'There was an error updating the SRAI - no changes made.';
    }
    return json_encode(['result' => $msg]);
}

/**
 * Function insertSRAI
 *
 * @return string JSON
 */
function insertSRAI()
{
    global $form_vars;
    $bot_id = trim($form_vars['bot_id'] ?? '');
    $pattern = trim($form_vars['pattern'] ?? '');
    $pattern = function_exists('_strtoupper') ? _strtoupper($pattern) : strtoupper($pattern);
    $template_id = trim($form_vars['template_id'] ?? '');

    $fields = [];
    if (empty($bot_id)) $fields[] = 'bot_id';
    if (empty($pattern)) $fields[] = 'pattern';
    if (empty($template_id)) $fields[] = 'template_id';

    if (!empty($fields)) {
        $msg = 'No fields can be empty. (' . implode(', ', $fields) . ')';
    } else {
        $sql = 'INSERT INTO `srai_lookup` (`id`, `bot_id`, `pattern`, `template_id`) VALUES (NULL, :bot_id, :pattern, :template_id);';
        $params = [
            ':bot_id' => $bot_id,
            ':pattern' => $pattern,
            ':template_id' => $template_id
        ];
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
        $msg = ($affectedRows > 0)
            ? "SRAI added."
            : "SRAI not updated - no changes made.";
    }
    return json_encode(['result' => $msg]);
}
