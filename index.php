<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.11
* FILE: index.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 02-13-2013
* DETAILS: Program O's starting point
***************************************/

if (!file_exists('config/global_config.php')) {
    // No config exists; prompt to install
    exit(
        'Program O exists, but is not installed. ' .
        '<a href="install/install_programo.php">Install Program O</a>'
    );
}

require_once 'config/global_config.php';
require_once _LIB_PATH_ . 'misc_functions.php';

$get_vars = filter_input_array(INPUT_GET) ?? [];
$qs = !empty($get_vars) ? '?' . http_build_query($get_vars) : '';

$format = _strtolower($get_vars['format'] ?? 'plain');

$gui = match ($format) {
    'json' => 'jquery',
    'xml'  => 'xml',
    default => 'plain',
};

if (!defined('SCRIPT_INSTALLED')) {
    header('Location: ' . _INSTALL_URL_ . 'install_programo.php');
    exit;
}

header("Location: gui/{$gui}/{$qs}");
exit;
