<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class Campaign extends Application_controller
{

    public function __construct()
    {
        parent::__construct();

        // Add breadcrumb
        $this->_breadcrumb = [
            [
                'link' => '/campaign',
                'name' => 'キャンペーン'
            ]
        ];

    }

    /**
     * Campaign list
     *
     * @throws APP_Api_internal_call_exception
     */
    public function index()
    {
        if (!$this->current_user->has_permission('CAMPAIGN_CODE_LIST')) {
            return redirect();
        }

        $pagination = [
            'page' => (int) $this->input->get_or_default('p', 1),
            'limit' => PAGINATION_DEFAULT_LIMIT
        ];

        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['limit'];

        $filter = [
            'limit' => $pagination['limit'],
            'offset' => $pagination['offset']
        ];

        $this->session->set_userdata('campaign', $filter);
        // Call api to get list of news

        $res = $this->_api('campaign')->get_list($filter);

        $list_campaigns = [];
        if (isset($res['result'])) {
            $list_campaigns = $res['result']['items'];
            $pagination['total'] = $res['result']['total'];
        }

        $this->_render( array_merge($filter, [
            'menu_active' => 'li_campaign_list',
            'campaigns_list' => $list_campaigns,
            'pagination' => $pagination
        ]));
    }

    /**
     * Ajax get detail
     * @throws APP_Api_internal_call_exception
     */
    public function get_detail()
    {
        $id = $this->input->post('id');

        if ($id) {
            $res = $this->_internal_api('campaign', 'get_detail', [
                'id' => $id
            ]);

            if (!empty($res)) {
                $res['started_at'] = date('Y/m/d H:i', strtotime($res['started_at']));
                if($res['ended_at']){
                    $res['ended_at'] = date('Y/m/d H:i', strtotime($res['ended_at']));
                }
                return $this->_true_json($res);
            }
        }

        return $this->_false_json(APP_Response::BAD_REQUEST);
    }

    /**
     * Add news
     */
    public function add()
    {
        if (!$this->current_user->has_permission('CAMPAIGN_CODE_CREATE')) {
            return redirect('campaign');
        }

        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_campaign_list'
        ];

        if ($this->input->is_post()) {

            $data = [
                'code' => $this->input->post('code'),
                'name' => $this->input->post('name'),
                'status' => 'active',
                'started_at' => $this->input->post('started_at') ? $this->input->post('started_at').' '.$this->input->post('from_hour').':'.$this->input->post('from_minute').':00' : null
            ];

            if($this->input->post('ended_at')) {
                $data['ended_at'] = $this->input->post('ended_at').' '.$this->input->post('to_hour').':'.$this->input->post('to_minute').':00';
            }

            $res = $this->_api('campaign')->create($data);

            if(isset($res['result'])) {
                $this->_flash_message('キャンペーンコードを作成しました');
                return redirect('campaign');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();

        }

        $this->_render($view_data);
    }

    /**
     * Edit news
     * @param int $id
     * @throws APP_Api_internal_call_exception
     */
    public function edit($id = null)
    {
        if (!$this->current_user->has_permission('CAMPAIGN_CODE_UPDATE')) {
            return redirect('campaign');
        }

        $campaign = null;

        if ($id) {
            $campaign = $this->_internal_api('campaign', 'get_detail', [
                'id' => $id
            ]);
        }

        if (empty($campaign)) {
            return $this->_redirect('campaign');
        }

        $view_data = [
            'form_errors' =>[],
            'menu_active' => 'li_campaign_list'
        ];

        if ($this->input->is_post()) {

            $data = [
                'id' => $id,
                'name' => $this->input->post('name'),
                'code' => $this->input->post('code'),
                'status' => 'active',
                'started_at' => $this->input->post('from_date').' '.$this->input->post('from_hour').':'.$this->input->post('from_minute').':00'
            ];

            if($this->input->post('ended_at')) {
                $data['ended_at'] = $this->input->post('ended_at').' '.$this->input->post('to_hour').':'.$this->input->post('to_minute').':00';
            }

            $res = $this->_api('campaign')->edit($data);

            if (isset($res['result'])) {
                $this->_flash_message('キャンペーンコードを作成しました');
                return redirect('campaign');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();

        }

        if($campaign['started_at']) {
            $date_from = explode(' ', $campaign['started_at']);
            $campaign['from_date'] = $date_from[0];
            $campaign['from_hour'] = explode(':', $date_from[1])[0];
            $campaign['from_minute'] = explode(':', $date_from[1])[1];
        }

        if($campaign['ended_at']) {
            $date_to = explode(' ', $campaign['ended_at']);
            $campaign['ended_at'] = $date_to[0];
            $campaign['to_hour'] = explode(':', $date_to[1])[0];
            $campaign['to_minute'] = explode(':', $date_to[1])[1];
        }

        $view_data['campaign'] = $campaign;

        $this->_render($view_data);
    }

    /**
     * Delete news
     */
    public function delete()
    {
        // Check permission
        if (!$this->current_user->has_permission('CAMPAIGN_CODE_DELETE')) {
            return redirect('campaign');
        }

        $res = $this->_api('campaign')->delete([
            'id' => $this->input->post('id')
        ]);

        if ($res['submit'] == TRUE) {
            $this->_flash_message('キャンペーンコードを削除しました');

            return $this->_build_json(TRUE);
        }
    }
}
