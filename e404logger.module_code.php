///////////////////////////////////
//
//  Error 404 Logger
//  --------------------------------
//  module version: 0.07
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
$showReferer = isset($showReferer) ? $showReferer : 'yes';
$keepLastDays = isset($keepLastDays) ? $keepLastDays : 7;
$resultsPerPage = isset($resultsPerPage) ? $resultsPerPage : 20;
$showTop = isset($showTop) ? $showTop : 20;

// load classes
include_once(MODX_BASE_PATH . 'assets/modules/error404logger/e404logger.class.inc.php');
include_once(MODX_BASE_PATH . 'manager/includes/controls/datagrid.class.php');

global $modx, $modx_manager_charset, $modx_lang_attribute, $modx_textdir, $manager_theme, $_style, $_lang;

$src = get_tpl();
$ph = get_ph();
$output = parse_tpl($src,$ph);

$output .= 'var  queryString = "?a='.$_GET['a'].'&id='.$_GET['id'].'";

function navAllInactive() {
oNav = document.getElementById("nav");
oLis = oNav.getElementsByTagName("LI");

for (i = 0; i < oLis.length; i++) {
oLis[i].className = "";
}
}

function hideAllData() {
oData = document.getElementById("data");
oDivs = oData.getElementsByTagName("DIV");

for (i = 0; i < oDivs.length; i++) {
oDivs[i].style.display = "none";
}
}

function showData(ime) {
hideAllData();
navAllInactive();

o = document.getElementById("li-"+ime);
o.className = "active";
o = document.getElementById("activeTab");
o.value = ime;

oData = document.getElementById(ime);
oData.style.display = "block";

return false;
}

function doRemove(url) {
if (confirm("Really delete entries for \'"+url+"\'?")) {
url = escape(url);
window.location = "'.$_SERVER['SCRIPT_NAME'].'"+queryString+"&tab=top&do=remove&url="+url;
}
return false;
}

function clearAll() {
o = document.getElementById("activeTab");  
if (confirm("Really delete ALL entries?")) {
window.location = "'.$_SERVER['SCRIPT_NAME'].'"+queryString+"&tab="+o.value+"&do=clearAll";
}
return false;
}

function clearLast(num) {
o = document.getElementById("activeTab");  
if (confirm("Really delete all entries except for the last "+num+" days?")) {
window.location = "'.$_SERVER['SCRIPT_NAME'].'"+queryString+"&tab="+o.value+"&do=clearLast&days="+num;
}
return false;
}

</script>

</head>
<body>
<h1>Error 404 Logger</h1>
<script type="text/javascript" src="media/script/tabpane.js"></script>
<div class="sectionHeader">Error 404 Logger</div>
<div class="sectionBody" style="padding:10px 20px;">
<input type="hidden" id="activeTab" value="">
<div id="menuDiv">
<ul id="menu">
<li onclick="clearAll();">' . $_lang["clear_log"] . '</li>
<li onclick="clearLast('.$keepLastDays.');">' . $_lang["clear_log"] . ' recent '.$keepLastDays.' days</li>
<li class="close" onclick="window.location=\'index.php?a=106\'">' . $_lang['close'] . '</li>
</ul>
</div>
<div id="nav">
<ul>
<li onclick="showData(\'all\');" id="li-all">All entries</li>
<li id="li-top" onclick="showData(\'top\');">Most wanted</li>
</ul>
</div>
<div id="data">';

$e404 = new Error404Logger();

// check if there is something to do
$action = '';
$action = (empty($_GET['do'])) ? '' : $_GET['do'];

// remove single URL from list
if ($action == "remove")
{
$url = (empty($_GET['url'])) ? '' : $_GET['url'];
if ($url != "")
{
$e404->remove($url);
}
}

// clear all data
if ($action == "clearAll")
{
$e404->clearAll();
}

// clear data except for last N days
if ($action == "clearLast") {
$days = (empty($_GET['days'])) ? '' : $_GET['days'];
if ($url != "") {
$e404->clearLast($days);
}
}

// create grid with all data
$res = $e404->getAll();

$grd = new DataGrid('', $res, $resultsPerPage);
$grd->noRecordMsg = 'There are no Error 404 entries! Good for you...';
$grd->cssClass = "grid";
$grd->columnHeaderClass = "gridHeader";
$grd->itemClass = "gridItem";
$grd->altItemClass = "gridAltItem";
$grd->pagerClass = "pager";
$grd->Class = "page";
$grd->columns = "IP, host, time, URL";
if ($showReferer == 'yes') { $grd->columns .= ',referer'; };

$grd->colTypes = ',,date:' . $modx->toDateFormat(null, 'formatOnly') . ' %H:%M:%S';
if ($showReferer == 'yes') { $grd->colTypes .= ',,template:<a href="[+referer+]" target="_blank">[+referer+]</a>';};

$grd->fields = "ip,host,createdon,url";
if ($showReferer == 'yes') { $grd->fields.= ',template';};

$grd->pagerLocation = "top-left";


$output .= '<div id="all" style="display: none;">';
$output .= $grd->render();
$output .= '</div>';
$output .= '<div id="top" style="display: none;">';


// create most wanted grid
$res = $e404->getTop($showTop);
$howMany = $showTop == 0 ? 99999 : $showTop;

if ($showTop != 0) { 
$output .= "Showing top ".$howMany."<br /> <br />";
} else {
$output .= "Showing all<br /> <br />";
}

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

$output .= $grd->render();
if(empty($_GET['tab'])) $_GET['tab'] ='';

$output .= '</div>';
$output .= '

<script language="javascript" type="text/javascript">
var tab = "'.$_GET['tab'].'";
if (tab == "") {
showData(\'all\');
} else {
showData(tab);
}

</script>
</body>
</html>';

return $output;



function get_tpl()
{
	$tpl = <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" [+dir+] lang="[+mxla+]" xml:lang="[+mxla+]">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=[+charset+]" />
<link rel="stylesheet" href="[+site_url+]assets/modules/error404logger/e404logger.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="[+theme_path+]/style.css" />
<title>Error 404 Logger</title>
<script type="text/javascript" language="javascript">
EOT;
	return $tpl;
}

function get_ph()
{
	global $modx,$modx_textdir,$modx_lang_attribute,$modx_manager_charset,$manager_theme;
	
	$ph['dir'] = $modx_textdir ? 'dir="rtl" ' : '';
	$ph['mxla'] = $modx_lang_attribute ? $modx_lang_attribute : 'en';
	$ph['charset'] = $modx_manager_charset;
	$ph['site_url'] = MODX_SITE_URL;
	$ph['theme_path'] = MODX_MANAGER_URL . 'media/style/' . $manager_theme;
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
