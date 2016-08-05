<?php

/**
 * データ作成クラス
 *
 * @author Yoshikazu Ozawa
 */
class APP_Data_generator {

    const OPERATOR_NAME = "system";

    var $models = array();

    protected $CI = NULL;

    public function __construct($options = array())
    {
        $this->CI =& get_instance();

        if ( ! isset($this->CI->db)) {
            $this->CI->db =& $this->CI->load->database("master", TRUE);
        }

        if ( ! isset($this->CI->dbs)) {
            $this->CI->dbs =& $this->CI->load->database("slave", TRUE);
        }

        foreach ($this->models as $name) {
            $this->CI->load->model($name . "_model");
        }

        $this->CI->load->helper("format_helper");
    }

    /**
     * データ登録
     * 特定のプレフィックスと番号で、データをランダムで登録する
     *
     * @access public
     * @param string $prefix
     * @param integer $start
     * @param integer $end
     * @return void
     */
    public function generate($prefix, $start, $end, $options = array())
    {
        set_time_limit(0);

        if (TRUE !== ($result = $this->before_generate($options))) {
            echo sprintf("ERROR!!! %s\n", $result);
            return;
        }

        for ($i = $start; $i <= $end; $i++) {
            echo sprintf("create %s %d ... ", $prefix, $i);

            $data = $this->prepare_data($prefix, $i);
            if ( ! is_array($data)) {
                echo sprintf("ERROR!!! %s\n", $data);
                continue;
            }
            $result = call_user_func_array(array($this, "create"), $data);
            if (TRUE !== $result) {
                echo sprintf("ERROR!!! %s\n", $result);
                continue;
            }

            echo "OK\n";
        }
    }

    /**
     * データ整形
     * データを作成する
     *
     * @access protected
     * @param array $prefix
     * @param array $idx
     * @return string
     */
    protected function prepare_data($prefix, $idx)
    {
        return "prepare_data() must be override";
    }

    /**
     * CSV読み込みよるデータの作成
     *
     * @access public
     * @param string $file
     * @param array $options
     */
    public function load_file($file, $options = array())
    {
        set_time_limit(0);

        if (TRUE !== ($result = $this->before_generate($options))) {
            echo sprintf("ERROR!!! %s\n", $result);
            return;
        }

        $fp = fopen($file, "r");

        while ($data = fgetcsv($fp)) {
            echo sprintf("create %s ... ", mb_truncate(implode(",", $data), 30, "(...)"));

            $data = $this->prepare_csv_data($data);
            if ( ! is_array($data)) {
                echo sprintf("ERROR!!! %s\n", $data);
                continue;
            }
            $result = call_user_func_array(array($this, "create"), $data);
            if (TRUE !== $result) {
                echo sprintf("ERROR!!! %s\n", $result);
                continue;
            }

            echo "OK\n";
        }

        fclose($fp);
    }

    /**
     * CSVデータ正規化
     * CSVから読み込まれたデータを作成ロジックにまわすために正規化を行う
     *
     * @access protected
     * @param array $data
     * @return array
     */
    protected function prepare_csv_data($data)
    {
        return $data;
    }

    /**
     * データ生成前処理
     * マスターデータ取得などはここで
     *
     * @access protected
     * @param array $options
     * @return bool
     */
    protected function before_generate($options = array())
    {
        return TRUE;
    }
}

