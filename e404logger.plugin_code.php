//<?php
/**
 * Error 404 Logger
 *
 * Plugin logs requests that trigger an Page not found error.
 *
 * @category    plugin
 * @version     0.2
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Andraz Kozelj (andraz dot kozelj at amis dot net) date created: 14.03.2007
 * @author      yama (http://kyms.jp)
 * @internal    @events        OnPageNotFound, OnWebPageInit
 * @internal    @modx_category Manager and Admin
 * @internal    @properties &found_ref_only=Found ref only;list;yes,no;yes &count_robots=Robots count;list;yes,no;no; &robots=Robots list;text;googlebot,baidu,bing,crawl;&limit=Number of limit logs;1000 &trim=Number deleted at a time;100 &remoteIPIndexName=RemoteIP Index Name;text;REMOTE_ADDR
 * @global $modx
 * @global $remoteIPIndexName
 */

$e404logger_dir = 'assets/modules/error404logger/';
include_once($e404logger_dir.'helpers.php');

if (empty($found_ref_only)) {
    $found_ref_only = 'no';
}
if (empty($count_robots)) {
    $count_robots = 'yes';
}
if (empty($robots)) {
    $robots = 'googlebot,baidu,msnbot';
}
if(empty($limit)) {
    $limit = 1000;
}
if(empty($trim)) {
    $trim = 100;
}

if ($modx->event->name === 'OnWebPageInit' && isset($_SESSION['mgrValidated'])) {
    if(!input_get('e404_redirect')) {
        return;
    }

    $url = str_replace(
            array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D')
            , array('!', '*', "'", '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']')
            , urlencode(input_get('e404_redirect')));
    header('Refresh: 0.5; URL=' . $url);
    exit;
}

if ($modx->event->name !== 'OnPageNotFound' || isset($_SESSION['mgrValidated'])) {
    return;
}

if($found_ref_only === 'yes' && !server_var('HTTP_REFERER')) {
    return;
}

if($count_robots === 'no') {
    $robots = explode(',',$robots);
    foreach($robots as $robot) {
        if(strpos(gethostbyaddr(server_var('REMOTE_ADDR')), $robot) !== false) {
            return;
        }
    }
}

include_once(MODX_BASE_PATH . $e404logger_dir . 'e404logger.class.inc.php');
$e404 = new Error404Logger();
$e404->insert($remoteIPIndexName);
$e404->purge_log($limit,$trim);
