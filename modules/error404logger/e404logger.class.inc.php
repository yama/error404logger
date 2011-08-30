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
var $tableName;
var $tableNameModx;

	// constructor
	function Error404Logger()
	{
		global $modx;
		
		$this->tableName = 'error_404_logger';
		$this->tableNameModx = $modx->getFullTableName($this->tableName);
		return $this->checkTable();
	}
	
	// create table
	function createTable()
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$sql = "CREATE TABLE ".$this->tableNameModx." (
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
		$table = $this->tableNameModx;
		
		$rs = $modx->db->query("DESC {$table}");
		if(!$rs)
		{
			return $this->createTable($table);
		}
		else
		{
			$metaData = $modx->db->getTableMetaData($table);
			
			// version 0.02
			if ($metaData['referer'] == '')
			{
				$sql = "ALTER TABLE {$table} ADD COLUMN `referer` varchar(200) NULL AFTER `host`";
				return $res = $modx->db->query($sql);
			}
		}
	}
	
	// get top N queries for nonexistent pages
	function getTop($num = 0)
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$sql = "SELECT distinct(url), count(url) AS num FROM {$table} GROUP BY url ORDER BY num DESC ";
		if ($num != 0) {$sql .= "LIMIT {$num}";};
		$res = $modx->db->query($sql);
		return $res;
	}
	
	// get all results
	function getAll()
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$res = $modx->db->query("SELECT * FROM {$table} ORDER BY createdon DESC");
		return $res;
	}
	
	// add 404 query
	function insert()
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$url       = $this->fix_xss_value($_SERVER["REQUEST_URI"]);
		$ip        = $_SERVER["REMOTE_ADDR"];
		//	$ip = long2ip($ip);
		$host      = gethostbyaddr($ip);
		$referer   = $this->fix_xss_value($_SERVER["HTTP_REFERER"]);
		$createdon = date('Y-m-d H:i:s',time());
		
		$modx->db->query("INSERT INTO {$table} (url, ip, host, referer, createdon) VALUES ('{$url}','{$ip}','{$host}','{$referer}','$createdon')");
		return $modx->db->getInsertId();
	}
	
	// remove specific url from entries
	function remove($url)
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$url = urldecode($url);
		$modx->db->query("DELETE FROM {$table} WHERE url = '".$url."'");
		return $modx->db->getAffectedRows();
	}
	
	function clearAll()
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$modx->db->query("DELETE FROM {$table}");
		return $modx->db->getAffectedRows();
	}
	
	function clearLast($num)
	{
		global $modx;
		$table = $this->tableNameModx;
		
		$datum = time() - $num * 3600 * 24 ;
		$modx->db->query("DELETE FROM {$table} WHERE UNIX_TIMESTAMP(createdon) < {$datum}");
		return $modx->db->getAffectedRows();
	}
	
	function fix_xss_value($value)
	{
		global $modx;
		
		$value = $modx->db->escape($value);
		$value = htmlentities($value, ENT_QUOTES, mb_internal_encoding());
		return $value;
	}
	
	function purge_log($limit=1000,$trim=100)
	{
		global $modx;
		
		if($limit < $trim) $trim = $limit;
		
		$tbl_logs404 = $this->tableNameModx;
		$sql = 'SELECT COUNT(id) as count FROM ' . $tbl_logs404;
		$rs = $modx->db->query($sql);
		if($rs) $row = $modx->db->getRow($rs);
		$over = $row['count'] - $limit;
		if($over > 0)
		{
			$sql = 'DELETE FROM ' . $tbl_logs404 . ' LIMIT ' . ($over + $trim);
			$modx->db->query($sql);
			$sql = 'OPTIMIZE TABLE ' . $tbl_logs404;
			$modx->db->query($sql);
		}
	}
}
