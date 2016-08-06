<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'controllers/modules/APP_Api_authenticatable.php';

/**
 * Application_controller
 *
 * @property APP_Config config
 * @property object agent
 * @property object output
 * @property User_record current_user
 * @property User_model user_model
 *
 * @package Controller
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */
class Application_controller extends APP_Controller
{
    use APP_Api_authenticatable;

    const LISTLIMIT = 20;

    var $students = null; // List students of parent

    /**
     * Application_controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // For maintainance
        // redirect('/under_maintainance.html', 'auto', '302');

      /*  $this->_before_filter('_find_current_user');
        $this->_before_filter('_check_grade');
        $this->_before_filter('_require_login');
        $this->_before_filter('_find_current_grade');
        $this->_before_filter('_set_the_promotion_code');*/

        // profilerを無効化
        //$this->output->enable_profiler(FALSE);
    }

    /**
     * @param array $data
     * @param string $template_path
     * @param bool|TRUE $layout
     *
     * @throws APP_Api_internal_call_exception
     * @throws APP_DB_exception_duplicate_key_entry
     * @throws APP_Exception
     * @throws Exception
     */
    public function _render($data = [], $template_path = NULL, $layout = TRUE)
    {



        parent::_render($data, $template_path, $layout);
    }

    /**
     * _meta
     *
     * Fetch meta information of HTML
     *
     * @access public
     * @param array $config
     * @return array
     */
    public function _meta($config = [])
    {

        $title = (isset($config['title']) ? $config['title'] : '無料の動画で授業の予習・復習をするならスクールTV');
        $default_description = '小学生・中学生が勉強するならスクールTV。全国の学校の教科書に対応した動画で学習できます。授業の予習・復習にぴったり。';
        $default_keywords = 'SchoolTV';

        return [
            'app_id' => $this->config->item('app_fbid'),
            'title' => $title,
            'image' => isset($config['image']) ? $config['image'] : site_url('img/opg.png'),
            'canonical' => isset($config['canonical']) ? site_url() . $config['canonical'] : null,
            'nexturl' => isset($config['nexturl']) ? site_url() . $config['nexturl'] : null,
            'prevurl' => isset($config['prevurl']) ? site_url() . $config['prevurl'] : null,
            'url' => current_url(),
            'site_name' => 'SchoolTV',
            'site_description' => '無料の動画で授業の予習・復習をするならスクールTV',
            'twitter_name' => '@SchoolTV',
            'description' => isset($config['description']) ?
                $config['description'] : $default_description,
            'type' => site_url() == current_url() ? 'website' : 'article',
            'keywords' => isset($config['keywords']) ?
                implode(',', array_merge($config['keywords'], ['SchoolTV'])) : $default_keywords,
            'copyright' => 'Copyright SchoolTV Co,. Ltd. All Rights Reserved.',

            'breadcrumb' => isset($config['breadcrumb']) ? $config['breadcrumb'] : [],
            'video_info' => isset($config['video_info']) ? $config['video_info'] : []
        ];
    }

    /**
     * Set params of list
     *
     * @return array
     */
    public function _params()
    {
        $params = $this->input->param();

        if (empty($params)) {
            $params = [];
        }

        if (empty($params['limit'])) {
            $params['limit'] = self::LISTLIMIT;
        }

        if (empty($params['offset'])) {
            $params['offset'] = 0;
        }

        if (!empty($params['p']) && is_numeric($params['p']) && $params['p'] > 0) {
            $params['offset'] = ($params['p'] - 1) * $params['limit'];
        } else {
            unset($params['p']);
        }

        return $params;
    }

    /**
     * Require login
     */
    public function _require_login()
    {
        if (!$this->current_user->is_login()) {
            $url = uri_string() !== 'login/logout' ? '?r=' . urlencode(uri_string()) : '';
            $this->_redirect('login' . $url);
        }
    }

    /**
     * Handle is parent by before filter
     */
    public function _is_parent()
    {
        if (!$this->current_user->is_login()) {
            return;
        }

        if ($this->current_user->primary_type !== 'parent') {
            $this->_redirect('profile/detail');
        }
    }

    /**
     * Is student
     */
    public function _is_student()
    {
        if (!$this->current_user->is_login()) {
            return;
        }

        if ($this->current_user->primary_type !== 'student') {
            $this->_redirect('/');
        }
    }

    /**
     * Check permission in group
     */
    public function _group_permission()
    {
        if ($this->uri->segment(1) == 'group_setting' && !in_array($this->uri->segment(2), $this->current_user->in_group)) {

            if ($this->current_user->primary_type == 'student') {
                $this->_redirect('profile/detail');
            } else {
                $this->_redirect('/');
            }
        }
        return;
    }

    /**
     * Check grade
     */
    public function _check_grade()
    {
        if (!$this->current_user->is_login()) {
            return;
        }

        if ($this->current_user->primary_type == 'student' && empty($this->current_user->grade_id) && uri_string() != 'school/search') {
            $this->_redirect('school/search');
        }
    }

    /**
     * Auto redirect to default page when user is logged in
     */
    public function _logged_in()
    {
        if ($this->current_user->is_login()) {
            $this->_redirect('profile/detail');
        }
    }

    /**
     * Find current user on user site
     */
    public function _find_current_user()
    {
        $this->load->model('user_model');
        $this->load->model('user_group_model');
        $this->load->library('session');

        $this->current_user = new APP_Anonymous_operator;
        APP_Model::set_operator($this->current_user);

        // If session has user_data
        $user_id = $this->session->userdata('user_id');
        if (empty($user_id)) {

            // Keep continue authentication
            $this->load->helper('cookie');
            $cname = null;
            $token = null;
            if (is_array($_COOKIE)) {
                foreach (array_keys($_COOKIE) AS $k) {
                    if (!preg_match('/^STV\_/', $k)) {
                        continue;
                    }

                    $cname = $k;
                    $token = $this->input->cookie($k);
                }
            }

            if ($cname && $token) {
                $res = $this->user_model->get_autologin($token);

                if ($res && ('STV_' . md5($res->token) === $cname)) {
                    $user_id = (int)$res->user_id;
                }
            }

            //ここまで来てUserIDが存在しない場合
            if (!$user_id) {
                return;
            }
        }

        // MEMO: status active:通常
        $user = $this->user_model
            ->select('user_grade_history.*')
            ->select('user_profile.*')
            ->select('user.*')
            ->where('status', 'active')
            ->join('user_profile', 'user_profile.user_id = user.id')
            ->join('user_grade_history', 'user_grade_history.id = user.current_grade', 'left')
            ->find($user_id);

        if (empty($user)) {
            return;
        }

        //TODO : find the better way for get user group and textbook
        $user->in_group = $this->user_group_model->get_user_group_id($user->id);

        $this->current_user = $user;
        APP_Model::set_operator($this->current_user);

        // Find list student if user is parent

        if($this->current_user->primary_type == 'parent') {
            $this->students = [];
            $groups = $this->_api('user_group')->get_list([
                'user_id' => $this->current_user->id,
                'group_type' => 'family'
            ]);

            if ($groups['result']) {
                foreach ($groups['result']['items'] AS $group) {

                    foreach ($group['members'] AS $member) {
                        if (!$member['email_verified']) {
                            continue;
                        }

                        if ($member['primary_type'] == 'student' && !isset($this->students[$member['user_id']])) {
                            $detail = $this->_api('user')->get_detail([
                                'id' => $member['user_id']
                            ]);

                            if (!$this->session->userdata('switch_student_id')) {
                                $this->session->set_userdata('switch_student_id', $member['user_id']);
                            }

                            if (isset($detail['result'])) {
                                $this->students[$member['user_id']] = $detail['result'];
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * Find current grade
     */
    public function _find_current_grade()
    {
        $grade_id = $this->input->cookie('current_grade_id');

        if (!$grade_id) {

            $grade_id = $this->session->userdata('current_grade_id');
        }

        $this->session->set_userdata('current_grade_id', $grade_id);
    }

    /**
     * Handle Exception
     *
     * @access public
     *
     * @param Exception $e
     *
     * @return bool 例外通知するかどうか
     * @throws APP_Api_call_exception
     * @throws Exception
     */
    public function _catch_exception(Exception $e)
    {
        // 内部APIエラー呼び出しの場合のエラーハンドリング
        if ($e instanceof APP_Api_call_exception) {

            switch ($e->getCode()) {

                // レコードが見つからない場合は 404 とする
                // 権限がない場合は、404表示とする
                // パラメータ不備の場合は、404表示とする
                case APP_Api::NOT_FOUND:
                case APP_Api::FORBIDDEN:
                case APP_Api::INVALID_PARAMS:
                    if (ENVIRONMENT != 'development') {
                        return $this->_render_404();
                    }
                    break;

                // 未認証の場合は ログイン認証処理 を呼び出すこととする
                case APP_Api::UNAUTHORIZED:
                    if (method_exists($this, '_require_login')) {
                        return $this->_require_login();
                    }
                    break;

                default:
                    break;
            }

        }

        throw $e;
    }

    /**
     * Get total members in a family
     *
     * @param int $member_id any member_id in family
     *
     * @return array
     */
    public function get_family($member_id = 0)
    {
        if (empty($member_id)) {
            return [];
        }

        $group = $this->_api('user')->get_list_groups([
            'user_id' => $member_id
        ]);

        if (empty($group['result']['groups'])) {
            return [];
        }

        $group_id = $group['result']['groups'][0]['group_id'];
        $members = $this->_api('user_group')->get_list_members([
            'group_id' => $group_id
        ]);

        return !empty($members['result']['users']) ? $members['result']['users'] : [];
    }

    /*
     * Get the child value from session
     *
     * @param int $user_id
     */
    public final function get_user_id(&$user_id = null)
    {
        if($this->current_user->primary_type == 'parent') {

            if(isset($this->students[$user_id])) {
                $this->session->set_userdata('switch_student_id', $user_id);
            }
            $user_id = ($user_id == null) ? $this->session->userdata('switch_student_id') : $user_id;

        } else {
            $user_id = ($user_id == null) ? $this->current_user->id : $user_id;
        }
    }

    /*
     * Set the promotion code for user
     *
     */
    public function _set_the_promotion_code()
    {
        // Set the promotion code for user
        if($this->input->get('i')) {
            $this->session->set_userdata('promotion_code', $this->input->get('i'));
        }
    }

}
