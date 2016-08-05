<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Coin controller
 * Manage coin purchase history and coin usage history
 *
 */
class Coin extends Application_controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Search purchasing coin history Spec CO10
     */
    public function search_purchase()
    {
        if (!$this->current_user->has_permission('SEARCH_COIN_PURCHASING')) {
            return redirect('news');
        }

        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_coin_purchase'
        ];

        if ($this->input->is_get()) {

            $pagination = [
                'page' => (int) $this->input->get_or_default('p', 1),
                'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
            ];

            $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

            $params = array_merge($this->input->get(), [
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset'],
                'sort_by' => 'created_at',
                'sort_position' => 'desc'
            ]);

            $res = $this->_api('coin')->search_purchase($params);

            if (isset($res['result'])) {
                $view_data['list_purchases'] = $res['result']['items'];
                $pagination['total'] = $res['result']['total'];
            } else {
                $view_data['form_errors'] = $res['invalid_fields'];
                $view_data['list_purchases'] = [];
            }

            $view_data['search'] = $pagination['search'] = $this->input->get();
            $view_data['pagination'] = $pagination;

            $view_data['csv_download_string'] = '/coin/download_csv/purchase?' . http_build_query($view_data['search']);
        }

        $this->_breadcrumb = [
            [
                'link' => '/coin/search_purchase',
                'name' => 'コイン購入履歴'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * Search usage coin history Spec CO20
     */
    public function search_usage()
    {
        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_coin_usage'
        ];

        if ($this->input->is_get()) {

            $pagination = [
                'page' => (int) $this->input->get_or_default('p', 1),
                'limit' => (int) $this->input->get_or_default('limit', PAGINATION_DEFAULT_LIMIT)
            ];

            $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

            $params = array_merge($this->input->get(), [
                'limit' => $pagination['limit'],
                'offset' => $pagination['offset'],
                'sort_by' => 'created_at',
                'sort_position' => 'desc'
            ]);

            $res = $this->_api('coin')->search_buying($params);

            if (isset($res['result'])) {
                $view_data['items'] = $res['result']['items'];
                $pagination['total'] = $res['result']['total'];
            } else {
                $view_data['form_errors'] = $res['invalid_fields'];
                $view_data['items'] = [];
            }

            $view_data['search'] = $pagination['search'] = $this->input->get();
            $view_data['pagination'] = $pagination;

            $view_data['csv_download_string'] = '/coin/download_csv/usage?' . http_build_query($view_data['search']);
        }

        $this->_breadcrumb = [
            [
                'link' => '/coin/search_purchase',
                'name' => 'コイン利用履歴'
            ]
        ];

        return $this->_render($view_data);
    }

    /**
     * Download csv for search
     * @param string $type (purchase|usage)
     */
    public function download_csv($type = '')
    {
        $this->load->helper('download');

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        switch ($type) {
            case 'purchase':

                $res = $this->_api('coin')->search_purchase($this->input->get());

                if (!isset($res['result'])) {
                    return redirect('coin/search_purchase');
                }

                $data = [
                    [
                        'コイン購入履歴'
                    ], [
                        '検索期間',
                        $this->input->get_or_default('from_date', '') . '~' . $this->input->get_or_default('to_date', '')
                    ], [
                        'コイン購入ID:',
                        $this->input->get('primary_type', ''),
                        'ユーザーID:',
                        $this->input->get('user_id', ''),
                        'ログインID:',
                        $this->input->get('login_id', ''),
                        'ニックネーム:',
                        $this->input->get('nickname', ''),
                        'GMOペイメントID:',
                        $this->input->get('gmo_user_id', ''),
                    ],
                    [
                        '件数',
                        $res['result']['total']
                    ], [
                        'コイン購入ID',
                        '購入日',
                        '購入方法',
                        'GMOペイメントID',
                        'コイン数',
                        '金額',
                        '保護者ID',
                        '保護者ログインID',
                        '保護者紹介コード',
                        '保護者の紹介者ID',
                        '保護者の紹介者ログインID',
                        '子どもID',
                        '子どもログインID',
                        '子ども紹介コード',
                        '子どもの紹介者ID',
                        '子どもの紹介者ログインID',
                    ]
                ];

                foreach ($res['result']['items'] AS $item) {
                    $data[] = [
                        $item['id'],
                        $item['created_at'],
                        'クレジットカード',
                        $item['order_id'],
                        $item['coin'],
                        '¥' . $item['amount'],
                        $item['user']['id'],
                        $item['user']['login_id'],
                        $item['user']['invitation_code'],
                        $item['user']['inviter_id'],
                        $item['user']['inviter_login_id'],
                        $item['target_user']['id'],
                        $item['target_user']['login_id'],
                        $item['target_user']['invitation_code'],
                        $item['target_user']['inviter_id'],
                        $item['target_user']['inviter_login_id'],
                    ];
                }

                // Export to csv
                force_download_csv('export_coin_purchase_history_'.business_date('Ymd').'.csv', $data);

                break;
            case 'usage':

                $res = $this->_api('coin')->search_buying($this->input->get());

                if (!isset($res['result'])) {
                    return redirect('coin/search_purchase');
                }

                $data = [
                    [
                        'コイン利用履歴'
                    ], [
                        '検索期間',
                        $this->input->get_or_default('from_date', '') . '~' . $this->input->get_or_default('to_date', '')
                    ], [
                        'コイン利用ID:',
                        $this->input->get('primary_type', ''),
                        'ユーザーID:',
                        $this->input->get('user_id', ''),
                        'ログインID:',
                        $this->input->get('login_id', ''),
                        'ニックネーム:',
                        $this->input->get('nickname', '')
                    ],
                    [
                        '件数',
                        $res['result']['total']
                    ], [
                        'コイン利用ID',
                        '利用日',
                        '子どもID',
                        '子どもログインID',
                        '子ども紹介コード',
                        '子ども紹介者ID',
                        '子ども紹介者ログインID',
                        'コイン利用数',
                        '購入ドリルID',
                        '購入ドリル名'
                    ]
                ];

                $offset = 0;
                foreach ($res['result']['items'] AS $item) {
                    $data[] = [
                        $item['id'],
                        $item['created_at'],
                        $item['user']['id'],
                        $item['user']['login_id'],
                        $item['user']['invitation_code'],
                        $item['user']['inviter_id'],
                        $item['user']['inviter_login_id'],
                        $item['coin'],
                        $item['deck']['id'],
                        $item['deck']['name'],
                    ];
                }

                // Export to csv
                force_download_csv('export_coin_usage_history_'.business_date('Ymd').'.csv', $data);

                break;
        }
    }
}