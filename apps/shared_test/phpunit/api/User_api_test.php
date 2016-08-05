<?php

require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/user_profile_builder.php";
require_once dirname(__FILE__) . "/../builder/user_email_verify_builder.php";
require_once dirname(__FILE__) . "/../builder/user_password_verify_builder.php";
require_once dirname(__FILE__) . "/../builder/group_builder.php";
require_once dirname(__FILE__) . "/../builder/user_group_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";


/**
 * Test user api
 *
 * @author dung.nguyen@interest-marketing
 */
class User_api_test extends CIUnit_TestCase
{
    //Set model
    protected $models = array(
        'user_email_verify_model',
        'user_password_verify_model',
        'user_group_model',
        'user_promotion_code_model',
        'user_grade_history_model',
        'master_grade_model',
        'user_account_model',
        'user_profile_model',
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
        $this->CI->load->library("API/User_api");

        //Create object api
        $this->api =& $this->CI->user_api;

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
     * Test function LOGIN
     * SPEC U_001
     * @dataProvider provider_login
     * @group user_login
     */
    public function test_login($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $this->user_model->create($p_user);

        if($params_api['flag'] == 'success') {

            $params_api['password'] = $p_user['password'];
            $params_api['id'] = $p_user['id'];
        }

        //Call API
        $res = $this->api->auth($params_api);

        //Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertFalse($res['submit']);
            $this->assertTrue($res['success']);

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
    public function provider_login()
    {
        return [
            [['flag' => 'invalid_params', 'password' => '', 'id' => 'tester']],
            [['flag' => 'invalid_params', 'password' => 'password', 'id' => '']],
            [['flag' => 'invalid_params', 'password' => '', 'id' => '']],
            [['flag' => 'invalid_params', 'password' => 'password', 'id' => generate_unique_key(256)]],

            [['flag' => 'bad_request', 'password' => 'pass', 'id' => 'test test']],
            [['flag' => 'bad_request', 'password' => 'test test', 'id' => 'test test']],
            [['flag' => 'bad_request', 'password' => 11111, 'id' => 'tester']],
            [['flag' => 'bad_request', 'password' => ',.22.2', 'id' => '23...2.']],
            [['flag' => 'bad_request', 'password' => 'password', 'id' => generate_unique_key(254)]],

            [['flag' => 'success']],
        ];
    }

    /**
     * Test function register
     * @group user_r
     * SPEC U_010
     * @dataProvider provider_register
     */
    public function test_register($params_api)
    {
        // Param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        // Create data user in database
        $this->user_model->create($p_user);

        $this->master_grade  = new Master_grade_builder();
        $p_master_grade = $this->master_grade->builder();

        // Create data user in database
        $res_master_grade = $this->master_grade_model->create($p_master_grade, ['return' => true]);

        //for case the same id, id have existed
        if (!isset($params_api['id'])) {
            $params_api['id'] = $p_user['id'];
        }

        if (isset($params_api['grade_id'])) {
            $params_api['grade_id'] = $res_master_grade->id;
        }

        // Call API
        $res = $this->api->register($params_api);

         //Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertFalse($res['submit']);
            $this->assertTrue($res['success']);

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
    public function provider_register()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '', 'id' => 'test test', 'password' => 'password', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'test+02@interest-marketing.net', 'id' => '', 'password' => 'password', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'test+02@interest-marketing.net', 'id' => 'test test', 'password' => '', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'test+02@interest-marketing.net', 'id' => 'test test', 'password' => 'password', 'promotion_code'=> '', 'type' => '', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'formatvalid-interest-marketing.net', 'id' => 'test test', 'password' => 'password', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'test+02@interest-marketing.net', 'password' => 'password', 'invite_code'=> '', 'type' => 'student', 'grade_id' => 'test' ]],
            [['flag' => 'invalid_params', 'email' => 'test+02@interest-marketing.net', 'id' => 'test test', 'password' => 'password', 'promotion_code'=> '', 'type' => 'other-type', 'grade_id' => 'test' ]],

            [['flag' => 'success', 'email' => 'test+02@interest-marketing.net', 'id' => 'testDUNG', 'password' => 'passwords', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 'test test']],
            [['flag' => 'success', 'email' => 'test+02@interest-marketing.net', 'id' => 'testDUNG', 'password' => 'passwords', 'promotion_code'=> '', 'type' => 'student', 'grade_id' => 123]],
            [['flag' => 'success', 'email' => 'test+02@interest-marketing.net', 'id' => 'testDUNG', 'password' => 'passwords', 'promotion_code'=> 'test test', 'type' => 'student', 'grade_id' => 123]],
            [['flag' => 'success', 'email' => 'test+02@interest-marketing.net', 'id' => 'testDUNG', 'password' => 'passwords', 'promotion_code'=> 123, 'type' => 'student', 'grade_id' => 123]],
            [['flag' => 'success', 'email' => 'test+02@interest-marketing.net', 'id' => 'testDUNG', 'password' => 'passwords', 'promotion_code'=> '', 'type' => 'student', ]],
        ];
    }

    /**
     * Test function verify_email
     * SPEC U_031
     * @dataProvider provider_verify_email
     * @group user_verify_email
     */
    public function test_verify_email($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);

        //param user_verify_email
        $this->user_email_verify  = new User_email_verify_builder();
        $p_user_email_verify['expired_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $p_user_email_verify['user_id'] = $res_user->id;
        $p_user_email_verify = $this->user_email_verify->builder($p_user_email_verify);

        //Create data user_email_verify in database
        $this->user_email_verify_model->create($p_user_email_verify);

        if($params_api['flag'] == 'success') {

            $params_api['token'] = $p_user_email_verify['token'];

        }

        //Call API
        $res = $this->api->verify_email($params_api);

        //Check data
        if ($params_api['flag'] == 'invalid_params') {

            $this->assertFalse($res['submit']);
            $this->assertTrue($res['success']);

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
    public function provider_verify_email()
    {
        return [
            [['flag' => 'invalid_params', 'token' => '' ]],

            [['flag' => 'bad_request', 'token' => 'test test']],

            [['flag' => 'success']],
        ];
    }

    /**
     * Test function update_password
     * SPEC U_051
     * @dataProvider provider_update_password
     * @group user_update_password
     */
    public function test_update_password($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);

        //param user_verify_email
        $this->user_password_verify  = new User_password_verify_builder();
        $p_user_password_verify['user_id'] = $res_user->id;
        $p_user_password_verify['expired_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        if (isset($params_api['expired_token'])) {

            $p_user_password_verify['expired_at'] = date('Y-m-d H:i:s', strtotime('-30 minutes'));
            unset($params_api['expired_token']);
        }

        $p_user_password_verify = $this->user_password_verify->builder($p_user_password_verify);

        //Create data user_email_verify in database
        $this->user_password_verify_model->create($p_user_password_verify);

        if(!isset($params_api['token'])) {

            $params_api['token'] = $p_user_password_verify['token'];

        }

        if(!isset($params_api['id'])) {

            $params_api['id'] = $res_user->login_id;
        }

        //Call API
        $res = $this->api->update_password($params_api);

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
    public function provider_update_password()
    {
        return [
            [['flag' => 'invalid_params', 'token' => '', 'password' => 'password', 'confirm_password' => 'password'  ]],
            [['flag' => 'invalid_params', 'id' => '', 'password' => 'password', 'confirm_password' => 'password'  ]],
            [['flag' => 'invalid_params', 'password' => '', 'confirm_password' => 'password'  ]],
            [['flag' => 'invalid_params', 'password' => 'password', 'confirm_password' => ''  ]],
            [['flag' => 'invalid_params', 'password' => 'password', 'confirm_password' => 'other_passwrd'  ]],

            [['flag' => 'bad_request', 'token' => 'token_wrong', 'password' => 'password', 'confirm_password' => 'password'  ]],
            [['flag' => 'bad_request', 'id' => 'token_wrong', 'password' => 'password', 'confirm_password' => 'password'  ]],
            [['flag' => 'bad_request', 'password' => 'password', 'confirm_password' => 'password', 'expired_token' => true  ]],

            [['flag' => 'success', 'password' => 'password', 'confirm_password' => 'password' ]],
        ];
    }

    /**
     * Test function send_verify_email
     * SPEC U_030
     * @dataProvider provider_send_verify_email
     * @group user_send_verify_email
     */
    public function test_send_verify_email($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        if(!isset($params_api['email'])) {

            $params_api['email'] = $p_user['email'];
        }

        //Call API
        $res = $this->api->send_verify_email($params_api);

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
    public function provider_send_verify_email()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '' ]],
            [['flag' => 'invalid_params', 'email' => 'interest-marketing.net' ]],

            [['flag' => 'bad_request', 'email' => 'tester999999@interest-marketing.net']],

            [['flag' => 'success']],
        ];
    }

    /**
     * Test function change_password
     * SPEC U_053
     * @dataProvider provider_change_password
     * @group user_change_password
     */
    public function test_change_password($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);

        $this->set_current_user($res_user->id);

        if(!isset($params_api['current_password'])) {

            $params_api['current_password'] = $p_user['password'];

        }

        //Call API
        $res = $this->api->change_password($params_api);

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
    public function provider_change_password()
    {
        return [
            [['flag' => 'invalid_params', 'current_password' => '', 'password' => 'password', 'confirm_password' => 'passwo rd'  ]],
            [['flag' => 'invalid_params', 'current_password' => 'wrong', 'password' => 'password', 'confirm_password' => 'passw ord'  ]],
            [['flag' => 'invalid_params', 'current_password' => 'wrong', 'password' => 'password', 'confirm_password' => 'w_pas sword'  ]],
            [['flag' => 'invalid_params', 'current_password' => 'wrong', 'password' => 'password', 'confirm_password' => ''  ]],
            [['flag' => 'invalid_params', 'current_password' => 'wrong', 'password' => '', 'confirm_password' => 'w_passw ord'  ]],

            [['flag' => 'success', 'password' => 'pas sword2', 'confirm_password' => 'pas sword2' ]],
        ];
    }

    /**
     * Test function update
     * SPEC U_060
     * @dataProvider provider_update
     * @group user_update
     */
    public function test_update($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);
        //param user
        /*
        $this->user_profile  = new User_profile_builder();
        $p_user_profile['user_id'] = $res_user->id;
        $p_user_profile = $this->user_profile->builder($p_user_profile);

        //Create data user_profile in database
        $res_user_profile = $this->user_profile_model->create($p_user_profile, ['return' => true]);
        */

        //param user_2 for check nickname exit
        $this->user  = new User_builder();
        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);

        //Create data user2 in database
        $res_user2 = $this->user_model->create($p_user2, ['return' => true]);

        if(!isset($params_api['nickname']))
        {
            $params_api['nickname'] = $res_user->nickname;
        }

        if(!isset($params_api['id']))
        {
            $params_api['id'] = $res_user->id;
        }

        //Call API
        $res = $this->api->update($params_api);

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
    public function provider_update()
    {
        return [
            [['flag' => 'invalid_params', 'id' => '', 'sex' => '1', 'avatar' => 1]],

            [['flag' => 'bad_request', 'id' => 99999999,'sex' => '1', 'avatar' => 1,'birthday'=> date('Y-m-d', strtotime('+1 years')) ]],

            [['flag' => 'success', 'sex' => '1', 'avatar' => 1,'birthday'=> date('Y-m-d', strtotime('-1 years')) ]],
        ];
    }

    /**
     * Test function resend_id
     * SPEC U_040
     * @dataProvider provider_resend_id
     * @group user_resend_id
     */
    public function test_resend_id($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $this->user_model->create($p_user);

        if(!isset($params_api['email'])) {

            $params_api['email'] = $p_user['email'];
        }

        //Call API
        $res = $this->api->resend_id($params_api);

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
    public function provider_resend_id()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '' ]],
            [['flag' => 'invalid_params', 'email' => 'interest-marketing.net' ]],

            [['flag' => 'bad_request', 'email' => 'tester999999@interest-marketing.net']],

             [['flag' => 'success']],
        ];
    }

    /**
     * Test function reset_password
     * SPEC U_050
     * @dataProvider provider_reset_password
     * @group user_reset_password
     */
    public function test_reset_password($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = $params_api['type'];
        unset($params_api['type']);
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $this->user_model->create($p_user);

        if(!isset($params_api['email'])) {

            $params_api['email'] = $p_user['email'];
        }

        //Call API
        $res = $this->api->reset_password($params_api);

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
    public function provider_reset_password()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '', 'type' => 'student' ]],
            [['flag' => 'invalid_params', 'email' => 'inter est-marketing.net', 'type' => 'parent' ]],

            [['flag' => 'bad_request', 'email' => 'tester999999@interest-marketing.net', 'type' => 'parent']],
            [['flag' => 'bad_request', 'email' => 'tester999999@interest-marketing.net', 'type' => 'student']],

            [['flag' => 'success', 'type' => 'parent']],
            [['flag' => 'success', 'type' => 'student']],
        ];
    }

    /**
     * Test function delete
     * SPEC U_100
     * @dataProvider provider_delete
     * @group user_delete
     */
    public function test_delete($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';

        $p_user['type'] = 'student';

        if(isset($params_api['type'])) {

            $p_user['type'] = $params_api['type'];
            unset($params_api['type']);
        }

        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        //$this->set_current_user($res_user->id);

        if(!isset($params_api['id'])) {

            $params_api['id'] = $res_user->login_id;
        }

        //Call API
        $res = $this->api->delete($params_api);

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
    public function provider_delete()
    {
        return [
            [['flag' => 'invalid_params', 'id' => '' ]],

            [['flag' => 'bad_request', 'id' => 99999 ]],
            [['flag' => 'bad_request', 'type' => 'parent' ]],

            [['flag' => 'success']],
        ];
    }

    /**
     * Test function update_email
     * SPEC U_052
     * @dataProvider provider_update_email
     * @group user_update_email
     */
    public function test_update_email($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data user 2 for check email exist
        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);

        //Create data user2 in database
        $this->user_model->create($p_user2);

        //Don't for update the same email other people
        if(!isset($params_api['email'])) {

            $params_api['email'] = $p_user2['email'];
        }

        if(!isset($params_api['id'])) {

            $params_api['id'] = $res_user->id;
        }

        //Call API
        $res = $this->api->update_email($params_api);

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
    public function provider_update_email()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '' ]],
            [['flag' => 'invalid_params', 'email' => 'interest-marketing.net' ]],
            [['flag' => 'invalid_params', ]],

            [['flag' => 'success', 'email' => 'tester999999@interest-marketing.net']],

        ];
    }

    /**
     * Test function get_list
     * SPEC U_070
     * @dataProvider provider_get_list
     * @group user_get_list
     */
    public function atest_get_list($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data
        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_user_group['user_id'] = $res_user->id;
        $p_user_group['group_id'] = $res_group->id;
        $this->user_group  = new User_group_builder();
        $p_user_group = $this->user_group->builder($p_user_group);
        $this->user_group_model->create($p_user_group);

        if(!isset($params_api['group_id'])) {

            $params_api['group_id'] = $res_group->id;
        }

        //Call API
        $res = $this->api->get_list($params_api);

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
    public function provider_get_list()
    {
        return [
            [['flag' => 'invalid_params', 'group_id' => '' ]],

           // [['flag' => 'bad_request', 'group_id' => 99999]],

            [['flag' => 'success', ]],
        ];
    }

    /**
     * Test function get_detail
     * SPEC U_071
     * @dataProvider provider_get_detail
     * @group user_get_detail
     */
    public function test_get_detail($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data
        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_user_group['user_id'] = $res_user->id;
        $p_user_group['group_id'] = $res_group->id;
        $this->user_group  = new User_group_builder();
        $p_user_group = $this->user_group->builder($p_user_group);
        $this->user_group_model->create($p_user_group);

        if(!isset($params_api['id'])) {

            $params_api['id'] = $res_user->id;
        }

        //Call API
        $res = $this->api->get_detail($params_api);

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
    public function provider_get_detail()
    {
        return [
            [['flag' => 'invalid_params', 'id' => '' ]],

            [['flag' => 'bad_request', 'id' => 9999999999999 ]],

            [['flag' => 'success',]],

        ];
    }

    /**
     * Test function send_invite
     * SPEC U_080
     * @dataProvider provider_send_invite
     * @group user_send_invite
     */
    public function test_send_invite($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Call API
        $res = $this->api->send_invite($params_api);

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
    public function provider_send_invite()
    {
        return [
            [['flag' => 'invalid_params', 'email' => '' ]],
            [['flag' => 'invalid_params', 'email' => 'interest-marketing.net' ]],

            [['flag' => 'success', 'email' => 'tester999999@interest-marketing.net']],

        ];
    }

    /**
     * Test function search
     * SPEC U_090
     * @dataProvider provider_search
     * @group user_search
     */
    public function test_search($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data user 2 for check email exist
        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);

        //Create data user2 in database
        $res_user2 = $this->user_model->create($p_user2, ['return' => true] );

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_user2->login_id;

        }
        //Call API
        $res = $this->api->search($params_api);

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
    public function provider_search()
    {
        return [
            [['flag' => 'invalid_params', 'id' => '' ]],

            [['flag' => 'success']],

        ];
    }

    /**
     * Test function search_list
     * SPEC U_095
     * @dataProvider provider_search_list
     * @group user_search_list
     */
    public function test_search_list($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data user 2 for check email exist
        $p_user2['id'] = 'tester2';
        $p_user2['type'] = 'student';
        $p_user2 = $this->user->builder($p_user2);

        //Create data user2 in database
        $res_user2 = $this->user_model->create($p_user2, ['return' => true] );

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_user2->login_id;

        }
        //Call API
        $res = $this->api->search_list($params_api);

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
    public function provider_search_list()
    {
        return [
          //  [['flag' => 'invalid_params', 'id' => '' ]],

            [['flag' => 'success']],

        ];
    }

    /**
     * Test function get_list_groups
     * SPEC UG_072
     * @dataProvider provider_get_list_groups
     * @group user_get_list_groups
     */
    public function test_get_list_groups($params_api)
    {
        //param user
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);

        //Create data user in database
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        //Create data
        $this->group  = new Group_builder();
        $p_group = $this->group->builder();
        $res_group = $this->group_model->create($p_group, ['return' => true]);

        $p_user_group['user_id'] = $res_user->id;
        $p_user_group['group_id'] = $res_group->id;
        $this->user_group  = new User_group_builder();
        $p_user_group = $this->user_group->builder($p_user_group);
        $this->user_group_model->create($p_user_group);

        if(!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user->id;
        }

        //Call API
        $res = $this->api->get_list_groups($params_api);

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
    public function provider_get_list_groups()
    {
        return [
            [['flag' => 'invalid_params', 'user_id' => '' ]],
            [['flag' => 'invalid_params', 'user_id' => 9999999999999 ]],

            [['flag' => 'success',]],

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
        //$this->CI->load->library('API/User_api');
        $this->api->current_user = $target;
        $this->api->set_operator($target);

        //Assign value for model
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
        //Get data account default in database
        $target = $this->admin_model->available(TRUE)->find($account_id);

        //Load library and assigned value
        //$this->CI->load->library('API/User_api');
        $this->api->current_user = $target;
        $this->api->set_operator($target);

        //Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}
