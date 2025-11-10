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

    /** @var string */
    protected $tableName = '';

    public function __construct() {
        $this->tableName = $this->resolveTableName();
        $this->checkTable();
    }

    protected function resolveTableName() {
        $tableName = evo()->getFullTableName('error_404_logger');
        if (!empty($tableName)) {
            return $tableName;
        }

        $prefix = array_get(evo()->config, 'table_prefix', '');
        if ($prefix === '') {
            return '';
        }

        return $prefix . 'error_404_logger';
    }

    protected function getTableName() {
        if (empty($this->tableName)) {
            $this->tableName = $this->resolveTableName();
        }

        return $this->tableName;
    }
    
    public function getTop($num = null) {
        $table = $this->getTableName();
        if ($table === '') {
            return false;
        }

        $sql = sprintf(
            'SELECT distinct(url), count(url) AS num FROM %s GROUP BY url ORDER BY num DESC'
            , $table
        );
        if ($num) {
            $sql .= ' LIMIT ' . $num;
        };
        return db()->query($sql);
    }
    
    // get all results
    public function getAll() {
        $table = $this->getTableName();
        if ($table === '') {
            return false;
        }

        return db()->select(
            '*'
            , $table
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

        $table = $this->getTableName();
        if ($table === '') {
            return false;
        }

        return db()->insert(
            array(
                'url'       => db()->escape($this->fix_xss_value(serverv('REQUEST_URI'))),
                'ip'        => $ip,
                'host'      => gethostbyaddr($ip),
                'referer'   => db()->escape($this->fix_xss_value(serverv('HTTP_REFERER'))),
                'createdon' => date('Y-m-d H:i:s')
            )
            , $table
        );
    }
    
    // remove specific url from entries
    public function remove($url=null) {
        if($url) {
            $table = $this->getTableName();
            if ($table === '') {
                return 0;
            }

            db()->delete(
                $table
                , sprintf("url='%s'", urldecode($url))
            );
            return db()->getAffectedRows();
        }
        $table = $this->getTableName();
        if ($table === '') {
            return 0;
        }

        return db()->truncate($table);
    }
    
    public function clearLast($num) {
        $table = $this->getTableName();
        if ($table === '') {
            return 0;
        }

        db()->delete(
            $table
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
        
        $table = $this->getTableName();
        if ($table === '') {
            return;
        }

        $rs = db()->select('COUNT(id) as count', $table);
        if($rs) {
            $row = db()->getRow($rs);
        }
        $over = $row['count'] - $limit;
        if(!$over) {
            return;
        }
        db()->delete(
            $table
            , ''
            , 'createdon ASC', $over + $trim
        );
        db()->query(
            sprintf("OPTIMIZE TABLE %s", $table
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

    protected function normalizeTableNameForLike($tableName) {
        $tableName = trim($tableName, '`');
        if (strpos($tableName, '`.`') !== false) {
            $tableName = substr($tableName, strpos($tableName, '`.`') + 3);
            $tableName = trim($tableName, '`');
        } elseif (strpos($tableName, '.') !== false) {
            $parts = explode('.', $tableName);
            $tableName = trim(end($parts), '`');
        }

        return $tableName;
    }

    function table_exists($table_name) {
        $tableLike = $this->normalizeTableNameForLike($table_name);

        return db()->count(
            db()->query(
                sprintf(
                    "SHOW TABLES LIKE '%s'"
                    , $tableLike
                )
            )
        );
    }

    // create table
    public function createTable() {
        $table = $this->getTableName();
        if ($table === '') {
            return false;
        }

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
            , $table
        ));
    }
    
    // check if table exists and update version
    public function checkTable() {
        $table = $this->getTableName();
        if ($table === '') {
            return;
        }

        if(!$this->table_exists($table)) {
            $this->createTable();
        }
        $metaData = db()->getTableMetaData($table);
        if ($metaData['referer']) {
            return;
        }
        db()->query(
            sprintf(
                'ALTER TABLE %s ADD COLUMN `referer` varchar(200) NULL AFTER `host`'
                , $table
            )
        );
    }
}
