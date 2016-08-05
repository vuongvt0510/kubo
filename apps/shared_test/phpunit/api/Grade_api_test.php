<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";
/**
 * Test Grade_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Grade_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'master_grade_model',
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
        $this->CI->load->library("API/Grade_api");

        // Create object api
        $this->api =& $this->CI->grade_api;

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
     * SPEC PRE_020
     * @group Grade_get_list
     */
    public function test_get_list($params_api)
    {
        // Create data
        $this->user = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->master_grade = new Master_grade_builder();
        $p_master_grade = $this->master_grade->builder();
        $this->master_grade_model->create($p_master_grade);

        // Call API
        $res = $this->api->get_list($params_api);

        $this->assertTrue($res['submit']);
        $this->assertTrue($res['success']);

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
        $this->api->set_operator($target);

        // Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}
