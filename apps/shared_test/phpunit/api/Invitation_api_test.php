<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/admin_builder.php";

/**
 * Test Invitation_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Invitation_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'user_invite_code_model',
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
        $this->CI->load->library("API/Invitation_api");

        // Create object api
        $this->api =& $this->CI->invitation_api;

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
     * Test function search_users
     * SPEC I-001
     * @dataProvider provider_search_users
     * @group admin_search_users
     */
    public function test_search_users($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $p_user2['invited_from_id'] = $res_user->id;
        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);
        $this->user_model->create($p_user2);

        // Call API
        if (!isset($params_api['from_date'])) {

            $params_api['from_date'] = date('Y-m-d');
        }

        if (!isset($params_api['to_date'])) {

            $params_api['to_date'] = date('Y-m-d');
        }

        $res = $this->api->search_users($params_api);

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
    public function provider_search_users()
    {
        return [
            [['flag' => 'invalid_params', 'from_date'=> date('Y-m-d', strtotime('+30 hours')), 'to_date' => date('Y-m-d', strtotime('-30 hours'))]],

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
