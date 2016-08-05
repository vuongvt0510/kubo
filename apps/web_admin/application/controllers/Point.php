<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Point exchange class
 *
 */
class Point extends Application_controller
{

    public function __construct()
    {
        parent::__construct();

        // Add breadcrumb
        $this->_breadcrumb = [
            [
                'link' => '/point',
                'name' => 'ポイント交換'
            ]
        ];

        // Load model
        $this->load->model('point_exchange_model');
    }

    /**
     * Show all status point
     */
    public function index()
    {
        // Check permission
        if (!$this->current_user->has_permission('POINT_EXCHANGE_LIST')) {
            return redirect('/');
        }

        // Reset sort
        if ($this->input->get('mode') == 'reset') {
            $this->session->set_userdata('sort_by', 'id');
            $this->session->set_userdata('sort_position', 'desc');
            return redirect('/point');
        }

        // Add breadcrumb
        $this->_breadcrumb = [
            [
                'link' => '/point',
                'name' => 'ポイント交換'
            ]
        ];
        $view_data = [];

        // Set up pagination
        $pagination['page'] = (int) $this->input->get_or_default('p', 1, TRUE);
        $pagination['limit'] = $this->input->get_or_default('limit', Point_exchange_model::PX_LIMIT); // limit 50
        $pagination['total'] = 0;
        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        // Set status default
        $status_default = array_keys($this->point_exchange_model->get_list_status_admin());

        // Set sort default
        $sort_by = $this->input->get_or_default('sort_by', 'created_at');
        $sort_position = $this->input->get_or_default('sort_position', 'desc');

        // Set params
        $params = [
            'user_id' => (int) $this->input->get_or_default('user_id', '', TRUE),
            'login_id' => $this->input->get_or_default('login_id', '', TRUE),
            'enc_user_id' => $this->input->get_or_default('enc_user_id', '', TRUE),
            'ip_address' => $this->input->get_or_default('ip_address', '', TRUE),
            'from_date' => $this->input->get_or_default('from_date', 0),
            'to_date' => $this->input->get_or_default('to_date', '', TRUE),
            'status' => $this->input->get_or_default('status', $status_default, TRUE),
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset'],
            'sort_by' => $sort_by,
            'sort_position' => $sort_position
        ];

        // Call list waiting confirm exchange netmile
        $res = $this->_api('point_exchange')->get_list_point($params);
        $view_data['form_errors'] = isset($res['invalid_fields']) ? $res['invalid_fields'] : [];

        // change total pagination
        $pagination['total'] = isset($res['result']) ? $res['result']['total'] : 0;
        $pagination['search'] = $this->input->get();

        // Add data to view
        $view_data['list_status'] = $this->point_exchange_model->get_list_status_admin();
        $view_data['sort'] = http_build_query($this->input->get());
        $view_data['search'] = $this->input->get();
        $view_data['sort_by'] = $sort_by;
        $view_data['sort_position'] = $sort_position;
        $view_data['menu_active'] = 'li_point_exchange';
        $view_data['list_points'] = isset($res['result']) ? $res['result'] : [];
        $view_data['wait_number'] = isset($res['result']) ? $res['result']['wait_number'] : 0;
        $view_data['error_number'] = isset($res['result']) ? $res['result']['error_number'] : 0;
        $view_data['pagination'] = $pagination;
        $view_data['csv_download_string'] = '/point/download_csv?' . http_build_query($view_data['search']);

        $view_data['has_permission_proccess'] = $this->current_user->has_permission('POINT_EXCHANGE_ACCEPT')
            || $this->current_user->has_permission('POINT_EXCHANGE_REJECT');

        return $this->_render($view_data);
    }

    /**
     * Download csv for search by type
     */
    public function download_csv()
    {
        // Check permission
        if (!$this->current_user->has_permission('POINT_EXCHANGE_DOWNLOAD_CSV')) {
            return redirect('/');
        }

        $this->load->helper('download');

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Set status default
        $status_default = array_keys($this->point_exchange_model->get_list_status_admin());
        $list_status = $this->input->get_or_default('status', $status_default);

        $origin_status = $this->point_exchange_model->get_list_status_admin();
        foreach ($list_status AS $key => $value) {
            $list_status[$key] = $origin_status[$value];
        }

        // Set params
        $params = [
            'user_id' => (int) $this->input->get_or_default('user_id', ''),
            'login_id' => $this->input->get_or_default('login_id', ''),
            'enc_user_id' => $this->input->get_or_default('enc_user_id', ''),
            'ip_address' => $this->input->get_or_default('ip_address', ''),
            'from_date' => $this->input->get_or_default('from_date', 0),
            'to_date' => $this->input->get_or_default('to_date', ''),
            'status' => $this->input->get_or_default('status', $status_default),
        ];

        // Call list waiting confirm exchange netmile
        $res = $this->_api('point_exchange')->get_list_point($params);

        $data = [
            [ // Title
                'ポイント交換申請中'
            ], 
            [ // Date
                '検索期間',
                $this->input->get_or_default('from_date', 0) . '~' . $this->input->get_or_default('to_date', '')
            ], 
            [ // Status
                'ステータス',
                join("-", $list_status)
            ], 
            [ // User_id
                'ユーザーID',
                $this->input->get_or_default('user_id', '')
            ], 
            [ // Login_id
                'ログインID',
                $this->input->get_or_default('login_id', '')
            ], 
            [ // Email
                'ネットマイルID',
                $this->input->get_or_default('enc_user_id', '')
            ], 
            [ // Ip Address
                'IPアドレス',
                $this->input->get_or_default('ip_address', '')
            ], 
            [ // Total record
                '件数',
                $res['result']['total']
            ], 
            [ // Content
                '',
                'ID', // ID
                'ステータス', // Status
                'ユーザーID（親）', // User_id
                'ユーザーID（子）', // Target_id
                '家族グループ', // Group
                'ネットマイルID', // Enc_user_id netmile
                'IPアドレス', // Ip address
                'ラビポイント数', // Point
                'マイル数', // Mile
                '月額課金（現在のステータス）', // Contact
                '申請日時', // Created_at
                '更新日時', // Updated_at
            ]
        ];

        $offset = 0;
        foreach ($res['result']['items'] AS $point) {
            $data[] = [
                ++$offset,
                $point['id'],
                $point['status'],
                $point['user_id']. " (". $point['user_login_id']. ")",
                $point['target_id']. " (". $point['target_login_id']. ")",
                $point['group_id']. " (". $point['group_name']. ")",
                $point['enc_user_id'],
                $point['ip_address'],
                $point['point'],
                $point['mile'],
                $point['contract'],
                $point['created_at'],
                $point['updated_at']
            ];
        }

        // Export to csv
        force_download_csv('point_exchange.csv', $data);
    }

    /**
     * Reject exchange point
     */
    public function reject_exchange()
    {
        // Check permission
        if (!$this->current_user->has_permission('POINT_EXCHANGE_REJECT')) {
            return redirect('/');
        }

        // Check post input
        if (!$this->input->is_post() || !$this->input->is_ajax_request() ) {
            return $this->_render_404();
        }

        // Update status
        $res = $this->_api('point_exchange')->reject_exchange([
            'list_point_ids' => $this->input->post('list_point_ids')
        ]);

        if (isset($res['result'])) {
            $this->_flash_message('ステータスを更新しました');
            $res['message'] = "ステータスを更新しました";
        }

        return $this->_build_json($res);
    }

    /**
     * Accept exchange point
     */
    public function accept_exchange()
    {
        // Check permission
        if (!$this->current_user->has_permission('POINT_EXCHANGE_ACCEPT')) {
            return redirect('/');
        }

        // Check post input
        if (!$this->input->is_post() || !$this->input->is_ajax_request() ) {
            return $this->_render_404();
        }

        // Update status point
        $res = $this->_api('point_exchange')->accept_exchange([
            'list_point_ids' => $this->input->post('list_point_ids')
        ]);

        $this->_flash_message('ステータスを更新しました');

        return $this->_build_json($res);
    }
}
