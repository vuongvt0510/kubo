<?php

require_once dirname(__FILE__) . "/../builder/account_builder.php";


/**
* test model account
 *
 * @author dung.nguyen@interest-marketing
 */
class Account_model_test extends CIUnit_TestCase
{
    protected $models = array(
        "account_address_model",
        "bank_account_model",
        "account_model",

    );

    public function setUp(){
        foreach ($this->models as $m){
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }

        $this->set_current_user();

        $this->tearDown();
        parent::setUp();

        $this->builder = new Account_builder();
    }

    public function tearDown(){
        foreach ($this->models as $m) {
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->trans_reset_status();
            if($m != 'account_model')
                $this->CI->{$m}->empty_table();
        }

        parent::tearDown();
    }

    /**
     * Test function update
     */
    public function test_update()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $account = $this->account_model->create($params, ['return' => TRUE]);

        $res = $this->account_model->update($account->id, $params, ['return' => TRUE]);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->role_id, $params['role_id']);
        $this->assertEquals($res->special_flag, $params['special_flag']);
        $this->assertEquals($res->display_name, $params['display_name']);
        $this->assertEquals($res->status, $params['status']);
        $this->assertEquals($res->business_title, $params['business_title']);
        $this->assertEquals($res->name, $params['name']);
        $this->assertEquals($res->name_kana, $params['name_kana']);
        $this->assertEquals($res->image_key, $params['image_key']);
        $this->assertEquals($res->introduction, $params['introduction']);
        $this->assertEquals($res->tel, $params['tel']);

    }

    /**
     * test function create
     */
    public function test_create()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $account = $this->account_model->create($params, ['return' => TRUE]);

        $res = $this->account_model->find($account->id);

        //Delete data account
        $this->account_model->destroy($res->id);

        //Check data
        $this->assertNotEmpty($account);
        $this->assertEquals($res->role_id, $params['role_id']);
        $this->assertEquals($res->special_flag, $params['special_flag']);
        $this->assertEquals($res->display_name, $params['display_name']);
        $this->assertEquals($res->status, $params['status']);
        $this->assertEquals($res->business_title, $params['business_title']);
        $this->assertEquals($res->name, $params['name']);
        $this->assertEquals($res->name_kana, $params['name_kana']);
        $this->assertEquals($res->image_key, $params['image_key']);
        $this->assertEquals($res->introduction, $params['introduction']);
        $this->assertEquals($res->tel, $params['tel']);
    }

    /**
     * test function update email
     */
    public function test_update_email()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $account = $this->account_model->create($params, ['return' => TRUE]);

        $params['email'] = 'dung.nguyen@interest-marketing.com';
        $res = $this->account_model->update_email($account->id, $params['email']);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertEquals($res, 1);
    }

    /**
     * test function find by email
     */
    public function test_find_by_email()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $account = $this->account_model->create($params, ['return' => TRUE]);

        $res = $this->account_model->find_by_email($account->email);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->email, $params['email']);
        $this->assertEquals($res->role_id, $params['role_id']);
        $this->assertEquals($res->special_flag, $params['special_flag']);
        $this->assertEquals($res->display_name, $params['display_name']);
        $this->assertEquals($res->status, $params['status']);
        $this->assertEquals($res->business_title, $params['business_title']);
        $this->assertEquals($res->name, $params['name']);
        $this->assertEquals($res->name_kana, $params['name_kana']);
        $this->assertEquals($res->image_key, $params['image_key']);
        $this->assertEquals($res->introduction, $params['introduction']);
        $this->assertEquals($res->tel, $params['tel']);

    }

    /**
     * Test function search
     *
     */
    public function test_search()
    {
        //Create data
        $params = $this->builder->build_account_params();
        $params['names'][0] = '重松和明';
        $account = $this->account_model->create($params, ['return' => TRUE]);

        $res = $this->account_model->search($params);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res[0]->name, $params['names'][0]);

    }

    protected function set_current_user()
    {
        //Get data account default in database
        $target = $this->account_model->available(TRUE)->find(2);
        //Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}