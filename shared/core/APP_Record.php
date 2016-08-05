<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Codeigniter拡張レコード
 *
 * モデルから検索したレコードを取得する
 *
 * @author Yoshikazu Ozawa
 */
class APP_Record {

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $model =& $this->model();

        $record = array();
        foreach ($model->fields as $f) {
            $record[$f] = $this->{$f};
        }

        return $record;
    }

    /**
     * @return mixed
     */
    protected function & model()
    {
        $record_class = get_class($this);
        $model_class = str_replace("_record", "_model", $record_class);

        $CI =& get_instance();

        if ( ! isset($CI->{$model_class})) {
            $CI->load->model($model_class);
        }

        return $CI->{$model_class};
    }

}

