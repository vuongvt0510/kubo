<?php

require_once dirname(__FILE__) . "/../builder/claim_builder.php";
require_once dirname(__FILE__) . "/../builder/account_builder.php";

/**
 * Test model claim
 * @author dung.nguyen@interest-marketing
 */
class Claim_model_test extends CIUnit_TestCase
{
    protected $models = array(
        "account_address_model",
        "bank_account_model",
        "account_model",
        "claim_model",
    );

    public function setUp(){
        foreach ($this->models as $m){
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }

        $this->set_current_user();

        $this->tearDown();
        parent::setUp();

        $this->builder = new Claim_builder();
        $this->account_builder = new Account_builder();
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
     *
     * @test
     */
    public function test_update()
    {
        //Create data
        $params = $this->builder->build_claim_params();

        $params_account = $this->account_builder->build_account_params();
        $account = $this->account_model->create($params_account, ['return' => TRUE]);

        $params['account_id'] = $account->id;
        $claim = $this->claim_model->create($params, ['return' => TRUE]);

        $res = $this->claim_model->update($claim->id, $params, ['return' => TRUE]);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->status, $params['status']);
        $this->assertEquals($res->account_id, $params['account_id']);
        $this->assertEquals($res->published_at, $params['published_at']);
        $this->assertEquals($res->created_at, $params['created_at']);
        $this->assertEquals($res->created_by, $params['created_by']);
        $this->assertEquals($res->updated_at, $params['updated_at']);
        $this->assertEquals($res->updated_by, $params['updated_by']);

    }

    /**
     * Test function create
     *
     * @test
     */
    public function test_create()
    {
        //Create data
        $params = $this->builder->build_claim_params();
        $params_account = $this->account_builder->build_account_params();

        $account = $this->account_model->create($params_account, ['return' => TRUE]);

        $params['account_id'] = $account->id;
        $claim = $this->claim_model->create($params, ['return' => TRUE]);

        $this->assertNotEmpty($claim);

        $res = $this->claim_model->find($claim->id);

        //Delete data account
        $this->account_model->destroy($account->id);

        //Check data
        $this->assertEquals($res->status, $params['status']);
        $this->assertEquals($res->account_id, $params['account_id']);
        $this->assertEquals($res->published_at, $params['published_at']);
        $this->assertEquals($res->created_at, $params['created_at']);
        $this->assertEquals($res->created_by, $params['created_by']);
        $this->assertEquals($res->updated_at, $params['updated_at']);
        $this->assertEquals($res->updated_by, $params['updated_by']);

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