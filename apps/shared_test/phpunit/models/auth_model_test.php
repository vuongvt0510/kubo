<?php

require_once dirname(__FILE__) . "/../builder/account_builder.php";


/**

 */
class Auth_model_test extends CIUnit_TestCase
{
    protected $models = array(
        "auth_model"
    );

    public function setUp(){
        foreach ($this->models as $m){
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }

        $this->tearDown();
        parent::setUp();

        $this->builder = new Account_builder();
    }

    public function tearDown(){
        foreach ($this->models as $m) {
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->trans_reset_status();
           // $this->CI->{$m}->empty_table();
        }

        parent::tearDown();
    }

    /**
     * Test function login
     *
     * @test
     */
    public function test_login()
    {
        //Create data
        $params['role_id'] = 1; //Permission all root
        $params = $this->builder->build_account_params($params);
        $this->auth_model->create($params);

        $res = $this->auth_model->login($params['email'], $params['password']);

        //Delete account
        $this->auth_model->destroy($res->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->email, $params['email']);
        $this->assertEquals($res->name, $params['name']);

    }

    /**
     * Test function find email
     */
    public function test_find_by_email()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $this->auth_model->create($params);
        $res = $this->auth_model->find_by_email($params['email'], ['return' => TRUE]);

        //Delete account
        $this->auth_model->destroy($res->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->email, $params['email']);
    }

}