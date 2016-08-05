<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/news_builder.php";
require_once dirname(__FILE__) . "/../builder/admin_builder.php";

/**
 * Test News_api
 *
 * @author dung.nguyen@interest-marketing
 */
class News_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'admin_model',
        'news_model',
        'user_account_model',
        'user_model',
    );

    public function setUp()
    {
        foreach ($this->models as $m) {
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }


        $this->tearDown();
        parent::setUp();

        // Load API
        $this->CI->load->library("API/News_api");

        // Create object api
        $this->api =& $this->CI->news_api;

    }

    public function tearDown()
    {
        foreach ($this->models as $m) {
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->empty_table();
        }
        parent::tearDown();
    }

    /**
     * Test function get_list
     * @dataProvider provider_get_list
     * SPEC PRE_020
     * @group News_get_list
     */
    public function test_get_list($params_api)
    {

        // Create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $p_news['status'] = 'public';

        if(isset($params_api['status']) && $params_api['status'] != 'all')
        {
            $p_news['status'] = $params_api['status'];
        }

        $this->news = new News_builder();
        $p_news = $this->news->builder($p_news);
        $this->news_model->create($p_news);

        // Call API
        $res = $this->api->get_list($params_api);

        // Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertTrue($res['success']);
            $this->assertFalse($res['submit']);

        } else if ($params_api['flag'] == 'bad_request') {

            $this->assertFalse($res['submit']);
            $this->assertFalse($res['success']);

        } else if ($params_api['flag'] == 'success') {

            $this->assertTrue($res['submit']);
            $this->assertTrue($res['success']);
        }

    }

    /*
    * TEST for all case
    */
    public function provider_get_list()
    {
        return [
            [['flag' => 'invalid_params', 'status'=> '', 'public_status' => '' ]],

            [['flag' => 'success', 'public_status' => 'available', 'status' => 'private']],
            [['flag' => 'success', 'public_status' => 'available', 'status' => 'public']],
        ];

    }

    /**
     * Test function get_detail
     * SPEC UNW_011
     * @dataProvider provider_get_detail
     * @group News_get_detail
     */
    public function test_get_detail($params_api)
    {
        // Create data
        if(isset($params_api['user']))
        {
            $this->user  = new User_builder();
            $p_user['id'] = 'tester';
            $p_user['type'] = 'student';
            $p_user = $this->user->builder($p_user);
            $res_user = $this->user_model->create($p_user, ['return' => true]);
            $this->set_current_user($res_user->id);
            unset($params_api['user']);
        }

        if(isset($params_api['admin']))
        {
            $this->admin  = new Admin_builder();
            $p_admin['password'] = 'password';
            $p_admin = $this->admin->builder($p_admin);
            $res_admin = $this->admin_model->create($p_admin, ['return' => true]);
            $this->set_current_admin($res_admin->id);
            unset($params_api['admin']);
        }

        $this->news = new News_builder();

        $p_news['status'] = 'public';
        if (isset($params_api['status']) && $params_api['status'] == 'private') {

            $p_news['started_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $p_news['ended_at'] = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        }

        $p_news = $this->news->builder($p_news);
        $res_news = $this->news_model->create($p_news, ['return' => true]);

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_news->id;
        }

        // Call API
        $res = $this->api->get_detail($params_api);

        // Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertTrue($res['success']);
            $this->assertFalse($res['submit']);

        } else if ($params_api['flag'] == 'bad_request') {

            $this->assertFalse($res['submit']);
            $this->assertFalse($res['success']);

        } else if ($params_api['flag'] == 'success') {

            $this->assertTrue($res['submit']);
            $this->assertTrue($res['success']);
        }

    }
    /*
     * TEST for all case
     */
    public function provider_get_detail()
    {
        return [
            [['flag' => 'success', 'admin' => '', 'status'=> 'private']],

            [['flag' => 'invalid_params', 'user' => '', 'id'=> '']],

            [['flag' => 'bad_request', 'user' => '', 'id'=> 99999999999]],

            [['flag' => 'bad_request', 'user' => '', 'status'=> 'private']],

            [['flag' => 'success', 'user' => '' ]],
        ];

    }

    /**
     * Test function delete
     * SPEC N_100
     * @dataProvider provider_delete
     * @group News_delete
     */
    public function test_delete($params_api)
    {
        // Create data
        if(isset($params_api['user']))
        {
            $this->user  = new User_builder();
            $p_user['id'] = 'tester';
            $p_user['type'] = 'student';
            $p_user = $this->user->builder($p_user);
            $res_user = $this->user_model->create($p_user, ['return' => true]);
            $this->set_current_user($res_user->id);
            unset($params_api['user']);
        }

        if(isset($params_api['admin']))
        {
            $this->admin  = new Admin_builder();
            $p_admin['password'] = 'password';
            $p_admin = $this->admin->builder($p_admin);
            $res_admin = $this->admin_model->create($p_admin, ['return' => true]);
            $this->set_current_admin($res_admin->id);
            unset($params_api['admin']);
        }

        $this->news = new News_builder();
        $p_news = $this->news->builder();
        $res_news = $this->news_model->create($p_news, ['return' => true]);

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_news->id;
        }

        // Call API
        $res = $this->api->delete($params_api);

        // Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertTrue($res['success']);
            $this->assertFalse($res['submit']);

        } else if ($params_api['flag'] == 'bad_request') {

            $this->assertFalse($res['submit']);
            $this->assertFalse($res['success']);

        } else if ($params_api['flag'] == 'success') {

            $this->assertTrue($res['submit']);
            $this->assertTrue($res['success']);
        }

    }
    /*
     * TEST for all case
     */
    public function provider_delete()
    {
        return [
            [['flag' => 'bad_request', 'user' => '' ]],

            [['flag' => 'invalid_params', 'admin' => '', 'id'=> '']],

            [['flag' => 'bad_request', 'admin' => '', 'id'=> 99999999999]],

            [['flag' => 'success', 'admin' => '' ]],
        ];

    }

    /**
     * Test function create
     * SPEC N_110
     * @dataProvider provider_create
     * @group News_create
     */
    public function test_create($params_api)
    {
        // Create data
        if(isset($params_api['user']))
        {
            $this->user  = new User_builder();
            $p_user['id'] = 'tester';
            $p_user['type'] = 'student';
            $p_user = $this->user->builder($p_user);
            $res_user = $this->user_model->create($p_user, ['return' => true]);
            $this->set_current_user($res_user->id);
            unset($params_api['user']);
        }

        if(isset($params_api['admin']))
        {
            $this->admin  = new Admin_builder();
            $p_admin['password'] = 'password';
            $p_admin = $this->admin->builder($p_admin);
            $res_admin = $this->admin_model->create($p_admin, ['return' => true]);
            $this->set_current_admin($res_admin->id);
            unset($params_api['admin']);
        }

        $this->news = new News_builder();
        $p_news = $this->news->builder();

        if (!isset($params_api['title'])) {

            $params_api['title'] = $p_news['title'];
        }

        if (!isset($params_api['content'])) {

            $params_api['content'] = $p_news['content'];
        }

        if (!isset($params_api['started_at'])) {

            $params_api['started_at'] = $p_news['started_at'];
        }

        if (!isset($params_api['ended_at'])) {

            $params_api['ended_at'] = $p_news['ended_at'];
        }

        if (!isset($params_api['status'])) {

            $params_api['status'] = $p_news['status'];
        }

        $tmp = $params_api['flag'];
        unset($params_api['flag']);

        // Call API
        $res = $this->api->create($params_api);

        $params_api['flag'] = $tmp;
        // Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertTrue($res['success']);
            $this->assertFalse($res['submit']);

        } else if ($params_api['flag'] == 'bad_request') {

            $this->assertFalse($res['submit']);
            $this->assertFalse($res['success']);

        } else if ($params_api['flag'] == 'success') {

            $this->assertTrue($res['submit']);
            $this->assertTrue($res['success']);
        }

    }
    /*
     * TEST for all case
     */
    public function provider_create()
    {
        return [
            [['flag' => 'bad_request', 'user' => '' ]],

            [['flag' => 'invalid_params', 'admin' => '', 'title'=> '', 'content' => '', 'started_at' => '', 'ended_at' => '', 'status' => '',]],
            [['flag' => 'invalid_params', 'admin' => '', 'started_at' => date('Y-m-d H:i:s', strtotime('+60 minutes')) ]],
            [['flag' => 'invalid_params', 'admin' => '', 'status'=> 'status']],

            [['flag' => 'success', 'admin' => '' ]],
        ];

    }

    /**
     * Test function edit
     * SPEC N-111
     * @dataProvider provider_edit
     * @group News_edit
     */
    public function test_edit($params_api)
    {
        // Create data
        if(isset($params_api['user']))
        {
            $this->user  = new User_builder();
            $p_user['id'] = 'tester';
            $p_user['type'] = 'student';
            $p_user = $this->user->builder($p_user);
            $res_user = $this->user_model->create($p_user, ['return' => true]);
            $this->set_current_user($res_user->id);
            unset($params_api['user']);
        }

        if(isset($params_api['admin']))
        {
            $this->admin  = new Admin_builder();
            $p_admin['password'] = 'password';
            $p_admin = $this->admin->builder($p_admin);
            $res_admin = $this->admin_model->create($p_admin, ['return' => true]);
            $this->set_current_admin($res_admin->id);
            unset($params_api['admin']);
        }

        $this->news = new News_builder();
        $p_news = $this->news->builder();
        $res_news = $this->news_model->create($p_news, ['return' => true]);

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_news->id;
        }

        if (!isset($params_api['content'])) {

            $params_api['content'] = $res_news->content;
        }

        if (!isset($params_api['title'])) {

            $params_api['title'] = $res_news->title;
        }

        if (!isset($params_api['started_at'])) {

            $params_api['started_at'] = $res_news->started_at;
        }

        if (!isset($params_api['ended_at'])) {

            $params_api['ended_at'] = $res_news->ended_at;
        }

        if (!isset($params_api['status'])) {

            $params_api['status'] = $res_news->status;
        }

        $tmp = $params_api['flag'];
        unset($params_api['flag']);

        // Call API
        $res = $this->api->edit($params_api);

        $params_api['flag'] = $tmp;
        // Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertTrue($res['success']);
            $this->assertFalse($res['submit']);

        } else if ($params_api['flag'] == 'bad_request') {

            $this->assertFalse($res['submit']);
            $this->assertFalse($res['success']);

        } else if ($params_api['flag'] == 'success') {

            $this->assertTrue($res['submit']);
            $this->assertTrue($res['success']);
        }

    }

    /*
    * TEST for all case
    */
    public function provider_edit()
    {
        return [
            [['flag' => 'invalid_params', 'admin' => '', 'id'=> '', 'content' => '', 'title' => '', 'started_at' => '', 'ended_at' => '', 'status' => '']],
            [['flag' => 'invalid_params', 'admin' => '', 'ended_at' => date('Y-m-d H:i:s', strtotime('-60 minutes'))]],
            [['flag' => 'invalid_params', 'admin' => '', 'status' => 'status' ]],

            [['flag' => 'bad_request', 'admin' => '', 'id'=> 99999999999]],
            [['flag' => 'bad_request', 'user' => '', ]],

            [['flag' => 'success', 'admin' => '' ]],
        ];

    }

    /**
     * @group admin
     * @return mixed
     */
    protected function set_current_user($account_id)
    {
        // Get data account default in database
        $target = $this->user_account_model->available(TRUE)->find($account_id);

        // Load library and assigned value
        // $this->CI->load->library('API/Textbook_api');
        $this->api->current_user = $target;
        $this->api->set_operator($target);

        // Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

    /**
     * @group admin
     * @return mixed
     */
    protected function set_current_admin($account_id)
    {
        // Get data account default in database
        $target = $this->admin_model->available(TRUE)->find($account_id);

        // Load library and assigned value
        // $this->CI->load->library('API/Textbook_api');
        $this->api->current_user = $target;
        $this->api->set_operator($target);

        // Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }
}
