<?php
global $keepLastDays;

if(!isset($showReferer))    $showReferer    = 'yes';
if(!isset($keepLastDays))   $keepLastDays   = 7;
if(!isset($resultsPerPage)) $resultsPerPage = 20;
if(!isset($showTop))        $showTop        = 20;

// load classes
include_once(__DIR__ . '/helpers.php');
include_once(__DIR__ . '/e404logger.class.inc.php');
include_once(MODX_CORE_PATH . 'controls/datagrid.class.php');

global $modx, $modx_manager_charset, $modx_lang_attribute, $modx_textdir, $manager_theme, $_style, $_lang;

$e404 = new Error404Logger();
$ph = $e404->get_ph();

// check if there is something to do
switch(input_get('do')) {
    case 'remove'   : // remove single URL from list
        if(input_get('url')) {
            $e404->remove(input_get('url'));
        }
        break;
    case 'clearAll' : // clear all data
        $e404->remove();
        break;
    case 'clearLast': // clear data except for last N days
        if(input_get('days')){
            $e404->clearLast(input_get('days'));
        }
}

// create grid with all data
$grd = new DataGrid('', $e404->getAll(), $resultsPerPage);
$grd->noRecordMsg       = 'There are no Error 404 entries! Good for you...';
$grd->cssClass          = 'grid';
$grd->columnHeaderClass = 'gridHeader';
$grd->itemClass         = 'gridItem';
$grd->altItemClass      = 'gridAltItem';
$grd->pagerClass        = 'pager';
$grd->Class             = 'page';
$grd->columns           = 'IP, host, time, URL';

$grd->colTypes = ',,date:' . $modx->toDateFormat(null, 'formatOnly') . ' %H:%M:%S';
$urldecode = (isset($modx->config['enable_phx']) && $modx->config['enable_phx']!=0) ? ':urldecode:escape' : '';
$grd->colTypes .= ',template:<a href="[+url+]" target="_blank">[+url' . $urldecode . '+]</a>';

$grd->fields = 'ip,host,createdon,url';

if ($showReferer === 'yes') {
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

$grd->noRecordMsg       = 'There are no Error 404 entries! Good for you...';
$grd->cssClass          = "grid";
$grd->columnHeaderClass = "gridHeader";
$grd->itemClass         = "gridItem";
$grd->altItemClass      = "gridAltItem";
$grd->columns           = ",count,URL";
$grd->colTypes          = 'template:<a class="red" href="#" onclick="doRemove(\'[+url+]\'); return false;">Remove</a>,integer,template:[+url' . $urldecode . '+]';
$grd->fields            = "template,num,url";
$grd->pagerLocation = "top-left";

$ph['showtop'] = $grd->render();

return $modx->parseText(
    file_get_contents(__DIR__ . '/template.tpl')
    , $ph
);
