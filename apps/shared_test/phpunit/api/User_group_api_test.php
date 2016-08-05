<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/user_group_builder.php";
require_once dirname(__FILE__) . "/../builder/group_builder.php";
require_once dirname(__FILE__) . "/../builder/group_invite_builder.php";

/**
 * Test User_group_api
 *
 * @author dung.nguyen@interest-marketing
 */
class User_group_api_test extends CIUnit_TestCase
{

    //Set model
    protected $models = array(
        'user_group_model',
        'group_invite_model',
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
        $this->CI->load->library("API/User_group_api");

        //Create object api
        $this->api =& $this->CI->user_group_api;

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
     * Test function invite
     * SPEC UG_020
     * @dataProvider provider_invite
     * @group user_group_invite
     */
    public function test_invite($params_api)
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
        $res = $this->api->invite($params_api);

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
    public function provider_invite()
    {
        return [
            [['flag' => 'invalid_params', 'group_id' => '' ]],
            [['flag' => 'invalid_params', 'group_id' => 9999999 ]],
            [['flag' => 'invalid_params', 'email' => '' ]],
            [['flag' => 'invalid_params', 'email' => 'vaavagmail.com' ]],

            [['flag' => 'success', 'email' => 'dung.nguyen@interest-marketing.net']],

        ];
    }

    /**
     * Test function verify_invitation
     * SPEC UG_022
     * @dataProvider provider_verify_invitation
     * @group user_group_verify_invitation
     */
    public function test_verify_invitation($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_group_invite['user_id'] = $res_user->id;
        $p_group_invite['group_id'] = $res_group->id;
        $p_group_invite['token'] = 'token';
        $p_group_invite['expired_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $this->group_invite  = new Group_invite_builder();
        $p_group_invite = $this->group_invite->builder($p_group_invite);

        $res_group_invite = $this->group_invite_model->create($p_group_invite, ['return' => true]);

        if (!isset($params_api['token'])) {

            $params_api['token'] = $res_group_invite->token;

        }

        if (!isset($params_api['group_id'])) {

            $params_api['group_id'] = $res_group->id;

        }

        if (!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user->id;

        }

        if (!isset($params_api['role'])) {

            $params_api['role'] = 'owner';

        }

        //Call API
        $res = $this->api->verify_invitation($params_api);

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
    public function provider_verify_invitation()
    {
        return [
            [['flag' => 'invalid_params', 'token' => '', 'group_id' =>  '', 'user_id' => '', 'role' => ''  ]],
            [['flag' => 'invalid_params', 'token' => 9999999999 ]],
            [['flag' => 'invalid_params', 'group_id' => 9999999999 ]],
            [['flag' => 'invalid_params', 'role' => 'vaavagmail.com' ]],

            [['flag' => 'bad_request', 'user_id' => 99999999999 ]],

            [['flag' => 'success', ]],

        ];
    }

    /**
     * Test function check_invitation
     * SPEC UG_021
     * @dataProvider provider_check_invitation
     * @group user_group_check_invitation
     */
    public function test_check_invitation($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_group_invite['user_id'] = $res_user->id;
        $p_group_invite['group_id'] = $res_group->id;
        $p_group_invite['token'] = 'token';
        $p_group_invite['expired_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $this->group_invite  = new Group_invite_builder();
        $p_group_invite = $this->group_invite->builder($p_group_invite);

        $res_group_invite = $this->group_invite_model->create($p_group_invite, ['return' => true]);

        if (!isset($params_api['token'])) {

            $params_api['token'] = $res_group_invite->token;

        }

        //Call API
        $res = $this->api->check_invitation($params_api);

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
    public function provider_check_invitation()
    {
        return [
            [['flag' => 'invalid_params', 'token' => '']],
            [['flag' => 'invalid_params', 'token' => 'wrong' ]],

            [['flag' => 'success', ]],

        ];
    }

    /**
     * Test function add_member
     * SPEC UG_050
     * @dataProvider provider_add_member
     * @group user_group_add_member
     */
    public function test_add_member($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);
        $res_user2 = $this->user_model->create($p_user2, ['return' => true]);

        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        if (!isset($params_api['group_id'])) {

            $params_api['group_id'] = $res_group->id;

        }
        if (!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user2->id;

        }
        if (!isset($params_api['role'])) {

            $params_api['role'] = 'owner';
        }

        //Call API
        $res = $this->api->add_member($params_api);

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
    public function provider_add_member()
    {
        return [
            [['flag' => 'invalid_params', 'group_id' => '', 'user_id' => '', 'role' => '' ]],
            [['flag' => 'invalid_params', 'group_id' => 999999999999999999999999999999 ]],

            [['flag' => 'invalid_params', 'role' => 'not role' ]],

            [['flag' => 'bad_request', 'user_id' => 999999999999999999999999999999 ]],

            [['flag' => 'success', ]],

        ];
    }

    /**
     * Test function remove_member
     * SPEC UG_051
     * @dataProvider provider_remove_member
     * @group user_group_remove_member
     */
    public function test_remove_member($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);
        $this->user_model->create($p_user2);

        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_user_group['user_id'] = $res_user->id;
        $p_user_group['group_id'] = $res_group->id;
        $this->user_group  = new User_group_builder();
        $p_user_group = $this->user_group->builder($p_user_group);
        $this->user_group_model->create($p_user_group);

        if (!isset($params_api['group_id'])) {

            $params_api['group_id'] = $res_group->id;

        }
        if (!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user->id;

        }

        //Call API
        $res = $this->api->remove_member($params_api);

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
    public function provider_remove_member()
    {
        return [
            [['flag' => 'invalid_params', 'group_id' => '', 'user_id' => '',]],
            [['flag' => 'invalid_params', 'group_id' => 999999999999999999999999999999 ]],

            [['flag' => 'bad_request', 'user_id' => 999999999999999999999999999999 ]],

            [['flag' => 'success', ]],

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
