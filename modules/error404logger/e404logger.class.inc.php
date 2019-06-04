<?php
///////////////////////////////////
//
//  Error 404 Logger
//  --------------------------------
//  class version: 0.07
//  suitable for MODx 0.9.5+
//
//  author     : Andraz Kozelj (andraz dot kozelj at amis dot net)
//  written on : 14.03.2007
//
//

class Error404Logger {

// set table name
public $tbl_error_404_logger;

    public function __construct()
    {
        global $modx;
        
        $this->tbl_error_404_logger = $modx->getFullTableName('error_404_logger');
        $this->checkTable();
    }
    
    // create table
    public function createTable()
    {
        global $modx;

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                `id` int(10) unsigned NOT NULL auto_increment,
                `createdon` datetime NOT NULL,
                `ip` varchar(20) NOT NULL,
                `host` varchar(100) NOT NULL,
                `url` varchar(200) NOT NULL,
                `referer` varchar(200) NOT NULL,
                PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM AUTO_INCREMENT=1 ;'
            , $this->tbl_error_404_logger
        );
        return $modx->db->query($sql);
    }
    
    // check if table exists and update version
    public function checkTable()
    {
        global $modx;

        $this->createTable($this->tbl_error_404_logger);
        
        $metaData = $modx->db->getTableMetaData($this->tbl_error_404_logger);

        if ($metaData['referer'] == '')
        {
            return $modx->db->query(
                sprintf(
                    'ALTER TABLE %s ADD COLUMN `referer` varchar(200) NULL AFTER `host`'
                    , $this->tbl_error_404_logger
                )
            );
        }
        return false;
    }
    
    // get top N queries for nonexistent pages
    public function getTop($num = 0)
    {
        global $modx;

        $sql = sprintf(
            'SELECT distinct(url), count(url) AS num FROM %s GROUP BY url ORDER BY num DESC '
            , $this->tbl_error_404_logger);
        if ($num != 0) {
            $sql .= 'LIMIT ' . $num;
        };
        return $modx->db->query($sql);
    }
    
    // get all results
    public function getAll()
    {
        global $modx;

        return $modx->db->select('*', $this->tbl_error_404_logger, '', 'createdon DESC');
    }
    
    // add 404 query
    public function insert($remoteIPIndexName='')
    {
        global $modx;

        $url = $this->fix_xss_value($_SERVER['REQUEST_URI']);
        if( !empty($remoteIPIndexName) && isset($_SERVER[$remoteIPIndexName]) )
        {
            $ip = preg_replace('/.*[, ]([^, ]+)\z/','$1',$_SERVER[$remoteIPIndexName]); //For multi stairs proxy
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        //    $ip = long2ip($ip);
        $host      = gethostbyaddr($ip);
        $referer   = $this->fix_xss_value($_SERVER['HTTP_REFERER']);
        $createdon = date('Y-m-d H:i:s');
        $f = compact('url', 'ip', 'host', 'referer', 'createdon');
        return $modx->db->insert($f, $this->tbl_error_404_logger);
    }
    
    // remove specific url from entries
    public function remove($url)
    {
        global $modx;

        $modx->db->query(
            sprintf(
                "DELETE FROM %s WHERE url='%s'"
                , $this->tbl_error_404_logger
                , urldecode($url)
            )
        );
        return $modx->db->getAffectedRows();
    }
    
    public function clearAll()
    {
        global $modx;

        $modx->db->delete($this->tbl_error_404_logger);
        return $modx->db->getAffectedRows();
    }
    
    public function clearLast($num)
    {
        global $modx;

        $datum = time() - $num * 3600 * 24 ;
        $modx->db->delete($this->tbl_error_404_logger, 'UNIX_TIMESTAMP(createdon) < ' . $datum);
        return $modx->db->getAffectedRows();
    }
    
    public function fix_xss_value($value)
    {
        global $modx;
        
        $value = $modx->db->escape($value);
        $value = htmlentities($value, ENT_QUOTES, mb_internal_encoding());
        $value = str_replace('&amp;', '&', $value);
        return $value;
    }
    
    public function purge_log($limit=1000, $trim=100)
    {
        global $modx;
        
        if($limit < $trim) {
            $trim = $limit;
        }
        
        $rs = $modx->db->select('COUNT(id) as count', $this->tbl_error_404_logger);
        if($rs) {
            $row = $modx->db->getRow($rs);
        }
        $over = $row['count'] - $limit;
        if(!$over) {
            return;
        }
        $modx->db->delete($this->tbl_error_404_logger,'', 'createdon ASC', $over + $trim);
        $modx->db->query('OPTIMIZE TABLE ' . $this->tbl_error_404_logger);
    }

    public function get_tpl()
    {
        $tab = (isset($_GET['tab']) && !empty($_GET['tab'])) ? $_GET['tab'] : '';
        $tpl = <<< EOT
<!DOCTYPE html>
<html lang="[+lc+]">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=[+charset+]" />
<link rel="stylesheet" href="[+site_url+]assets/modules/error404logger/e404logger.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="[+theme_path+]/style.css" />
<title>Error 404 Logger</title>
<script>
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
        global $modx_textdir,$modx_lang_attribute,$modx_manager_charset,$manager_theme,$_lang,$keepLastDays;

        $ph['dir']                 = ($modx_textdir && $modx_textdir==='rtl') ? 'dir="rtl" ' : '';
        $ph['lc']                  = $modx_lang_attribute ? $modx_lang_attribute : 'en';
        $ph['charset']             = $modx_manager_charset;
        $ph['site_url']            = MODX_SITE_URL;
        $ph['theme_path']          = MODX_MANAGER_URL . 'media/style/' . $manager_theme;
        $ph['_GET_a']              = $_GET['a'];
        $ph['_GET_id']             = $_GET['id'];
        $ph['_SERVER_SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];
        $ph['_lang_clear_log']     = $_lang['clear_log'];
        $ph['keepLastDays']        = $keepLastDays;
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
}
