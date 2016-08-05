<?php

/**
 * モデルのテストケース追加
 */
class CIUnit_ModelTestCase extends CIUnit_TestCase
{
    protected $models = array();

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // 生成ビルダーの追加
        foreach ($this->models as $m) {
            if (!file_exists($file = TESTSPATH . "/builders/models/{$m}_builder.php")) {
                continue;
            }

            require_once $file;
            $builder_instance = $m."_builder";
            $builder_class = ucfirst($m."_builder");
            $this->{$builder_instance} = new $builder_class($this);
        }
    }

    /**
     * Set Up
     *
     * モデルの自動読み込み
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        foreach ($this->models as $m) {
            $this->CI->load->model($m);
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->trans_reset_status();
            $this->{$m} =& $this->CI->{$m};
        }
    }

    /**
     * Tear down
     *
     * 利用したモデルに関連するデータを全削除
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();

        foreach (array_reverse($this->models) as $m) {
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->trans_reset_status();
            $this->CI->{$m}->empty_table();
        }
    }
}

