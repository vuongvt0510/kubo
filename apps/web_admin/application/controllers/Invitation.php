<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Invitation extends Application_controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Search user by campaign code Spec IC10
     */
    public function search_by_promotional_code()
    {
        $view_data = [
            'menu_active' => 'li_searchcampaign'
        ];

        $this->_render($view_data);

    }

    /**
     * Download csv for search by type
     * @param string $type (user|invite_code|promotion)
     */
    public function download_csv($type = '')
    {
        $this->load->helper('download');

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        switch ($type) {
            case 'promotion':

                $res = $this->_api('promotion')->search_users([
                    'type' => 'forceclub',
                    'from_date' => $this->input->get_or_default('from_date', ''),
                    'to_date' => $this->input->get_or_default('to_date', ''),
                    'code' => $this->input->get_or_default('code', '')
                ]);

                $data = [
                    [
                        'キャンペーン実績'
                    ], [
                        '検索期間',
                        $this->input->get_or_default('from_date', '') . '~' . $this->input->get_or_default('to_date', '')
                    ], [
                        '件数',
                        $res['result']['total']
                    ], [
                        '',
                        'ID',
                        '名前',
                        '属性',
                        '登録日時',
                        'キャンペーンコード'
                    ]
                ];

                $offset = 0;
                foreach ($res['result']['users'] AS $user) {
                    $data[] = [
                        ++$offset,
                        $user['login_id'],
                        $user['nickname'],
                        $user['primary_type'] == 'student' ? '子' : '保護者',
                        $user['created_at'],
                        $user['promotion_code']
                    ];
                }

                // Export to csv
                force_download_csv('user_promotion.csv', $data);

                break;

            case 'user_invite_id':

                $res = $this->_api('invitation')->search_users([
                    'from_date' => $this->input->get_or_default('from_date',''),
                    'to_date' => $this->input->get_or_default('to_date',''),
                    'login_id' => ltrim($this->input->get_or_default('login_id', '')),
                ]);
                $data = [
                    [
                        'キャンペーン実績'
                    ], [
                        '検索期間',
                        $this->input->get_or_default('from_date', '') . '~' . $this->input->get_or_default('to_date', '')
                    ], [
                        '件数',
                        $res['result']['total']
                    ], [
                        '',
                        'ID',
                        '名前',
                        '属性',
                        '登録日時',
                    ]
                ];
                $offset = 0;
                foreach ($res['result']['users'] AS $user) {
                    $data[] = [
                        ++$offset,
                        $user['login_id'],
                        $user['nickname'],
                        $user['primary_type'] == 'student' ? '子' : '保護者',
                        $user['created_at'],

                    ];
                }
                // Export to csv
                force_download_csv('invited_user.csv', $data);

                break;
        }
    }

}
