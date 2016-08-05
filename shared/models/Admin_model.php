<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';
require_once SHAREDPATH . "core/APP_Operator.php";

/**
 * Class Admin_model
 *
 * @property Admin_login_token_model admin_login_token_model
 * @property APP_Loader load
 * @property APP_Input input
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Admin_model extends APP_Paranoid_model
{
    public $database_name = DB_MAIN;
    public $table_name = 'admin';
    public $primary_key = 'id';

    public $record_class = 'Admin_record';

    /**
     * Check login admin
     *
     * @access public
     *
     * @param string $login_id
     * @param string $password
     * @param array $options
     *
     * @return object|bool
     */
    public function authenticate($login_id, $password, $options = array())
    {
        return $this->find_by([
            'login_id' => $login_id,
            'password' => $this->encrypt_password($password)
        ], $options);
    }

    /**
     * fetch admin is active|inactive
     * @param bool $available
     *
     * @return Admin_model
     */
    public function available($available = TRUE)
    {
        return $this->where('admin.status', ($available) ? 'active' : 'inactive');
    }

    /**
     * Save auto login information
     *
     * @param int $id
     * @return object
     */
    public function set_autologin($id)
    {
        // Load helper
        $this->load->helper('string_helper');

        // Load model
        $this->load->model('admin_login_token_model');

        $res = $this->admin_login_token_model->create([
            'admin_id' => $id,
            'token' => random_string('alnum', 32),
            'user_agent' => $this->input->user_agent(),
            'remote_ip' => $this->input->ip_address()
        ], [
            'mode' => 'replace',
            'return' => TRUE
        ]);

        return $res;
    }

    /**
     * Encrypt password
     *
     * @access public
     * @param string $password
     * @return string
     */
    public function encrypt_password($password)
    {
        return base64_encode(hash_hmac('sha256', $password, 'xE98#beD4fLd2qP3', TRUE));
    }

    /**
     * Get auto login info
     * @param string $token
     *
     * @return Object
     */
    public function get_autologin($token)
    {
        $this->load->model('admin_login_token_model');
        return $this->admin_login_token_model
            ->where('token', $token)
            ->first();
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function delete_autologin($token)
    {
        $this->load->model('admin_login_token_model');
        $this->admin_login_token_model
            ->where('token', $token)
            ->real_destroy_all();

        return TRUE;
    }
}

/**
 * Admin record
 *
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Admin_record implements APP_Operator
{

    /**
     * @var null ID
     */
    public $id = null;

    /**
     * @var null Name
     */
    public $name = null;

    /**
     * @var null Role of admin id
     */
    public $role_id = null;

    /**
     * @var null
     */
    public $_permissions = null;

    /**
     * Permission person
     *
     * @access public
     * @return array|null
     */
    public function permission()
    {
        if (!is_null($this->_permissions)) {
            return $this->_permissions;
        }

        $CI =& get_instance();
        $CI->load->model('role_permission_model');

        $this->_permissions = $CI->role_permission_model
            ->select('permission.identifier')
            ->join('permission', 'role_permission.permission_id = permission.id')
            ->where('role_id', $this->role_id)
            ->all();

        $this->_permissions = array_map(function($r) { return $r->identifier; }, $this->_permissions);

        return $this->_permissions;
    }

    /**
     * Check person has permission
     *
     * @access public
     * @param string $permission
     * @return bool
     */
    public function has_permission($permission = '')
    {
        if (in_array('ALL', $this->permission())) {
            return TRUE;
        }

        return in_array($permission, $this->permission());
    }

    /**
     * 該当の権限をすべて持っているか
     *
     * @access public
     * @return bool
     */
    public function has_all_permissions()
    {
        $permissions = [];
        foreach (func_get_args() as $p) {
            $p = is_array($p) ? $p : [$p];
            foreach ($p as $q) {
                $permissions[] = $q;
            }
        }

        foreach ($permissions as $p) {
            if (FALSE === $this->has_permission($p)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * 該当の権限を一部持っているか
     *
     * @access public
     * @return bool
     */
    public function has_either_permissions()
    {
        $permissions = [];
        foreach (func_get_args() as $p) {
            $p = is_array($p) ? $p : [$p];
            foreach ($p as $q) {
                $permissions[] = $q;
            }
        }

        foreach ($permissions as $p) {
            if (TRUE === $this->has_permission($p)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Check role person
     *
     * @access public
     * @return bool
     */
    public function has_role()
    {
        $roles = func_get_args();

        if (is_null($this->_roles)) {
            $this->_roles = [(int) $this->role_id];
        }

        if (in_array(ROLE_ADMINISTRATOR, $this->_roles)) {
            return TRUE;
        }

        $result = array_intersect($this->_roles, $roles);

        return count($result) === count($roles);
    }

    /**
     * 該当のロールを一部持っているか
     *
     * @access public
     * @return bool
     */
    public function has_either_roles()
    {
        $roles = [];
        foreach (func_get_args() as $role) {
            $role = is_array($role) ? $role : [$role];
            foreach ($role as $r) {
                $roles[] = $r;
            }
        }

        foreach ($roles as $role) {
            if (TRUE === $this->has_role($role)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * 未ログインユーザーかどうか
     *
     * @access public
     * @return bool
     */
    public function is_anonymous()
    {
        return FALSE;
    }

    /**
     * ログインしているかどうか
     *
     * @access public
     * @return bool
     */
    public function is_login()
    {
        return TRUE;
    }

    /**
     * 管理者かどうか
     *
     * @access public
     * @return bool
     */
    public function is_administrator()
    {
        return TRUE;
    }

    /**
     * 操作者IDを返す
     *
     * @access public
     * @return mixed
     */
    public function _operator_id()
    {
        return $this->id;
    }

    /**
     * 操作者名を返す
     *
     * @access public
     * @return string
     */
    public function _operator_name()
    {
        return $this->name;
    }

    /**
     * 操作者識別子を返す
     *
     * @access public
     * @return string
     */
    public function _operator_identifier()
    {
        return "admin:" . $this->id;
    }

    /**
     * Set user operator by
     *
     * @return string
     */
    public function _operated_by()
    {
        return "admin:" . $this->id;
    }
}

