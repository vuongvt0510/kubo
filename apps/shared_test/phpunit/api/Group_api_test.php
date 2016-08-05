<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/group_builder.php";


/**
 * Test group api
 *
 * @author dung.nguyen@interest-marketing
 */
class Group_api_test extends CIUnit_TestCase
{
    //Set model
    protected $models = array(
        'user_account_model',
        'group_model',
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

        //Load API
        $this->CI->load->library("API/Group_api");

        //Create object api
        $this->api =& $this->CI->group_api;

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
     * Test function create
     * SPEC UG-030
     * @dataProvider provider_create
     * @group group_create
     */
    public function test_create($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Call API
        $res = $this->api->create($params_api);

        //Check data
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
            [['flag' => 'invalid_params', 'primary_type'=> 'primary_type']],
            [['flag' => 'invalid_params', 'primary_type'=> '']],

            [['flag' => 'success', 'primary_type'=> 'family']],
            [['flag' => 'success', 'primary_type'=> 'friend']],
        ];

    }

    /**
     * Test function update
     * SPEC UG-060
     * @dataProvider provider_update
     * @group group_update
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

        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        if (!isset($params_api['group_id'])) {
            $params_api['group_id'] = $res_group->id;

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
            [['flag' => 'invalid_params', 'group_id'=> '']],
            [['flag' => 'invalid_params', 'group_id'=> 99999999999]],

            [['flag' => 'success', 'name'=> 'c43349834h34y8534534%#$%#$RÈ%#$%#$R##$%#family']],
            [['flag' => 'success', 'name'=>  99999999999]],
        ];

    }

    /**
     * @group admin
     * @return mixed
     */
    protected function set_current_user($account_id)
    {
        //Get data account default in database
        $target = $this->user_account_model->available(TRUE)->find($account_id);

        //Load library and assigned value
      //  $this->CI->load->library('API/Textbook_api');
        $this->api->set_operator($target);

        //Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}
