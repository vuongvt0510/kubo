<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/publisher_builder.php";
require_once dirname(__FILE__) . "/../builder/master_subject_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";
require_once dirname(__FILE__) . "/../builder/textbook_builder.php";
require_once dirname(__FILE__) . "/../builder/user_textbook_inuse_builder.php";
require_once dirname(__FILE__) . "/../builder/master_year_builder.php";
require_once dirname(__FILE__) . "/../builder/user_grade_history_builder.php";

/**
 * Test User_textbook_api
 *
 * @author dung.nguyen@interest-marketing
 */
class User_textbook_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
        'user_grade_history_model',
        'user_textbook_inuse_model',
        'textbook_model',
        'publisher_model',
        'master_year_model',
        'master_grade_model',
        'master_subject_model',
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
        $this->CI->load->library("API/User_textbook_api");

        // Create object api
        $this->api =& $this->CI->user_textbook_api;

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
     * SPEC UTB_010
     * @dataProvider provider_get_list
     * @group user_textbook_get_list
     */
    public function test_get_list($params_api)
    {
        // Create data
        $this->master_grade  = new Master_grade_builder();
        $p_master_grade = $this->master_grade->builder();
        $res_master_grade = $this->master_grade_model->create($p_master_grade, ['return' => true]);

        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user['grade_id'] = $res_master_grade->id;
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->master_year  = new Master_year_builder();
        $p_master_year = $this->master_year->builder();
        $res_master_year = $this->master_year_model->create($p_master_year, ['return' => true]);

        $this->master_subject  = new Master_subject_builder();
        $p_master_subject['grade_id'] = $res_master_grade->id;
        $p_master_subject = $this->master_subject->builder($p_master_subject);
        $res_master_subject = $this->master_subject_model->create($p_master_subject, ['return' => true]);

        $this->publisher  = new Publisher_builder();
        $p_publisher = $this->publisher->builder();
        $res_publisher = $this->publisher_model->create($p_publisher, ['return' => true]);

        $this->textbook  = new Textbook_builder();
        $p_textbook['subject_id'] = $res_master_subject->id;
        $p_textbook['publisher_id'] = $res_publisher->id;
        $p_textbook['year_id'] = $res_master_year->id;
        $p_textbook = $this->master_subject->builder($p_textbook);
        $res_textbook = $this->textbook_model->create($p_textbook, ['return' => true]);

        $this->user_textbook_inuse  = new User_textbook_inuse_builder();
        $p_user_textbook_inuse ['user_id'] = $res_user->id;
        $p_user_textbook_inuse ['textbook_id'] = $res_textbook->id;
        $p_user_textbook_inuse ['grade_id'] = $res_master_grade->id;
        $p_user_textbook_inuse = $this->user_textbook_inuse->builder($p_user_textbook_inuse);
        $this->user_textbook_inuse_model->create($p_user_textbook_inuse);

        if (!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user->id;

        }

        // Call API
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
            [['flag' => 'invalid_params', 'user_id' => '' ]],

            [['flag' => 'bad_request', 'user_id' => 99999999999999 ]],

            [['flag' => 'success', ]],

        ];
    }

    /**
     * Test function update
     * SPEC UTB-050
     * @dataProvider provider_update
     * @group user_textbook_update
     */
    public function test_update($params_api)
    {
        // Create data
        $this->master_grade  = new Master_grade_builder();
        $p_master_grade = $this->master_grade->builder();
        $res_master_grade = $this->master_grade_model->create($p_master_grade, ['return' => true]);
        /*
        $this->user_grade_history  = new User_grade_history_builder();
        $p_user_grade_history['user_id'] = $res_user->id;
        $p_user_grade_history['grade_id'] = $res_master_grade->id;
        $p_user_grade_history = $this->user_grade_history->builder($p_user_grade_history);
        $this->user_grade_history_model->create($p_user_grade_history);
        */
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user['grade_id'] = $res_master_grade->id;
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->master_year  = new Master_year_builder();
        $p_master_year = $this->master_year->builder();
        $res_master_year = $this->master_year_model->create($p_master_year, ['return' => true]);

        $this->master_subject  = new Master_subject_builder();
        $p_master_subject['grade_id'] = $res_master_grade->id;
        $p_master_subject = $this->master_subject->builder($p_master_subject);
        $res_master_subject = $this->master_subject_model->create($p_master_subject, ['return' => true]);

        $this->publisher  = new Publisher_builder();
        $p_publisher = $this->publisher->builder();
        $res_publisher = $this->publisher_model->create($p_publisher, ['return' => true]);

        $this->textbook  = new Textbook_builder();
        $p_textbook['subject_id'] = $res_master_subject->id;
        $p_textbook['publisher_id'] = $res_publisher->id;
        $p_textbook['year_id'] = $res_master_year->id;
        $p_textbook = $this->master_subject->builder($p_textbook);
        $res_textbook = $this->textbook_model->create($p_textbook, ['return' => true]);

        $this->user_textbook_inuse  = new User_textbook_inuse_builder();
        $p_user_textbook_inuse ['user_id'] = $res_user->id;
        $p_user_textbook_inuse ['textbook_id'] = $res_textbook->id;
        $p_user_textbook_inuse ['grade_id'] = $res_master_grade->id;
        $p_user_textbook_inuse = $this->user_textbook_inuse->builder($p_user_textbook_inuse);
        $this->user_textbook_inuse_model->create($p_user_textbook_inuse);

        if (!isset($params_api['user_id'])) {

            $params_api['user_id'] = $res_user->id;

        }

        if (!isset($params_api['textbook_id'])) {

            $params_api['textbook_id'] = $res_textbook->id;

        }

        if (!isset($params_api['new_textbook_id'])) {

            $params_api['new_textbook_id'] = $res_textbook->id;

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
            [['flag' => 'invalid_params', 'user_id' => '', 'textbook_id' => '', 'new_textbook_id' => '' ]],
            [['flag' => 'invalid_params',  'textbook_id' => 99999999999999,  ]],
            [['flag' => 'invalid_params',  'new_textbook_id' => 99999999999999,  ]],

            [['flag' => 'bad_request', 'user_id' => 99999999999999 ]],

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