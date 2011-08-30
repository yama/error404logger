//<?php
/**
 * Error 404 Logger
 *
 * Plugin logs requests that trigger an Page not found error.
 *
 * @category    plugin
 * @version     0.0.8
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Andraz Kozelj (andraz dot kozelj at amis dot net) date created: 14.03.2007
 * @author      yama (http://kyms.jp)
 * @internal    @events        OnPageNotFound
 * @internal    @modx_category Manager and Admin
 * @internal    @properties &found_ref_only=Found ref only;list;yes,no;yes &count_robots=Robots count;list;yes,no;no; &robots=Robots list;text;googlebot,baidu,msnbot;&limit=Number of limit logs;1000 &trim=Number deleted at a time;100
 */

$found_ref_only = (empty($found_ref_only)) ? 'no' : $found_ref_only;
$count_robots   = (empty($count_robots))   ? 'yes' : $count_robots;
$robots         = (empty($robots))         ? 'googlebot,baidu,msnbot' : $robots;
if(empty($limit)) $limit = 1000;
if(empty($trim))  $trim = 100;

if($found_ref_only == 'yes' && empty($_SERVER['HTTP_REFERER'])) return;
if($count_robots   == 'no')
{
	$host_name = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	foreach(explode(',',$robots) as $robot)
	{
		if(strstr($host_name, $robot)!==false) return;
	}
}

include_once($modx->config['base_path'] . 'assets/modules/error404logger/e404logger.class.inc.php');
$e404 = new Error404Logger();

$e = & $modx->Event;
switch($e->name)
{
	case 'OnPageNotFound' :
		$e404->insert();
		$e404->purge_log($limit,$trim);
		break;
}
