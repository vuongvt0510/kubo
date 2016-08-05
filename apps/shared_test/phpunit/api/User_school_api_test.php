<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/group_builder.php";
require_once dirname(__FILE__) . "/../builder/master_postalcode_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_builder.php";
require_once dirname(__FILE__) . "/../builder/master_school_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_group_builder.php";


/**
 * Test User_school api
 *
 * @author dung.nguyen@interest-marketing
 */
class User_school_api_test extends CIUnit_TestCase
{
    // Set model
    protected $models = array(
        'master_school_model',
        'master_area_model',
        'master_area_pref_model',
        'master_area_pref_group_model',
        'master_postalcode_model',
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
        $this->CI->load->library("API/User_school_api");

        // Create object api
        $this->api =& $this->CI->user_school_api;

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
     * Test function update
     * SPEC US-040
     * @dataProvider provider_update
     * @group user_school_update
     */
    public function test_update($params_api)
    {
        // Create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->master_postalcode = new Master_postalcode_builder();
        $this->master_area_pref = new Master_area_pref_builder();
        $this->master_area = new Master_area_builder();
        $this->master_school = new Master_school_builder();
        $this->master_area_pref_group = new Master_area_pref_builder();

        $p_master_area_pref_group = $this->master_area_pref_group->builder();
        $res_master_area_pref_group = $this->master_area_pref_group_model->create($p_master_area_pref_group, ['return' => true]);

        $p_master_postalcode = $this->master_postalcode->builder();
        $p_master_postalcode['postalcode'] = $this->master_school_model->sanitize_word($p_master_postalcode['postalcode']);
        $res_master_postalcode = $this->master_postalcode_model->create($p_master_postalcode, ['return' => true]);

        $p_master_area_pref['group_id'] = $res_master_area_pref_group->id;

        $p_master_area_pref = $this->master_area_pref->builder($p_master_area_pref);
        $res_master_area_pref = $this->master_area_pref_model->create($p_master_area_pref, ['return' => true]);

        $p_master_area['pref_id'] = $res_master_area_pref->id;
        $p_master_area = $this->master_area->builder($p_master_area);
        $res_master_area = $this->master_area_model->create($p_master_area, ['return' => true]);


        $p_master_school['area_id'] = $res_master_area->id;
        $p_master_school['postalcode_id'] = $res_master_postalcode->id;
        $p_master_school = $this->master_school->builder($p_master_school);
        $res_master_school = $this->master_school_model->create($p_master_school, ['return' => true]);

        if (!isset($params_api['school_id'])) {

            $params_api['school_id'] = $res_master_school->id;

        }

        // Call API
        $res = $this->api->update($params_api);

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
    public function provider_update()
    {
        return [
            [['flag' => 'invalid_params', 'school_id'=> '']],
            [['flag' => 'invalid_params', 'school_id'=> 9999999999]],

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
