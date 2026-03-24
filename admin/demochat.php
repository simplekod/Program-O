<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: demochat.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-12-2014
 * DETAILS: Displays a demo chat page for the currently selected chatbot
 ***************************************/

/** @noinspection PhpUndefinedVariableInspection */
$bot_id = (isset($bot_id) && $bot_id === 'new') ? 0 : ($bot_id ?? 0);

$upperScripts   = '';
$topNav         = $template->getSection('TopNav');
$leftNav        = $template->getSection('LeftNav');
$main           = $template->getSection('Main');
$navHeader      = $template->getSection('NavHeader');

$FooterInfo     = getFooter();
$errMsgClass    = (!empty($msg)) ? 'ShowError' : 'HideError';
$errMsgStyle    = $template->getSection($errMsgClass);
$noLeftNav      = '';
$noTopNav       = '';
$noRightNav     = $template->getSection('NoRightNav');

$headerTitle    = 'Actions:';
$pageTitle      = 'My-Program O - Chat Demo';
$mainContent    = 'This will eventually be the page for the chat demo.';
// showChatFrame() may depend on $bot_id; ensure $bot_id is set above
$mainContent    = showChatFrame();
$mainTitle      = 'Chat Demo';

/**
 * Function showChatFrame
 *
 * @return string
 */
function showChatFrame(): string
{
    global $template, $bot_name, $bot_id;

    $qs = '?bot_id=' . (isset($bot_id) ? $bot_id : 0);

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `format` FROM `bots` WHERE `bot_id` = :bot_id LIMIT 1;";
    $params = array(':bot_id' => $bot_id);

    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    // If db_fetch returns false/null or missing 'format', default to plain
    $format = '';
    if (is_array($row) && isset($row['format'])) {
        $format = strtolower($row['format']);
    } else {
        $format = 'html';
    }

    switch ($format) {
        case 'html':
            $url = '../gui/plain/';
            break;
        case 'json':
            $url = '../gui/jquery/';
            break;
        case 'xml':
            $url = '../gui/xml/';
            break;
        default:
            $url = '../gui/plain/';
    }

    $url .= $qs;
    $out = $template->getSection('ChatDemo');
    $out = str_replace('[pageSource]', $url, $out);
    $out = str_replace('[format]', $format, $out);

    return $out;
}
