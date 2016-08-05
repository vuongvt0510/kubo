<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists('CI_Form_validation')) {
    require_once BASEPATH . 'libraries/Form_validation.php';
}

/**
 * 検証クラス
 *
 * Form_validationが汎用性ないので書きかえて、
 * POSTだけでなく他のパラメータも利用できるように修正
 */
class APP_Form_validation extends CI_Form_validation {

    /**
     * @var array
     */
    protected $params = NULL;

    /**
     * APP_Form_validation constructor.
     * @param array $rules
     */
    public function __construct($rules = array())
    {
        parent::__construct($rules);
        $this->params =& $_POST;
    }

    /**
     * エラーがある場合は、出力して終了させる
     *
     * @access public
     * @param array $extra
     * @param array $options
     * @param bool $render
     *
     * @return array
     */
    public function error_json($extra = array(), $options = array(), $render = TRUE)
    {
        if ($render) {
            $this->CI->_submit_false_json($this->_error_array, $extra, $options);
        } else {
            return $this->CI->response->submit_false_json($this->_error_array);
        }
    }

    /**
     * リミット・オフセットルール追加
     *
     * @access public
     * @param array $options
     *
     * @return $this
     */
    public function set_offset_rules($options = array())
    {
        $rule = 'jp_trim|';
        if (isset($options['required']) && $options['required'] === TRUE) {
            $rule .= 'required|';
        }

        $rule .= 'is_natural';

        $this->set_rules('limit', 'リミット', $rule);
        $this->set_rules('offset', 'オフセット', $rule);

        return $this;
    }

    /**
     * @param $field
     * @param string $label
     * @param mixed $rules
     * @return $this
     */
    public function set_rules($field, $label = '', $rules = array(), $errors = array())
    {
        // If an array was passed via the first parameter instead of indidual string
        // values we cycle through it and recursively call this function.
        if (is_array($field))
        {
            foreach ($field as $row)
            {
                // Houston, we have a problem...
                if ( ! isset($row['field']) OR ! isset($row['rules']))
                {
                    continue;
                }

                // If the field label wasn't passed we use the field name
                $label = ( ! isset($row['label'])) ? $row['field'] : $row['label'];

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules']);
            }
            return $this;
        }

        // No fields? Nothing to do...
        if ( ! is_string($field) OR  ! is_string($rules) OR $field == '')
        {
            return $this;
        }

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? $field : $label;

        // Is the field name an array?  We test for the existence of a bracket "[" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches))
        {
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $x = explode('[', $field);
            $indexes[] = current($x);

            for ($i = 0; $i < count($matches['0']); $i++)
            {
                if ($matches['1'][$i] != '')
                {
                    $indexes[] = $matches['1'][$i];
                }
            }

            $is_array = TRUE;
        }
        else
        {
            $indexes    = array();
            $is_array    = FALSE;
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field'                => $field,
            'label'                => $label,
            'rules'                => $rules,
            'is_array'            => $is_array,
            'keys'                => $indexes,
            'postdata'            => NULL,
            'error'                => ''
        );

        return $this;
    }

    /**
     * @param string $group
     * @return bool
     */
    function run($group = '')
    {
        $this->_append_config_rules();

        if (count($this->_field_data) == 0)
        {
            log_message('debug', "Unable to find validation rules");
            return TRUE;
        }

        // Load the language file containing error messages
        $this->CI->lang->load('form_validation');

        // Cycle through the rules for each field, match the
        // corresponding $this->params item and test for errors
        foreach ($this->_field_data as $field => $row)
        {
            // Fetch the data from the corresponding $this->params array and cache it in the _field_data array.
            // Depending on whether the field name is an array or a string will determine where we get it from.

            if ($row['is_array'] == TRUE)
            {
                $this->_field_data[$field]['postdata'] = $this->_reduce_array($this->params, $row['keys']);
            }
            else
            {
                if (isset($this->params[$field]) AND $this->params[$field] != "")
                {
                    $this->_field_data[$field]['postdata'] = $this->params[$field];
                }
            }

            $this->_execute($row, explode('|', $row['rules']), $this->_field_data[$field]['postdata']);
        }

        // Did we end up with any errors?
        $total_errors = count($this->_error_array);

        if ($total_errors > 0)
        {
            $this->_safe_form_data = TRUE;
        }

        // Now we need to re-set the POST data with the new, processed data
        $this->_reset_post_array();

        // No errors, validation passes!
        if ($total_errors == 0)
        {
            return TRUE;
        }

        // Validation fails
        return FALSE;
    }

    /**
     * @param string $group
     * @return bool
     */
    protected function _append_config_rules($group = '')
    {
        // Does the _field_data array containing the validation rules exist?
        // If not, we look to see if they were assigned via a config file
        if (count($this->_field_data) == 0)
        {
            // No validation rules?  We're done...
            if (count($this->_config_rules) == 0)
            {
                return TRUE;
            }

            // Is there a validation rule for the particular URI being accessed?
            $uri = ($group == '') ? trim($this->CI->uri->ruri_string(), '/') : $group;

            if ($uri != '' AND isset($this->_config_rules[$uri]))
            {
                $this->set_rules($this->_config_rules[$uri]);
            }
            else
            {
                $this->set_rules($this->_config_rules);
            }
        }

        return TRUE;
    }

    /**
     *
     */
    public function _reset_post_array()
    {
        foreach ($this->_field_data as $field => $row)
        {
            if ( ! is_null($row['postdata']))
            {
                if ($row['is_array'] == FALSE)
                {
                    if (isset($this->params[$row['field']]))
                    {
                        $this->params[$row['field']] = $this->prep_for_form($row['postdata']);
                    }
                }
                else
                {
                    // start with a reference
                    $post_ref =& $this->params;

                    // before we assign values, make a reference to the right POST key
                    if (count($row['keys']) == 1)
                    {
                        $post_ref =& $post_ref[current($row['keys'])];
                    }
                    else
                    {
                        foreach ($row['keys'] as $val)
                        {
                            $post_ref =& $post_ref[$val];
                        }
                    }

                    if (is_array($row['postdata']))
                    {
                        $array = array();
                        foreach ($row['postdata'] as $k => $v)
                        {
                            $array[$k] = $this->prep_for_form($v);
                        }

                        $post_ref = $array;
                    }
                    else
                    {
                        $post_ref = $this->prep_for_form($row['postdata']);
                    }
                }
            }
        }
    }

    /**
     * @param string $str
     * @param string $field
     * @return bool
     */
    public function matches($str, $field)
    {
        if ( ! isset($this->params[$field])) {
            return FALSE;
        }

        $field = $this->params[$field];

        return ($str !== $field) ? FALSE : TRUE;
    }

    /**
     * 文字列型検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function is_string($str)
    {
        return is_string($str) ? TRUE : FALSE;
    }

    /**
     * BOOL型検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function is_boolean($str)
    {
        return preg_match("/^(1|0|true|false)$/", $str) ? TRUE : FALSE;
    }

    /**
     * メールアドレス確認
     *
     * フィーチャーフォンを通すためにいい加減なメールアドレスを許容する
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function valid_vague_email($str)
    {
        if (preg_match("/^([a-zA-Z0-9\.\+_-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * 日時フォーマット検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function datetime_format($str)
    {
        if (empty($str)) {
            return TRUE;
        }

        // Only yyyy-mm-dd hh:mm:ss or yyyy/mm/dd hh:mm:ss is allowed
        if (
            !preg_match("/(^\d{4})-(\d{2})-(\d{2}) (\d{1,2}):(\d{2}):(\d{2}$)/", $str, $matches) &&
            !preg_match("/(^\d{4})\/(\d{2})\/(\d{2}) (\d{1,2}):(\d{2}):(\d{2}$)/", $str, $matches)
        ) {
            return FALSE;
        }

        if (empty($matches)) {
            return FALSE;
        }

        // Check date
        if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return FALSE;
        }

        // Check hours
        if (((int) $matches[4] < 0 || (int) $matches[4] > 23)) {
            return FALSE;
        }

        // Check minutes
        if (((int) $matches[5] < 0 || (int) $matches[5] > 59)) {
            return FALSE;
        }

        // Check seconds
        if (((int) $matches[6] < 0 || (int) $matches[6] > 59)) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 日付フォーマット検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function date_format($str)
    {
        if (empty($str)) {
            return TRUE;
        }

        // Only yyyy-mm-dd hh:mm:ss or yyyy/mm/dd hh:mm:ss is allowed
        if (
            !preg_match("/(^\d{4})-(\d{2})-(\d{2}$)/", $str, $matches) &&
            !preg_match("/(^\d{4})\/(\d{2})\/(\d{2}$)/", $str, $matches)
        ) {
            return FALSE;
        }

        if (empty($matches)) {
            return FALSE;
        }

        // Check date
        if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 現在日付以降かどうかの検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function after_current_time($str)
    {
        if (empty($str)) {
            return TRUE;
        }

        if (strtotime($str) < time()) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 現在日時未満かどうかの検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function before_current_time($str)
    {
        if (empty($str)) {
            return TRUE;
        }

        if (strtotime($str) > time()) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * URL検証
     *
     * @access public
     * @param string $str
     * @return bool
     */
    public function valid_url($str)
    {
        if (filter_var($str, FILTER_VALIDATE_URL) === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}

