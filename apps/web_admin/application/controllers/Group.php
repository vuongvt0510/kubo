<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Group extends Application_controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Search group Spec G10
     */
    public function search()
    {
        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_group_search'
        ];

        if ($this->input->is_get()) {

            $pagination = [
                'page' => (int) $this->input->get_or_default('p', 1),
                'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
            ];

            $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

            $res = $this->_api('group')->search_list([
                'from_date' => $this->input->get_or_default('from_date', ''),
                'to_date' => $this->input->get_or_default('to_date', ''),
                'primary_type' => $this->input->get_or_default('primary_type', ''),
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset']
            ]);

            if (isset($res['result'])) {
                $view_data['list_groups'] = $res['result']['items'];
                $pagination['total'] = $res['result']['total'];
            } else {
                $view_data['form_errors'] = $res['invalid_fields'];
                $view_data['list_groups'] = [];
            }
            $view_data['search'] = $pagination['search'] = $this->input->get();
            $view_data['pagination'] = $pagination;

            $view_data['csv_download_string'] = '/group/download_csv?' . http_build_query($view_data['search']);
        }

        $this->_breadcrumb = [
            [
                'link' => '/group/search',
                'name' => '家族グループ／チーム'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * Download csv for search
     */
    public function download_csv()
    {
        $this->load->helper('download');

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        $res = $this->_api('group')->search_list([
            'from_date' => $this->input->get_or_default('from_date', ''),
            'to_date' => $this->input->get_or_default('to_date', ''),
            'primary_type' => $this->input->get_or_default('primary_type', '')
        ]);

        $data = [
            [
                '家族グループ／チーム'
            ], [
                '検索期間',
                $this->input->get_or_default('from_date', '') . '~' . $this->input->get_or_default('to_date', '')
            ], [
                'グループ種別',
                $this->input->get('primary_type') == 'family' ? '家族グループ' :  ($this->input->get('primary_type') == 'friend' ? 'チーム' : '')
            ],
            [
                '件数',
                $res['result']['total']
            ], [
                '',
                'グループID',
                'グループ作成日時',
                'グループ種別',
                '家族グループ名／チーム名',
            ]
        ];

        $offset = 0;
        foreach ($res['result']['items'] AS $group) {
            $data[] = [
                ++$offset,
                $group['id'],
                $group['created_at'],
                $group['primary_type'] == 'family' ? '家族グループ' : 'チーム',
                $group['name']
            ];
        }

        // Export to csv
        force_download_csv('export_group_'.business_date('Ymd').'.csv', $data);
    }

    public function add_user()
    {
        $this->_render([
            'menu_active' => 'li_user'
        ]);
    }

    public function view_user()
    {
        $this->_render([
            'menu_active' => 'li_user'
        ]);
    }

    /**
     * Create group for user
     * @param int $user_id
     */
    public function create($user_id)
    {
        if (!$this->current_user->has_permission('USER_UPDATE_GROUP')) {
            return redirect();
        }

        // get user detail
        $user = $this->_api('user')->get_detail([
            'id'=> $user_id
        ]);

        if (!isset($user['result']) || empty($user['result']['id'])) {
            return redirect('user/detail/'.$user_id);
        }

        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_user',
            'user_detail' => $user['result']
        ];

        if ($this->input->is_post()) {

            $view_data['post'] = $this->input->post();

            $res = $this->_api('group')->create($this->input->post());

            if (isset($res['invalid_fields'])) {
                $view_data['form_errors'] = $res['invalid_fields'];
            }
            else {

                $res = $this->_api('user_group')->add_member([
                    'group_id' => $res['result']['group_id'],
                    'user_id' => $user_id,
                    'role' => 'owner'
                ]);

                if ($res['submit'] == TRUE) {
                    $this->_flash_message('新しいグループを作成しました');
                    redirect('/user/detail/' . $user_id);
                    return;
                } else {
                    $view_data['errmsg'] = $res['errmsg'];
                }
            }
        }

        $this->_breadcrumb = [
            [
                'link' => '/group/search',
                'name' => '家族グループ／チーム'
            ]
        ];

        $this->_render($view_data);
    }

    public function create_details()
    {
        $this->_render([
            'menu_active' => 'li_user'
        ]);
    }
}