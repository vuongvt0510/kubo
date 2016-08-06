<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * トップ コントローラ
 *
 */
class News extends Application_controller
{

    public function __construct()
    {
        parent::__construct();

        // Add breadcrumb
        $this->_breadcrumb = [
            [
                'link' => '/news',
                'name' => 'お知らせ一覧'
            ]
        ];
    }

    /**
     * Admin news manager
     *
     * @throws APP_Api_internal_call_exception
     */
    public function index()
    {

    }

    /**
     * Ajax get detail
     * @throws APP_Api_internal_call_exception
     */
    public function get_detail()
    {
        $id = $this->input->post('id');

        if ($id) {
            $res = $this->_internal_api('news', 'get_detail', [
                'id' => $id
            ]);

            if (!empty($res)) {
                $res['content'] = nl2br(htmlspecialchars($res['content']));
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
        if (!$this->current_user->has_permission('NEWS_CREATE')) {
            return redirect('news');
        }

        $view_data = [
            'form_errors' => [],
            'menu_active' => 'li_news_create'
        ];

        if ($this->input->is_post()) {

            $data = [
                'title' => $this->input->post('title'),
                'content' => $this->input->post('content'),
                'status' => 'public',
                'started_at' => $this->input->post('started_at') ? $this->input->post('started_at').' '.$this->input->post('from_hour').':'.$this->input->post('from_minute').':00' : null
            ];

            if($this->input->post('ended_at')) {
                $data['ended_at'] = $this->input->post('ended_at').' '.$this->input->post('to_hour').':'.$this->input->post('to_minute').':00';
            }

            $res = $this->_api('news')->create($data);

            if(isset($res['result'])) {
                $this->_flash_message('お知らせを作成しました');
                return redirect('news');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();

        }

        // Add breadcrumb
        $this->_breadcrumb[] = [
            'name' => '新規作成'
        ];

        $this->_render($view_data);
    }

    /**
     * Edit news
     * @param int $id
     * @throws APP_Api_internal_call_exception
     */
    public function edit($id = null)
    {
        if (!$this->current_user->has_permission('NEWS_UPDATE')) {
            return redirect('news');
        }

        $news = null;

        if ($id) {
            $news = $this->_internal_api('news', 'get_detail', [
                'id' => $id
            ]);
        }

        if (empty($news)) {
            return $this->_redirect('news');
        }

        $view_data = [
            'form_errors' =>[]
        ];

        if ($this->input->is_post()) {

            $data = [
                'id' => $id,
                'title' => $this->input->post('title'),
                'content' => $this->input->post('content'),
                'status' => 'public',
                'started_at' => $this->input->post('started_at').' '.$this->input->post('from_hour').':'.$this->input->post('from_minute').':00'
            ];

            if($this->input->post('ended_at')) {
                $data['ended_at'] = $this->input->post('ended_at').' '.$this->input->post('to_hour').':'.$this->input->post('to_minute').':00';
            }

            $res = $this->_api('news')->edit($data);

            if (isset($res['result'])) {
                $this->_flash_message('お知らせを編集しました');
                return redirect('news');
            }

            $view_data['form_errors'] = $res['invalid_fields'];
            $view_data['post'] = $this->input->post();

        }

        if($news['started_at']) {
            $date_from = explode(' ', $news['started_at']);
            $news['started_at'] = $date_from[0];
            $news['from_hour'] = explode(':', $date_from[1])[0];
            $news['from_minute'] = explode(':', $date_from[1])[1];
        }

        if($news['ended_at']) {
            $date_to = explode(' ', $news['ended_at']);
            $news['ended_at'] = $date_to[0];
            $news['to_hour'] = explode(':', $date_to[1])[0];
            $news['to_minute'] = explode(':', $date_to[1])[1];
        }

        // Add breadcrumb
        $this->_breadcrumb[] = [
            'name' => $news['title']
        ];

        $view_data['news'] = $news;

        $this->_render($view_data);
    }

    /**
     * Delete news
     */
    public function delete()
    {
        // Check permission
        if (!$this->current_user->has_permission('NEWS_DELETE')) {
            return redirect('news');
        }

        // Check permission
        $new_ids = $this->input->post('news_id');

        foreach ($new_ids AS $new_id) {
            $this->_api('news')->delete([
                'id' => $new_id
            ]);
        }

        $this->_flash_message('お知らせを削除しました');

        redirect('news');
    }
}
