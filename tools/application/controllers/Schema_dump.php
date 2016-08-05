<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * スキーマダンプ生成
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozaw@interest-marketing.net>
 */

class Schema_dump extends APP_Cli_controller
{
    public function execute()
    {
        $databases = func_get_args();
        if (empty($databases)) {
            log_message("ERROR", "database name is not set.");
            log_message("ERROR", "Usage: schema_dump/execute [database1 database2 ...]");
            return;
        }

        if ($databases[0] == "all") {
            $q = $this->db->query("SHOW DATABASES");
            if (FALSE === $q) {
                log_message("ERROR", "SHOW DATABASES is failed.");
                exit(99);
            }

            $databases = $q->result_array();
            $q->free_result();

            $databases = array_map(function($d){
                list($dd) = array_values($d);
                return $dd;
            }, $databases);

            $databases = array_filter($databases, function($d){
                return !in_array($d, array("information_schema", "mysql", "performance_schema"));
            });
        } 

        $schema = array();

        foreach ($databases as $db) {
            $db_schema = array(
                'name' => $db,
                'tables' => array()
            );

            $this->db->change_database($db);

            $q = $this->db->query("SHOW TABLES");
            if (FALSE === $q) {
                log_message("ERROR", "SHOW TABLES is failed.");
                exit(99);
            }

            $tables = $q->result_array();
            $q->free_result();

            foreach ($tables as $t) {
                list($table) = array_values($t);

                $table_schema = array(
                    'name' => $table,
                    'columns' => array()
                );

                $q = $this->db->query("DESC `{$table}`");
                if (FALSE === $q) {
                    log_message("ERROR", "DESC {$table} is failed.");
                    exit(99);
                }

                $columns = $q->result_array();
                $q->free_result();

                $tc = array();
                foreach($columns as $c) {
                    $tc = array(
                        'name' => $c['Field'],
                        'type' => $this->convert_strict_type_to_php_type($c['Type']),
                        'strict_type' => $c['Type'],
                        'null' => ($c['Null'] === "NO") ? 0 : 1
                    );

                    $table_schema['columns'][] = $tc;
                }

                $db_schema['tables'][] = $table_schema;
            }
            
            $schema[] = $db_schema;
        }

        $engine =& $this->_template_engine();

        $data = $engine->view('schema_dump/schema.php', array('schema' => $schema), TRUE);
        file_put_contents(SHAREDPATH . "/config/schema.php", $data);
    }

    /**
     * MySQL型からphp型へ変換
     *
     * @param string $strict_type
     * @return string
     */
    private function convert_strict_type_to_php_type($strict_type)
    {
        if (preg_match("/^timestamp$/i", $strict_type)) {
            return "datetime";
        }

        if (preg_match("/^(datetime|date|time|year)$/i", $strict_type)) {
            return $strict_type;
        }

        if (preg_match("/^(enum|text|char|varchar)/i", $strict_type)) {
            return "string";
        }

        if (preg_match("/^(tinyblob|blob|mediumblob|longblob)/i", $strict_type)) {
            return "string";
        }

        if (preg_match("/^(float|double)/i", $strict_type)) {
            return "float";
        }

        if (preg_match("/^bigint/i", $strict_type)) {
            return "string";
        }

        if (preg_match("/^(tinyint|smallint|mediumint|int)/")) {
            return "integer";
        }

        return 'string';
    }
}

