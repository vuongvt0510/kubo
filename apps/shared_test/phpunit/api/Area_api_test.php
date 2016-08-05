<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_group_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_builder.php";
/**
 * Test Area_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Area_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'master_area_model',
        'master_area_pref_model',
        'master_area_pref_group_model',
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
        $this->CI->load->library("API/Area_api");

        // Create object api
        $this->api =& $this->CI->area_api;

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
     * SPEC AR_020
     * @dataProvider provider_get_list
     * @group area_get_list
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

        $this->master_area_pref_group = new Master_area_pref_builder();
        $this->master_area_pref = new Master_area_pref_builder();
        $this->master_area = new Master_area_builder();

        $p_master_area_pref_group = $this->master_area_pref_group->builder();
        $res_master_area_pref_group = $this->master_area_pref_group_model->create($p_master_area_pref_group, ['return' => true]);

        $p_master_area_pref['group_id'] = $res_master_area_pref_group->id;
        $p_master_area_pref = $this->master_area_pref->builder($p_master_area_pref);
        $res_master_area_pref = $this->master_area_pref_model->create($p_master_area_pref, ['return' => true]);

        $p_master_area['pref_id'] = $res_master_area_pref->id;
        $p_master_area = $this->master_area->builder($p_master_area);
        $this->master_area_model->create($p_master_area);

        // Call API
        if (!isset($params_api['pref_id'])) {

            $params_api['pref_id'] = $res_master_area_pref->id;;
        }

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
            [['flag' => 'invalid_params', 'pref_id'=> '']],

            [['flag' => 'invalid_params', 'pref_id'=> 'string']],

            [['flag' => 'success', ]],
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
        $this->api->set_operator($target);

        // Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}
