<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class APP_URI
 */
class APP_URI extends CI_URI {

    /**
     * @var string
     */
    var $extension = 'html';

    /**
     * セグメントを分割
     */
    public function _explode_segments()
    {
        parent::_explode_segments();

        if ( ! ((php_sapi_name() == 'cli') or defined('STDIN'))) {
            // 拡張子確認
            if (FALSE !== strpos(end($this->segments), '.')) {
                $names = explode('.', array_pop($this->segments));

                $this->extension = array_pop($names);
                $this->segments[] = implode('.', $names);
            }
        }
    }

}
