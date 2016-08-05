<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class User extends Application_controller
{

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Admin search users Spec US10
     */
    public function search()
    {
        if (!$this->current_user->has_permission('USER_LIST')) {
            return redirect();
        }

        $view_data = [
            'menu_active' => 'li_user'
        ];

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        // Get list users
        $users = $this->_api('user')->search_list(array_merge($this->input->get(), [
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset'],
            'sort_by' => 'id',
            'sort_position' => 'desc'
        ]), [
            'with_deleted' => TRUE,
            'point_remain_admin' => TRUE
        ]);

        if (isset($users['result'])) {
            $view_data['users'] = $users['result']['items'];
            $pagination['total'] = $users['result']['total'];
        }

        $view_data['search'] = $pagination['search'] = $this->input->get();
        $view_data['pagination'] = $pagination;
        $view_data['csv_download_string'] = '/user/download_csv?' . http_build_query($this->input->get());

        $this->_breadcrumb = [
            [
                'link' => '/user/search',
                'name' => 'ユーザー検索'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * User detail page Spec UE10
     *
     * @param int $user_id
     */
    public function detail($user_id = null)
    {
        // get user detail
        $user = $this->_api('user')->get_detail([
            'id' => $user_id,
            'get_all' => TRUE
        ]);

        if (!isset($user['result'])) {
            return redirect('user/search');
        }

        // Get user inviter
        if (($user['result']['invited_from_id'])) {
            $inviter = $this->_api('user')->get_detail([
                'id' => $user['result']['invited_from_id'],
                'get_all' => TRUE
            ]);
        }

        // Get promotion code
        $code = $this->_api('promotion')->get_detail([
            'user_id' => $user_id
        ]);

        // Get contract
        $contract = $this->_api('user_contract')->get_detail([
            'user_id' => $user_id
        ]);

        // Get current point and total point
        $point = $this->_api('user_rabipoint')->get_detail([
            'user_id' => $user_id
        ]);

        // get list group family
        $family = $this->_api('user_group')->get_list([
            'user_id' => $user_id,
            'group_type' => 'family'
        ]);

        // get list group friend
        $friend = $this->_api('user_group')->get_list([
            'user_id' => $user_id,
            'group_type' => 'friend'
        ]);

        // Get list prefectures
        $prefecture = $this->_api('prefecture')->get_list();

        // Get list grades
        $grades = $this->_api('grade')->get_list();

        $view_data = [
            'menu_active' => 'li_user',
            'promotion_code' => isset($code['result']['promotion_code']) ? $code['result']['promotion_code'] : null,
            'campaign_code' => isset($code['result']['campaign_code']) ? $code['result']['campaign_code'] : null,
            'user_id' => $user_id,
            'user_detail' => $user['result'],
            'prefectures' => $prefecture['result']['items'],
            'family' => isset($family['result']) ? $family['result']['items'] : [],
            'friend' => isset($friend['result']) ? $friend['result']['items'] : [],
            'contract' => isset($contract['result']) ? $contract['result']['status'] : null,
            'current_point' => isset($point['result']) ? $point['result']['point'] : 0,
            'total_point' => isset($point['result']) ? $point['result']['total_point'] : 0,
            'inviter' => isset($inviter['result']) ? $inviter['result'] : null,
            'grades' => $grades['result']['items']
        ];

        // Create session to redirect page after add user to group
        if (!empty($this->session->userdata('add_user_group'))) {
            $this->session->unset_userdata('add_user_group');
        }
        $this->session->set_userdata('add_user_group', $user_id);

        $this->_breadcrumb = [
            [
                'link' => '/user/detail',
                'name' => 'ユーザーの詳細'
            ]
        ];

        return $this->_render($view_data);
    }


    /**
     * Update user UE20
     *
     * @param int $user_id
     */
    public function edit($user_id = null)
    {
        if (!$this->current_user->has_permission('USER_UPDATE') || !$this->current_user->has_permission('USER_UPDATE_PROMOTION')) {
            return redirect('user/search');
        }

        $view_data = [
            'menu_active' => 'li_user'
        ];

        $view_data['user_id'] = $user_id;

        // Get info user
        $res = $this->_api('user')->get_detail([
            'id' => $user_id,
            'get_all' => TRUE
        ]);

        // Get promotion code
        $code = $this->_api('promotion')->get_detail([
            'user_id' => $user_id
        ]);

        $view_data['user'] = $res['result'];
        $view_data['promotion_code'] = isset($code['result']['promotion_code']) ? $code['result']['promotion_code'] : null;
        $view_data['campaign_code'] = isset($code['result']['campaign_code']) ? $code['result']['campaign_code'] : null;

        if ($this->input->is_post()) {

            // explode promotion_code and join 
            $promotion = join("",explode("-", $this->input->post('promotion_code')));

            // update user
            $res = $this->_api('user')->update([
                'id' => $user_id,
                'nickname' => $this->input->post('nickname') ? $this->input->post('nickname') : null,
                'sex' => $this->input->post('gender') == 'male' ? 0 : 1,
                'promotion_code' => $promotion ? $promotion : null,
                'campaign_code' => $this->input->post('campaign_code') ? $this->input->post('campaign_code') : null
            ], ['admin_edit' => TRUE]);

            $errmsg = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

            // update email
            $update_email = $this->_api('user')->update_email([
                'id' => $user_id,
                'email' => $this->input->post('email')
            ]);

            if (isset($update_email['invalid_fields'])) {
                $errmsg = array_merge($errmsg, $update_email['invalid_fields']);
            }

            if (empty($errmsg)) {
                $this->_flash_message('編集内容を保存しました');
                return redirect('user/detail/'.$user_id);
            }

            $view_data['form_errors'] = $errmsg;
            $view_data['user'] = array_merge($view_data['user'],$this->input->post());
            $view_data['promotion_code'] = $this->input->post('promotion_code');
            $view_data['campaign_code'] = $this->input->post('campaign_code');
        }

        $this->_breadcrumb = [
            [
                'link' => '/user/edit',
                'name' => 'ユーザー編集'
            ]
        ];

        $this->_render($view_data);
    }

    /**
     * Delete user Spec UD10
     */
    public function delete()
    {
        if (!$this->current_user->has_permission('USER_DELETE')) {
            return redirect('user/search');
        }

        if ($this->input->is_post()) {
            $this->_api('user')->delete([
                'id' => (int) $this->input->post('user_id')
            ]);
        }

        $this->_flash_message('ユーザーの退会処理が完了しました');
        return redirect('/user/search');
    }

    /**
     * Download csv for search
     */
    public function download_csv()
    {
        $this->load->helper('download');

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Get list users
        $res = $this->_api('user')->search_list(array_merge($this->input->get(), [
            'sort_by' => 'id',
            'sort_position' => 'asc',
            'csv' => TRUE
        ]), ['with_deleted' => TRUE]);


        if ($this->input->get('parent')) {
            $status = '保護者,';
        }
        if ($this->input->get('student')) {
            $status = $status.' 子ども,';
        }
        if ($this->input->get('contract')) {
            $status = $status.' 月額会員';
        }
        if (!isset($status)) {
            $status = '-';
        }

        $data = [
            [ // Title
                'ユーザーデータ'
            ], [ // Date
                '検索期間',
                $this->input->get_or_default('from_date', 0) . '~' . $this->input->get_or_default('to_date', '')
            ], [ // User_id
                'ユーザーID',
                $this->input->get_or_default('user_id', '')
            ], [ // Nickname
                'ニックネーム',
                $this->input->get_or_default('nickname', '')
            ], [ // Login_id
                'ログインID',
                $this->input->get_or_default('login_id', '')
            ], [ // Email
                'ネットマイルID',
                $this->input->get_or_default('email', '')
            ], [ // Promotion code
                '紹介コード',
                $this->input->get_or_default('code', '')
            ], [ // User inviter ID
                '紹介者ユーザーID',
                $this->input->get_or_default('user_invitation_id', '')
            ], [ // User inviter login ID
                '紹介者ログインID',
                $this->input->get_or_default('user_invitation_login_id', '')
            ], [ // User primary type parent
                '対象アカウント',
                $status
            ], [ // Total record
                '件数',
                $res['result']['total']
            ], [ // Content
                '',
                'ユーザーID', // User_id
                'ログインID', // User_id
                'ニックネーム', // Nickname
                'ユーザー種別', // Primary_type
                '子どもの学年', //user_grade
                '登録日時 ', // created_at
                'ステータス', // Status
                '退会日時', // Date withdraw
                '招待コード', // Invitation code
                '招待者ID', // Inviter’s ID
                '紹介者ログインID', // inviter_login_id
                'スクールTV Plusステータス', //
                'メールアドレス', // email
                '保持コイン数', // current_coin
                '保持ラビポイント', // current_rabipoint
                'ラビポイント累計 ', // total_rabipoint
            ]
        ];

        $offset = 0;
        foreach ($res['result']['items'] AS $user) {

            if ($user['status'] == 'active') {
                $status = '会員';
            } elseif ($user['status'] == 'suspended') {
                $status = '退会';
            } else {
                $status = '未承認';
            }

            if ($user['primary_type'] == 'student') {
                if ($user['contract_status'] == 'not_contract') {
                    $contract = '解約済み';
                } elseif ($user['contract_status'] == 'pending') {
                    $contract = '更新停止';
                } elseif ($user['contract_status'] == 'canceling') {
                    $contract = '解約中';
                } elseif ($user['contract_status'] == 'under_contract') {
                    $contract = '契約中';
                } else {
                    $contract = '未契約';
                }
            } else {
                $contract = '-';
            }

            $data[] = [
                ++$offset,
                $user['id'],
                $user['login_id'],
                $user['nickname'],
                $user['primary_type'] == 'student' ? '子ども' : '保護者',
                $user['user_grade'],
                $user['registered_at'],
                $status ? $status : '',
                $user['deleted_at'],
                $user['invitation_code'],
                $user['inviter_id'],
                $user['inviter_login_id'],
                $contract,
                $user['email'],
                $user['current_coin'],
                $user['current_rabipoint'],
                $user['total_rabipoint']
            ];
        }

        // Export to csv
        force_download_csv('export_users_'.business_date('Ymd').'.csv', $data);
    }

}
