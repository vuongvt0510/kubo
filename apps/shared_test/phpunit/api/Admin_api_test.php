<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/admin_builder.php";

/**
 * Test Admin_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Admin_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'admin_model',
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
        $this->CI->load->library("API/Admin_api");

        // Create object api
        $this->api =& $this->CI->admin_api;

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
     * Test function auth
     * SPEC AD_001
     * @dataProvider provider_auth
     * @group admin_auth
     */
    public function test_auth($params_api)
    {
        // Create data
        $this->admin = new Admin_builder();
        $p_admin['password'] = 'password';
        $p_admin = $this->admin->builder($p_admin);
        $res_admin = $this->admin_model->create($p_admin, ['return' => true]);
        $this->set_current_admin($res_admin->id);

        // Call API
        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_admin->name;
        }

        if (!isset($params_api['password'])) {

            $params_api['password'] = $p_admin['password'];
        }

        $res = $this->api->auth($params_api);

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
    public function provider_auth()
    {
        return [
            [['flag' => 'invalid_params', 'id'=> '', 'password' => '']],

            [['flag' => 'bad_request', 'id'=> 999999999]],

            [['flag' => 'success']],
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
        $target = $this->admin_model->find($account_id);

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
