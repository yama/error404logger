<?php
/**
 * Error 404 Logger
 *
 * Plugin logs requests that trigger an Page not found error.
 *
 * @category    plugin
 * @version     0.2
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Andraz Kozelj (andraz dot kozelj at amis dot net) date created: 14.03.2007
 * @author      yama (https://kyms.jp)
 * @internal    @events        OnPageNotFound, OnWebPageInit
 * @internal    @modx_category Manager and Admin
 * @internal    @properties &found_ref_only=Found ref only;list;yes,no;yes &count_robots=Robots count;list;yes,no;no; &robots=Robots list;text;googlebot,baidu,bing,crawl;&limit=Number of limit logs;1000 &trim=Number deleted at a time;100 &remoteIPIndexName=RemoteIP Index Name;text;REMOTE_ADDR
 * @global $modx
 * @global $remoteIPIndexName
 */

$e404logger_dir = 'assets/modules/error404logger/';
include_once(MODX_BASE_PATH . $e404logger_dir.'helpers.php');

if (isset($_SESSION['mgrValidated'])) {
    if (event()->name !== 'OnWebPageInit' || !getv('e404_redirect')) {
        return;
    }
    header('Refresh: 0.5; URL=' . getv('e404_redirect'));
    exit;
}

if (event()->name !== 'OnPageNotFound') {
    return;
}
if(array_get(event()->params, 'found_ref_only') === 'yes' && !serverv('HTTP_REFERER')) {
    return;
}

if(array_get(event()->params,'count_robots') === 'no') {
    $robots = explode(
        ','
        , array_get(event()->params,'robots', 'googlebot,baidu,msnbot')
    );
    $host_name = gethostbyaddr(serverv('REMOTE_ADDR'));
    foreach($robots as $robot) {
        if(strpos($host_name, $robot) !== false) {
            return;
        }
    }
}

include_once(MODX_BASE_PATH . $e404logger_dir . 'e404logger.class.inc.php');
$e404 = new Error404Logger();
$e404->insert($remoteIPIndexName);
$e404->purge_log(
    array_get(event()->params, 'limit', 1000)
    , array_get(event()->params, 'trim', 100)
);
