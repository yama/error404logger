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

    public function __construct() {
        $this->checkTable();
    }
    
    public function getTop($num = null) {
        $sql = sprintf(
            'SELECT distinct(url), count(url) AS num FROM %s GROUP BY url ORDER BY num DESC'
            , evo()->getFullTableName('error_404_logger')
        );
        if ($num) {
            $sql .= ' LIMIT ' . $num;
        };
        return db()->query($sql);
    }
    
    // get all results
    public function getAll() {
        return db()->select(
            '*'
            , evo()->getFullTableName('error_404_logger')
            , ''
            , 'createdon DESC'
        );
    }
    
    // add 404 query
    public function insert($remoteIPIndexName='') {
        if($remoteIPIndexName && serverv($remoteIPIndexName)) {
            $ip = preg_replace('/.*[, ]([^, ]+)\z/','$1', serverv($remoteIPIndexName));
        } else {
            $ip = serverv('REMOTE_ADDR');
        }
        return db()->insert(
            array(
                'url'       => db()->escape($this->fix_xss_value(serverv('REQUEST_URI'))),
                'ip'        => $ip,
                'host'      => gethostbyaddr($ip),
                'referer'   => db()->escape($this->fix_xss_value(serverv('HTTP_REFERER'))),
                'createdon' => date('Y-m-d H:i:s')
            )
            , evo()->getFullTableName('error_404_logger')
        );
    }
    
    // remove specific url from entries
    public function remove($url=null) {
        if($url) {
            db()->delete(
                evo()->getFullTableName('error_404_logger')
                , sprintf("url='%s'", urldecode($url))
            );
            return db()->getAffectedRows();
        }
        return db()->truncate(evo()->getFullTableName('error_404_logger'));
    }
    
    public function clearLast($num) {
        db()->delete(
            evo()->getFullTableName('error_404_logger')
            , sprintf(
                'UNIX_TIMESTAMP(createdon) < %s'
                , (time() - $num * 3600 * 24)
            )
        );
        return db()->getAffectedRows();
    }
    
    public function fix_xss_value($value) {
        return str_replace(
            '&amp;'
            , '&'
            , evo()->hsc($value)
        );
    }
    
    public function purge_log($limit=1000, $trim=100) {
        if($limit < $trim) {
            $trim = $limit;
        }
        
        $rs = db()->select('COUNT(id) as count', evo()->getFullTableName('error_404_logger'));
        if($rs) {
            $row = db()->getRow($rs);
        }
        $over = $row['count'] - $limit;
        if(!$over) {
            return;
        }
        db()->delete(
            evo()->getFullTableName('error_404_logger')
            , ''
            , 'createdon ASC', $over + $trim
        );
        db()->query(
            sprintf("OPTIMIZE TABLE %s", evo()->getFullTableName('error_404_logger')
            )
        );
    }

    public function get_ph() {
        return array(
            'dir'                 => (globalv('modx_textdir')==='rtl') ? 'dir="rtl" ' : '',
            'lc'                  => globalv('modx_lang_attribute','en'),
            'charset'             => globalv('modx_manager_charset','utf-8'),
            'site_url'            => MODX_SITE_URL,
            'theme_path'          => MODX_MANAGER_URL . 'media/style/' . globalv('manager_theme'),
            '_GET_a'              => getv('a'),
            '_GET_id'             => getv('id'),
            '_SERVER_SCRIPT_NAME' => serverv('SCRIPT_NAME'),
            '_lang_clear_log'     => array_get(globalv('_lang',array()),'clear_log'),
            'keepLastDays'        => globalv('keepLastDays',7)
        );
    }

    function table_exists($table_name) {
        return db()->count(
            db()->query(
                sprintf(
                    "SHOW TABLES LIKE '%s'"
                    , $table_name
                )
            )
        );
    }

    // create table
    public function createTable() {
        return db()->query(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                `id` int(10) unsigned NOT NULL auto_increment,
                `createdon` datetime NOT NULL,
                `ip` varchar(20) NOT NULL,
                `host` varchar(100) NOT NULL,
                `url` varchar(200) NOT NULL,
                `referer` varchar(200) NOT NULL,
                PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM AUTO_INCREMENT=1 ;'
            , evo()->getFullTableName('error_404_logger')
        ));
    }
    
    // check if table exists and update version
    public function checkTable() {
        if(!$this->table_exists(evo()->getFullTableName('error_404_logger'))) {
            $this->createTable();
        }
        $metaData = db()->getTableMetaData(evo()->getFullTableName('error_404_logger'));
        if ($metaData['referer']) {
            return;
        }
        db()->query(
            sprintf(
                'ALTER TABLE %s ADD COLUMN `referer` varchar(200) NULL AFTER `host`'
                , evo()->getFullTableName('error_404_logger')
            )
        );
    }
}
