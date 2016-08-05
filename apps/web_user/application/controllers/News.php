<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * News Controller
 *
 * @author Nghia Tran <nghia.tran@interest-marketing.net>
 */
class News extends Application_controller
{
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['index', 'detail']
        ]);
    }

    /**
     * Index Spec NW-10
     */
    public function index()
    {
        $view_data = [];
        $filter = [];

        $filter['public_status'] = 'available';

        $filter['sort_by'] = 'started_at';

        $filter['status'] = (!$this->current_user->is_login()) ? 'public' : 'all';

        $filter['sort_by'] = 'started_at';

        $filter['sort_position'] = 'desc';

        $res = $this->_api('news')->get_list($filter);

        if (isset($res['result'])) {
            $view_data['list_news'] = $res['result']['items'];
        }

        $this->_render($view_data);
    }

    /**
     * Index Spec NW-20
     *
     * @param int $id
     */
    public function detail($id)
    {
        $view_data = [];

        $res = $this->_api('news')->get_detail(['id' => $id]);

        if (isset($res['result'])) {
            $res['result']['content'] = nl2br(htmlspecialchars($res['result']['content']));
            $view_data['news'] = $res['result'];
        }

        // Update this news is read
        $this->_api('user_news')->update([
            'news_id' => $id,
            'user_id' => $this->current_user->id,
            'is_read' => 1
        ]);

        $this->_render($view_data);
    }
}