<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('CI_Upload')) {
    require  BASEPATH . "libraries/Upload.php";
}
class APP_Upload extends CI_Upload {

    var $_error_array  = array();
    var $_error_prefix = '<p>';
    var $_error_suffix = '</p>';

    /**
     * 指定されたフィールドにアップロードされているか
     *
     * @access public
     * @param string $field フィールド名
     * @return bool
     */
    public function is_upload($field = 'userfile')
    {
        if ( !isset($_FILES[$field])) {
            return FALSE;
        }

        if ( ! is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $error = ( ! isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];
            return $error != 4;  // ファイルが設定されているかのみ確認
        }

        return TRUE;
    }

    /**
     * 指定されたフィールドのファイルをアップロードする
     *
     * @access public
     * @param string $field フィールド名
     * @return mixed
     */
    public function do_upload($field = 'userfile')
    {
        $result = parent::do_upload($field);

        if ( ! empty($this->error_msg)) {
            $this->_error_array[$field] = is_array($this->error_msg) ? implode(" ", $this->error_msg) : $this->error_msg;
        }

        return $result;
    }

    /**
     * 指定したURLからファイルを取得する（直接URLを指定）
     *
     * @param string $source URL
     *
     * @return array|false
     */
    public function do_upload_from_source($source)
    {
        return $this->do_upload_from_url($source, TRUE);
    }

    /**
     * 指定したURLからファイルを取得する
     *
     * @access public
     *
     * @param string $field
     * @param bool $direct
     *
     * @return array|false
     * @internal param string $url
     */
    public function do_upload_from_url($field = "url", $direct = FALSE)
    {
        $CI =& get_instance();

        if ($direct) {
            $url = $field;
        } else {
            $url = $CI->input->param($field);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        $url_domain = parse_url($url, PHP_URL_HOST);
        $url_path = parse_url($url, PHP_URL_PATH);

        $path = tempnam($this->upload_path, 'apup');

        $file_name = basename($path);

        if (FALSE === ($fp = fopen($path, 'w'))) {
            $this->_error_array[$field] = "ファイルのダウンロードに失敗しました";
            return FALSE;
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);
        fclose($fp);

        if (FALSE === $result) {
            // TODO: メッセージのi18n化
            $this->_error_array[$field] = "ファイルのダウンロードに失敗しました ({$errno})";
            return FALSE;
        }

        if ($errno == CURLE_OPERATION_TIMEOUTED) {
            // TODO: メッセージのi18n化
            $this->_error_array[$field] = "ファイルのダウンロード中にタイムアウトしました ({$errno})";
            return FALSE;
        } else if ($errno !== 0) {
            // TODO: メッセージのi18n化
            $this->_error_array[$field] = "ファイルのダウンロードに失敗しました ({$errno})";
            return FALSE;
        }

        $size = filesize($path);
        $image_info = getimagesize($path);

        $this->file_temp = $path;
        $this->file_size = $size;
        $this->file_type = $content_type;
        $this->file_name = $file_name;
        $this->orig_name = $file_name;
        $this->file_ext  = $this->get_extension($url_path);
        $this->client_name = $url;

        $this->set_image_properties($this->upload_path.$this->file_name);

        if ($this->file_size > 0) {
            $this->file_size = round($this->file_size/1024, 2);
        }

        if (!$this->is_allowed_filesize()) {
            // TODO: エラーメッセージを追加する
            return FALSE;
        }

        if (!$this->is_allowed_filetype()) {
            // TODO: エラーメッセージを追加する
            return FALSE;
        }

        return TRUE;
    }

    public function mimes_types($mime)
    {
        global $mimes;

        if (count($this->mimes) == 0) {
            $loaded = FALSE;

            $paths = array(
                SHAREDPATH.'config/mimes.php',
                SHAREDPATH.'config/'.ENVIRONMENT.'/mimes.php',
                APPPATH.'config/mimes.php',
                APPPATH.'config/'.ENVIRONMENT.'/mimes.php'
            );

            foreach ($paths as $f) {
                if (is_file($f)) {
                    include $f;
                    $loaded = TRUE;
                }
            }

            if (!$loaded) {
                unset($mimes);
                return FALSE;
            }

            $this->mimes = $mimes;
            unset($mimes);
        }

        return ( ! isset($this->mimes[$mime])) ? FALSE : $this->mimes[$mime];
    }

    /**
     * 日本語名を書きかえる
     *
     * @access protected
     * @param string $filename
     * @return string
     */
    protected function _prep_filename($filename){
        if(strlen($filename) != mb_strlen($filename)){
            $ext = substr($filename, strrpos($filename, '.') + 1);
            $filename = md5($filename);
            $filename .= '.'.$ext;
        }
        return parent::_prep_filename($filename);
    }
}

