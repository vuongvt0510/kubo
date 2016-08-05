<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Admin_api
 *
 * @property Admin_model admin_model
 * @property APP_Input input
 *
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Admin_api extends Base_api
{

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Admin_api_validator';

    /**
     * Admin login API Spec AD-001
     *
     * @param array $params
     * @internal param $id login id
     * @internal param $password password to login
     * @internal param $auto_login check if auto login needed
     *
     * @return array
     */
    public function auth($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('id', 'ログインID', 'required|max_length[255]');
        $v->set_rules('password', 'パスワード', 'required|max_length[255]');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load library
        $this->load->library('session');
        // Load model
        $this->load->model('admin_model');

        // Load user information
        $admin = $this->admin_model
            ->available(TRUE)
            ->authenticate($params['id'], $params['password']);

        // Return error when record does not exist
        if (!$admin) {
            return $this->false_json(self::BAD_REQUEST, 'IDかパスワードが正しくありません');
        }

        // Set admin session
        $this->session->set_userdata('admin_id', $admin->id);

        // Save cookie
        if (isset($params['auto_login'])) {
            // Create auto_login record
            $res = $this->admin_model->set_autologin($admin->id);

            if ($res) {
                // Save cookie
                $this->input->set_cookie([
                    'name' => 'STVA_' . md5($res->token),
                    'value' => $res->token,
                    'expire' => 60 * 60 * 24 * 365,
                    'path' => '/',
                    'secure' => TRUE
                ]);
            }
        }

        // Return
        return $this->true_json($this->build_responses($admin));
    }

    /**
     * User logout API Spec AD-002
     *
     * @return array
     */
    public function logout()
    {
        // Only administrator can logout admin site
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::UNAUTHORIZED);
        }

        // Load model
        $this->load->model('admin_model');
        $this->load->helper('cookie');
        $this->load->library('session');

        // Delete cookie and token
        if (is_array($_COOKIE)) {
            foreach (array_keys($_COOKIE)  AS $k) {
                if (!preg_match('/^STVA\_/', $k)) {
                    continue;
                }

                delete_cookie($k);
                $this->admin_model->delete_autologin($this->input->cookie($k));
            }
        }

        // Delete session
        $this->session->sess_destroy();

        return $this->true_json();
    }

    /**
     * Admin get list
     *
     * @param array $params
     * @internal param $limit limit
     * @internal param $offset offset
     *
     * @return array
     */

    public function get_list($params = [])
    {
        //validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('ADMINS_LIST');
        $v->set_rules('limit','取得件数','integer');
        $v->set_rules('offset','取得開始','integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can create
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Set default for params
        if (!isset($params['sort_by']) || !in_array($params['sort_by'], ['id','created_at'])) {
            $params['sort_by'] = 'id';
        }

        // Set default for param sort position
        if (!isset($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'desc';
        }

        $this->_set_default($params);
        //load model

        $this->load->model('admin_model');

        $res = $this->admin_model
                ->calc_found_rows()
                ->select('admin.id ,admin.name , admin.login_id , admin.status , admin.created_at')
                ->select('role.id as role_id, role.name as role_name')
                ->join('role','role.id = admin.role_id')
                ->order_by('admin.'.$params['sort_by'],$params['sort_position'])
                ->limit($params['limit'], $params['offset'])
                ->all();

        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->admin_model->found_rows()
        ]);

    }

    /*
     *admin create acount
     *  @internal param string role_id
     * * @internal param string name
     * @internal param string login_id
     * @internal param string $password
     * @internal param string $confirm_password
     */
    public function create($params = [])
    {
        //validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('ADMIN_CREATE_ACOUNT');
        $v->set_rules('role_id',' 取得件数 ','integer');
        $v->set_rules('name',' ユーザー名 ','required|valid_name_exist');
        $v->set_rules('login_id','ログインID','required|valid_login_id_exist');
        $v->set_rules('password','現在のパスワード','required|min_length[8]');
        $v->set_rules('confirm_password', '新しいパスワード（確認）', 'required|valid_confirm_password[' . $params['password'] . ']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        unset($params['confirm_password']);

        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        $params['password'] = $this->admin_model->encrypt_password($params['password']);

        // Load model
        $this->load->model('admin_model');
        $res = $this->admin_model->create($params);

        // Return
        return $this->true_json($this->build_responses($res));
    }


    /**
     * Admin change password
     *
     * @param array $params
     * @internal param string $password
     * @internal param string $confirm_password
     * @return array
     */
    public function change_password($params = [])
    {
        //validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('password','現在のパスワード','required|min_length[8]');
        $v->set_rules('confirm_password', '新しいパスワード（確認）', 'required|valid_confirm_password[' . $params['password'] . ']');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $this->load->model('admin_model');

        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Update password
        $this->admin_model->update($this->operator()->id, [
            'password' => $this->admin_model->encrypt_password($params['password'])
        ]);

        // Return
        return $this->true_json();
    }
    /*
     * get detail
     * @params int admin id
     *
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);

        $v->require_login();
        $v->set_rules('id', 'ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }
        // Load model
        $this->load->model('admin_model');

        // If operator isn't admin, he can not get user detail who isn't available
        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Get user info
        $admin = $this->admin_model
                    ->select('admin.id ,admin.name , admin.login_id , admin.role_id, admin.status , admin.created_at')
                    ->where('admin.id', $params['id'])
                    ->first();
        // Return error if user does not exist
        if (!$admin) {
            return $this->false_json(self::USER_NOT_FOUND);
        }
            // Return
        return  $this->true_json($this->build_responses($admin));
    }

    /**
     * Edit admin API
     *
     * @param array $params
     * @internal param $id of
     * @internal param $role_id
     * @internal param $name
     * @return array
     */
    public function update($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('ADMIN_EDIT_ACCOUNT');
        $v->set_rules('id', 'ID', 'integer','required');
        $v->set_rules('role_id', ' 取得件数 ', 'integer');
        $v->set_rules('name', ' ユーザー名 ', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Only administrator can edit
        if (!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('admin_model');

        $res = $this->admin_model->update($params['id'], [
            'role_id' => $params['role_id'],
            'name' => $params['name']
        ], [
            'return' => TRUE
        ]);


        // Return
        return  $this->true_json($this->build_responses($res));
    }

    /**
     * Admin delete
     * @internal param $id of admin
     * @return array
     */
    public function delete($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->require_permissions('ADMIN_DELETE_ACCOUNT');
        $v->set_rules('id', 'ニュースID', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('admin_model');
        $this->load->helper('string_helper');

        $admin = $this->admin_model->find($params['id']);

        if (!$admin) {
            return $this->false_json(self::NOT_FOUND);
        }

        $this->admin_model->update($admin->id, [
            'login_id' => $admin->login_id.':deleted'.random_string('alnum', 15),
            'name' => $admin->name.':deleted'.random_string('alnum', 15),
            'status' => 'suspended',
            'deleted_at' => business_date('Y-m-d H:i:s'),
            'deleted_by' => 'admin:'.$this->operator()->id
        ]);

        return $this->true_json();
    }


    /**
     * Admin get role
     * @internal param $id of role
     * @return array
     */
    public function get_role($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('id', '取得件数', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(!$this->operator()->is_administrator()) {
            return $this->false_json(self::FORBIDDEN);
        }

        // Load model
        $this->load->model('role_model');

        $res = $this->role_model
            ->select('role.id,role.name')
            ->order_by('role.id','asc')
            ->all();

        return $this->build_responses($res);

    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    protected function build_response($res, $options = []){

        if(!$res) {
            return [];
        }

        $admin = [
            'id' => isset($res->id) ? (int) $res->id : null,
            'name'=> isset($res->name) ? $res->name : null,
            'login_id' => isset($res->login_id) ? $res->login_id : null,
            'status' => isset($res->status) ? $res->status : null,
            'role_id' => isset($res->role_id) ? (int) $res->role_id : null,
            'role_name' => isset($res->role_name) ?  $res->role_name : null,
            'created_at' => isset($res->created_at) ? $res->created_at : null

        ];

        return $admin;
    }

}
/**
 * Class Admin_api_validator
 *
 * @property Admin_api $base
 */
class Admin_api_validator extends Base_api_validation
{

    /**
     * Validate type
     *
     * @param String $confirm_password
     * @param String $password
     *
     * @return bool
     */
    function valid_confirm_password($confirm_password, $password)
    {

        if ($confirm_password != $password) {
            $this->set_message('valid_confirm_password', '新しいパスワード（確認）が違います');
            return FALSE;
        }

        return TRUE;
    }


    function valid_login_id_exist($login_id)
    {

        // Load model
        $this->base->load->model('admin_model');

        $admin = $this->base->admin_model->where([
            'login_id' => $login_id
        ])->first();

        if ($admin) {
            $this->set_message('valid_login_id_exist', 'このログインIDはすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

    function valid_name_exist($name)
    {
        $this->base->load->model('admin_model');

        $admin = $this->base->admin_model->where([
            'name' => $name,
        ])->first();

        if ($admin) {
            $this->set_message('valid_name_exist', 'このユーザー名はすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

    function valid_id_exist($id)
    {
        $this->base->load->model('admin_model');
        $admin = $this->base->admin_model->where([
           'id' => $id
        ])->first();
        if (!$admin) {
            $this->set_message('valid_id_exist', 'このログインIDはすでに使用されています');
            return FALSE;
        }

        return TRUE;
    }

}
