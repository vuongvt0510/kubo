<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/publisher_builder.php";
require_once dirname(__FILE__) . "/../builder/master_subject_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";
require_once dirname(__FILE__) . "/../builder/textbook_builder.php";
require_once dirname(__FILE__) . "/../builder/master_year_builder.php";
require_once dirname(__FILE__) . "/../builder/cache_textbook_count_builder.php";
require_once dirname(__FILE__) . "/../builder/master_textbook_inuse_builder.php";
require_once dirname(__FILE__) . "/../builder/master_postalcode_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_builder.php";
require_once dirname(__FILE__) . "/../builder/master_school_builder.php";
require_once dirname(__FILE__) . "/../builder/master_area_pref_group_builder.php";


/**
 * Test textbook api
 *
 * @author dung.nguyen@interest-marketing
 */
class Textbook_api_test extends CIUnit_TestCase
{
    //Set model
    protected $models = array(
        'master_school_model',
        'master_area_model',
        'master_area_pref_model',
        'master_area_pref_group_model',
        'master_postalcode_model',
        'master_textbook_inuse_model',
        'cache_textbook_count_model',
        'master_year_model',
        'user_account_model',
        'textbook_model',
        'publisher_model',
        'master_grade_model',
        'master_subject_model',
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
        $this->CI->load->library("API/Textbook_api");

        //Create object api
        $this->api =& $this->CI->textbook_api;

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
     * Test function search
     * SPEC TP_010
     * @dataProvider provider_search
     * @group textbook_search
     */
    public function test_search($params_api)
    {
        //create data
        $this->user  = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->master_grade  = new Master_grade_builder();
        $p_master_grade = $this->master_grade->builder();
        $res_master_grade = $this->master_grade_model->create($p_master_grade, ['return' => true]);

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
        $p_textbook = $this->textbook->builder($p_textbook);
        $res_textbook = $this->textbook_model->create($p_textbook, ['return' => true]);

        $this->master_postalcode = new Master_postalcode_builder();
        $this->master_area_pref = new Master_area_pref_builder();
        $this->master_area = new Master_area_builder();
        $this->master_school = new Master_school_builder();
        $this->master_area_pref_group = new Master_area_pref_builder();

        $p_master_postalcode = $this->master_postalcode->builder();
        $p_master_postalcode['postalcode'] = $this->master_school_model->sanitize_word($p_master_postalcode['postalcode']);
        $res_master_postalcode = $this->master_postalcode_model->create($p_master_postalcode, ['return' => true]);

        $p_master_area_pref_group = $this->master_area_pref_group->builder();
        $res_master_area_pref_group = $this->master_area_pref_group_model->create($p_master_area_pref_group, ['return' => true]);

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


        $this->master_textbook_inuse  = new Master_textbook_inuse_builder();
        $p_master_textbook_inuse['school_id'] = $res_master_school->id;
        $p_master_textbook_inuse['year_id'] = $res_master_year->id;
        $p_master_textbook_inuse['subject_id'] = $res_master_subject->id;
        $p_master_textbook_inuse['textbook_id'] = $res_textbook->id;
        $p_master_textbook_inuse = $this->master_textbook_inuse->builder($p_master_textbook_inuse);

        $res_master_textbook_inuse = $this->master_textbook_inuse_model->create($p_master_textbook_inuse, ['return' => true]);

        $this->cache_textbook_count = new Cache_textbook_count_builder();
        $p_cache_textbook_count['textbook_id'] = $res_textbook->id;
        $p_cache_textbook_count = $this->cache_textbook_count->builder($p_cache_textbook_count);
        $this->cache_textbook_count_model->create($p_cache_textbook_count);

        if (isset($params_api['keyword'])) {

            $params_api['keyword'] = $res_publisher->name;

        }
        if (isset($params_api['school_id'])) {

            $params_api['school_id'] = $res_master_school->id;

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
          //  [['flag' => 'invalid_params', 'keyword' => '' ]],

            [['flag' => 'success', 'school_id' => 1, 'keyword' => 1]],
            [['flag' => 'success']],
            [['flag' => 'success', 'school_id' => 1]],
            [['flag' => 'success', 'keyword' => 1]],

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
