<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/publisher_builder.php";
require_once dirname(__FILE__) . "/../builder/master_subject_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";
require_once dirname(__FILE__) . "/../builder/textbook_builder.php";
require_once dirname(__FILE__) . "/../builder/user_textbook_inuse_builder.php";

/**
 * Test User_grade_api
 *
 * @author dung.nguyen@interest-marketing
 */
class User_grade_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'user_grade_history_model',
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
        $this->CI->load->library("API/User_grade_api");

        // Create object api
        $this->api =& $this->CI->user_grade_api;

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
     * SPEC UGD_040
     * @dataProvider provider_update
     * @group user_grade_update
     */
    public function test_update($params_api)
    {
        // Create data
        $this->master_grade  = new Master_grade_builder();
        $p_master_grade['id'] = 1;
        $p_master_grade = $this->master_grade->builder($p_master_grade);
        $res_master_grade = $this->master_grade_model->create($p_master_grade, ['return' => true]);

        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user['grade_id'] = $res_master_grade->id;
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        if (!isset($params_api['grade_id'])) {

            $params_api['grade_id'] = $res_master_grade->id;

        }

        if (!isset($params_api['id'])) {

            $params_api['id'] = $res_user->id;

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
            [['flag' => 'invalid_params', 'grade_id' => '' , 'id' => '' ]],
            [['flag' => 'invalid_params', 'grade_id' => 99999999999999 ]],

            [['flag' => 'bad_request', 'id' => 99999999999999 ]],

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
        $this->api->current_user = $target;
        $this->api->set_operator($target);

        // Assign value for model
        if ($target instanceof APP_Operator) {
            APP_Model::set_operator($target);
        }
    }

}
