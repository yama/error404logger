<?php
///////////////////////////////////
//
//  Error 404 Logger
//  --------------------------------
//  module version: 0.2
//  suitable for MODx 0.9.5+
//
//  author: Andraz Kozelj (andraz dot kozelj at amis dot net)
//  date created:  14.03.2007
//  date modified: 08.18.2010 by yama
//
//  config string:  &showReferer=Show referer;list;yes,no;yes &keepLastDays=Clear all but last N days;int;7 &resultsPerPage=Results per page;int;20 &showTop=Number of most wanted (0 for all);int;20
// 
//

// config parameters
global $keepLastDays;
$showReferer = isset($showReferer) ? $showReferer : 'yes';
$keepLastDays = isset($keepLastDays) ? $keepLastDays : 7;
$resultsPerPage = isset($resultsPerPage) ? $resultsPerPage : 20;
$showTop = isset($showTop) ? $showTop : 20;

// load classes
include_once(MODX_BASE_PATH . 'assets/modules/error404logger/e404logger.class.inc.php');
include_once(MODX_BASE_PATH . 'manager/includes/controls/datagrid.class.php');

global $modx, $modx_manager_charset, $modx_lang_attribute, $modx_textdir, $manager_theme, $_style, $_lang;

$values['keepLastDays'] = $keepLastDays;

$e404 = new Error404Logger();
$ph = $e404->get_ph($values);

// check if there is something to do
$action = '';
if(isset($_GET['do']) && !empty($_GET['do'])) $action = $_GET['do'];

switch($action)
{
    case 'remove'   : // remove single URL from list
        if(isset($_GET['url']) && !empty($_GET['url'])) $e404->remove($_GET['url']);
        break;
    case 'clearAll' : // clear all data
        $e404->clearAll();
        break;
    case 'clearLast': // clear data except for last N days
        if(isset($_GET['days']) && !empty($_GET['days']))
        {
            $days = (!isset($_GET['days']) || empty($_GET['days'])) ? '' : $_GET['days'];
            $e404->clearLast($days);
        }
        break;
}

// create grid with all data
$grd = new DataGrid('', $e404->getAll(), $resultsPerPage);
$grd->noRecordMsg = 'There are no Error 404 entries! Good for you...';
$grd->cssClass = 'grid';
$grd->columnHeaderClass = 'gridHeader';
$grd->itemClass = 'gridItem';
$grd->altItemClass = 'gridAltItem';
$grd->pagerClass = 'pager';
$grd->Class = 'page';
$grd->columns = 'IP, host, time, URL';

$grd->colTypes = ',,date:' . $modx->toDateFormat(null, 'formatOnly') . ' %H:%M:%S';
$urldecode = (isset($modx->config['enable_phx']) && $modx->config['enable_phx']!=0) ? ':urldecode:escape' : '';
$grd->colTypes .= ',template:<a href="[+url+]" target="_blank">[+url' . $urldecode . '+]</a>';

$grd->fields = 'ip,host,createdon,url';

if ($showReferer === 'yes')
{
    $grd->columns  .= '/referer';
    $grd->colTypes .= '<br /><a href="' . $modx->config['site_url'] . 'index.php?e404_redirect=[+referer+]" target="_blank">[+referer' . $urldecode . '+]</a>';
}

$grd->pagerLocation = 'top-left';

$ph['logs'] = $grd->render();

// create most wanted grid
$howMany = $showTop == 0 ? 99999 : $showTop;

$ph['showing'] = ($showTop != 0) ? "<p>Showing top {$howMany}</p>" : '<p>Showing all</p>';

$res = $e404->getTop($showTop);
$grd = new DataGrid('', $res, $howMany);

$grd->noRecordMsg = 'There are no Error 404 entries! Good for you...';
$grd->cssClass = "grid";
$grd->columnHeaderClass = "gridHeader";
$grd->itemClass = "gridItem";
$grd->altItemClass = "gridAltItem";
$grd->columns = ",count,URL";
$grd->colTypes = 'template:<a class="red" href="#" onclick="doRemove(\'[+url+]\'); return false;">Remove</a>,integer,template:[+url' . $urldecode . '+]';
$grd->fields = "template,num,url";
$grd->pagerLocation = "top-left";

$ph['showtop'] = $grd->render();

return $e404->parse_tpl($e404->get_tpl(),$ph);
