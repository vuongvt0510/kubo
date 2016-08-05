<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class APP_Output
 */
class APP_Output extends CI_Output {

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->_zlib_oc = @ini_get('zlib.output_compression');

        $this->mime_types = &get_mimes();

        log_message('debug', "Output Class Initialized");
    }

}

