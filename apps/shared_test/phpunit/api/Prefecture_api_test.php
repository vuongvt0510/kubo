<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_group_builder.php";

/**
 * Test Prefecture_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Prefecture_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'master_area_pref_group_model',
        'master_area_pref_model',
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
        $this->CI->load->library("API/Prefecture_api");

        // Create object api
        $this->api =& $this->CI->prefecture_api;

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
     * @group prefecture_get_list
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

        $this->master_area_pref = new Master_area_pref_builder();
        $this->master_area_pref_group = new Master_area_pref_builder();

        $p_master_area_pref_group = $this->master_area_pref_group->builder();
        $res_master_area_pref_group = $this->master_area_pref_group_model->create($p_master_area_pref_group, ['return' => true]);

        $p_master_area_pref['group_id'] = $res_master_area_pref_group->id;
        $p_master_area_pref = $this->master_area_pref->builder($p_master_area_pref);
        $this->master_area_pref_model->create($p_master_area_pref, ['return' => true]);

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
