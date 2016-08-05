<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * データベーススキーマモデル
 *
 * @author Yoshikazu Ozawa
 * @see CI_Model
 */
class APP_DB_schema
{
    /**
     * @var null
     */
    private $schema = NULL;

    /**
     * @param array $params
     *
     * @throws APP_DB_schema_exception
     */
    public function __construct($params = array())
    {
        $params = array_merge(array(
            'file_path' => SHAREDPATH . "/config/schema.php"
        ), $params);

        if (!file_exists($params['file_path'])) {
            throw new APP_DB_schema_exception(sprintf("schema file (%s) is not found.", $params['file_path']), 9000);
        }

        include $params['file_path'];
        if (empty($config['database_schema'])) {
            throw new APP_DB_schema_exception(sprintf("schema file (%s) is empty.", $params['file_path']), 9001);
        }
        $this->schema = $config['database_schema'];
    }

    /**
     * @param $database
     * @param $table
     *
     * @return array
     * @throws APP_DB_schema_exception
     */
    public function list_fields($database, $table)
    {
        if (!isset($this->schema[$database][$table])) {
            throw new APP_DB_schema_exception(sprintf("schema (%s.%s) is not found.", $database, $table), 9002);
        }

        return array_keys($this->schema[$database][$table]);
    }
}

/**
 * データベーススキーマ例外クラス
 *
 * @author Yoshikazu Ozawa
 * @see CI_Model
 */
class APP_DB_schema_exception extends APP_Exception
{
}

