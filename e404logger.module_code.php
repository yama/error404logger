///////////////////////////////////
//
//  Error 404 Logger
//  --------------------------------
//  module version: 0.1
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

$src = get_tpl();
$values['keepLastDays'] = $keepLastDays;
$ph = get_ph($values);

$e404 = new Error404Logger();

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
$res = $e404->getAll();
$urldecode = (isset($modx->config['enable_phx']) && $modx->config['enable_phx']!=0) ? ':urldecode' : '';
$grd = new DataGrid('', $res, $resultsPerPage);
$grd->noRecordMsg = 'There are no Error 404 entries! Good for you...';
$grd->cssClass = "grid";
$grd->columnHeaderClass = "gridHeader";
$grd->itemClass = "gridItem";
$grd->altItemClass = "gridAltItem";
$grd->pagerClass = "pager";
$grd->Class = "page";
$grd->columns = "IP, host, time, URL";

$grd->colTypes = ',,date:' . $modx->toDateFormat(null, 'formatOnly') . ' %H:%M:%S';
$grd->colTypes .= ',template:<a href="[+url+]" target="_blank">[+url' . $urldecode . '+]</a>';

$grd->fields = "ip,host,createdon,url";

if ($showReferer == 'yes')
{
	$grd->columns  .= ',referer';
	$grd->colTypes .= ',template:<a href="' . $modx->config['site_url'] . 'index.php?e404_redirect=[+referer+]" target="_blank">[+referer' . $urldecode . '+]</a>';
	$grd->fields   .= ',template';
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
$grd->colTypes = 'template:<a class="red" href="#" onclick="doRemove(\'[+url+]\'); return false;">Remove</a>,integer';
$grd->fields = "template,num,url";
$grd->pagerLocation = "top-left";

$ph['showtop'] = $grd->render();

$output = parse_tpl($src,$ph);

return $output;



function get_tpl()
{
	$tab = (isset($_GET['tab']) && !empty($_GET['tab'])) ? $_GET['tab'] : '';
	$tpl = <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" [+dir+] lang="[+mxla+]" xml:lang="[+mxla+]">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=[+charset+]" />
<link rel="stylesheet" href="[+site_url+]assets/modules/error404logger/e404logger.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="[+theme_path+]/style.css" />
<title>Error 404 Logger</title>
<script type="text/javascript" language="javascript">
	var  queryString = "?a=[+_GET_a+]&id=[+_GET_id+]";
	
	function navAllInactive()
	{
		oNav = document.getElementById("nav");
		oLis = oNav.getElementsByTagName("LI");
		
		for (i = 0; i < oLis.length; i++)
		{
			oLis[i].className = "";
		}
	}
	
	function hideAllData()
	{
		oData = document.getElementById("data");
		oDivs = oData.getElementsByTagName("DIV");
		
	}
	
	function doRemove(url)
	{
		if (confirm("Really delete entries for '" + url + "'?"))
		{
			url = escape(url);
			window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&tab=top&do=remove&url="+url;
		}
		return false;
	}
	
	function clearAll()
	{
		if (confirm("Really delete ALL entries?"))
		{
			window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&do=clearAll";
		}
		return false;
	}
	
	function clearLast(num)
	{
		if (confirm("Really delete all entries except for the last "+num+" days?"))
		{
			window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&do=clearLast&days="+num;
		}
		return false;
	}
</script>
</head>
<body>
<h1>Error 404 Logger</h1>
<div class="sectionBody">
	<div id="actions">
		<ul class="actionButtons">
		<li onclick="clearAll();"><a href="#">[+_lang_clear_log+]</a></li>
		<li onclick="clearLast([+keepLastDays+]);"><a href="#">[+_lang_clear_log+] recent [+keepLastDays+] days</a></li>
		</ul>
	</div>
	<div class="tab-pane" id="pane1">
	<script type="text/javascript" src="media/script/tabpane.js"></script>
	<script type="text/javascript"> pane1 = new WebFXTabPane(document.getElementById("pane1"),false); </script>
		<div class="tab-page" id="all">
			<h2 class="tab">All entries</h2>
			<script type="text/javascript">pane1.addTabPage(document.getElementById("all"));</script>
			[+logs+]
		</div>
		<div class="tab-page" id="top">
			<h2 class="tab">Most wanted</h2>
			<script type="text/javascript">pane1.addTabPage(document.getElementById("top"));</script>
			<div>[+showing+]</div>
			[+showtop+]
		</div>
	</div>
</div>
</body>
</html>
EOT;
	return $tpl;
}

function get_ph()
{
	global $modx,$modx_textdir,$modx_lang_attribute,$modx_manager_charset,$manager_theme,$_lang,$keepLastDays;
	
	$ph['dir'] = $modx_textdir ? 'dir="rtl" ' : '';
	$ph['mxla'] = $modx_lang_attribute ? $modx_lang_attribute : 'en';
	$ph['charset'] = $modx_manager_charset;
	$ph['site_url'] = MODX_SITE_URL;
	$ph['theme_path'] = MODX_MANAGER_URL . 'media/style/' . $manager_theme;
	$ph['_GET_a']  = $_GET['a'];
	$ph['_GET_id'] = $_GET['id'];
	$ph['_SERVER_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
	$ph['_lang_clear_log'] = $_lang['clear_log'];
	$ph['keepLastDays'] = $keepLastDays;
	return $ph;
}

function parse_tpl($src,$ph)
{
	foreach($ph as $k=>$v)
	{
		$k = '[+' . $k . '+]';
		$src = str_replace($k,$v,$src);
	}
	return $src;
}
