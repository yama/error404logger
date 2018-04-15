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
var $tbl_error_404_logger;

    // constructor
    function Error404Logger()
    {
        global $modx;
        
        $this->tbl_error_404_logger = $modx->getFullTableName('error_404_logger');
        return $this->checkTable();
    }
    
    // create table
    function createTable()
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $sql = "CREATE TABLE IF NOT EXISTS ".$tbl_error_404_logger." (
        `id` int(10) unsigned NOT NULL auto_increment,
        `createdon` datetime NOT NULL,
        `ip` varchar(20) NOT NULL,
        `host` varchar(100) NOT NULL,
        `url` varchar(200) NOT NULL,
        `referer` varchar(200) NOT NULL,
        PRIMARY KEY  (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=1 ;";
        $res = $modx->db->query($sql);
        return $res;
    }
    
    // check if table exists and update version
    function checkTable()
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $this->createTable($tbl_error_404_logger);
        
        $metaData = $modx->db->getTableMetaData($tbl_error_404_logger);
        // version 0.02
        if ($metaData['referer'] == '')
        {
            $sql = "ALTER TABLE {$tbl_error_404_logger} ADD COLUMN `referer` varchar(200) NULL AFTER `host`";
            return $res = $modx->db->query($sql);
        }
    }
    
    // get top N queries for nonexistent pages
    function getTop($num = 0)
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $sql = "SELECT distinct(url), count(url) AS num FROM {$tbl_error_404_logger} GROUP BY url ORDER BY num DESC ";
        if ($num != 0) {$sql .= "LIMIT {$num}";};
        $res = $modx->db->query($sql);
        return $res;
    }
    
    // get all results
    function getAll()
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $res = $modx->db->select('*', $tbl_error_404_logger, '', 'createdon DESC');
        return $res;
    }
    
    // add 404 query
    function insert($remoteIPIndexName='')
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $url       = $this->fix_xss_value($_SERVER["REQUEST_URI"]);
        if( !empty($remoteIPIndexName) && isset($_SERVER[$remoteIPIndexName]) )
        {
            $ip = $_SERVER[$remoteIPIndexName];
            $ip = preg_replace('/.*[, ]([^, ]+)\z/','$1',$ip); //For multi stairs proxy
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        //    $ip = long2ip($ip);
        $host      = gethostbyaddr($ip);
        $referer   = $this->fix_xss_value($_SERVER["HTTP_REFERER"]);
        $createdon = date('Y-m-d H:i:s',time());
        $f = compact('url', 'ip', 'host', 'referer', 'createdon');
        $newid = $modx->db->insert($f, $tbl_error_404_logger);
        return $newid;
    }
    
    // remove specific url from entries
    function remove($url)
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $url = urldecode($url);
        $modx->db->query("DELETE FROM {$tbl_error_404_logger} WHERE url = '".$url."'");
        return $modx->db->getAffectedRows();
    }
    
    function clearAll()
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $modx->db->delete($tbl_error_404_logger);
        return $modx->db->getAffectedRows();
    }
    
    function clearLast($num)
    {
        global $modx;
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        
        $datum = time() - $num * 3600 * 24 ;
        $modx->db->delete($tbl_error_404_logger, "UNIX_TIMESTAMP(createdon) < {$datum}");
        return $modx->db->getAffectedRows();
    }
    
    function fix_xss_value($value)
    {
        global $modx;
        
        $value = $modx->db->escape($value);
        $value = htmlentities($value, ENT_QUOTES, mb_internal_encoding());
        $value = str_replace('&amp;', '&', $value);
        return $value;
    }
    
    function purge_log($limit=1000,$trim=100)
    {
        global $modx;
        
        if($limit < $trim) $trim = $limit;
        
        $tbl_error_404_logger = $this->tbl_error_404_logger;
        $rs = $modx->db->select('COUNT(id) as count',$tbl_error_404_logger);
        if($rs) $row = $modx->db->getRow($rs);
        $over = $row['count'] - $limit;
        if($over > 0)
        {
            $modx->db->delete($tbl_error_404_logger,'', 'createdon ASC', $over + $trim);
            $modx->db->query("OPTIMIZE TABLE {$tbl_error_404_logger}");
        }
    }
}
