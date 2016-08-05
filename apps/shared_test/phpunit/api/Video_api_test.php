<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/video_builder.php";
require_once dirname(__FILE__) . "/../builder/video_progress_builder.php";
require_once dirname(__FILE__) . "/../builder/publisher_builder.php";
require_once dirname(__FILE__) . "/../builder/master_subject_builder.php";
require_once dirname(__FILE__) . "/../builder/master_grade_builder.php";
require_once dirname(__FILE__) . "/../builder/textbook_builder.php";
require_once dirname(__FILE__) . "/../builder/user_textbook_inuse_builder.php";
require_once dirname(__FILE__) . "/../builder/master_year_builder.php";
require_once dirname(__FILE__) . "/../builder/user_grade_history_builder.php";
require_once dirname(__FILE__) . "/../builder/deck_builder.php";
require_once dirname(__FILE__) . "/../builder/deck_video_inuse_builder.php";
require_once dirname(__FILE__) . "/../builder/textbook_content_builder.php";
require_once dirname(__FILE__) . "/../builder/video_view_count_builder.php";
/**
 * Test Video_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Video_api_test extends CIUnit_TestCase
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
        'deck_video_inuse_model',
        'video_view_count_model',
        'textbook_content_model',
        'video_progress_model',
        'deck_model',
        'video_model',
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
        $this->CI->load->library("API/Video_api");

        // Create object api
        $this->api =& $this->CI->video_api;

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
     * Test function get_detail
     * SPEC VD_010
     * @dataProvider provider_get_detail
     * @group video_get_detail
     */
    public function test_get_detail($params_api)
    {
        // Create data
        $this->user = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->video = new Video_builder();
        $p_video = $this->video->builder();
        $res_video = $this->video_model->create($p_video, ['return' => true]);

        $this->video_progress = new Video_progress_builder();
        $p_video_progress['video_id'] = $res_video->id;
        $p_video_progress = $this->video_progress->builder($p_video_progress);
        $this->video_progress_model->create($p_video_progress);

        // Call API
        if (!isset($params_api['video_id'])) {

            $params_api['video_id'] = $res_video->id;;
        }

        $res = $this->api->get_detail($params_api);

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
    public function provider_get_detail()
    {
        return [
            [['flag' => 'invalid_params', 'video_id'=> '']],
            [['flag' => 'invalid_params', 'video_id'=> 999999999]],

            [['flag' => 'success']],
        ];

    }

    /**
     * Test function update_progress
     * SPEC VD-021
     * @dataProvider provider_update_progress
     * @group video_update_progress
     */
    public function test_update_progress($params_api)
    {
        // Create data
        $this->user = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        $this->set_current_user($res_user->id);

        $this->video = new Video_builder();
        $p_video = $this->video->builder();
        $res_video = $this->video_model->create($p_video, ['return' => true]);

        $this->video_progress = new Video_progress_builder();
        $p_video_progress['video_id'] = $res_video->id;
        $p_video_progress = $this->video_progress->builder($p_video_progress);
        $this->video_progress_model->create($p_video_progress);

        // Call API
        if (!isset($params_api['video_id'])) {

            $params_api['video_id'] = $res_video->id;;
        }

        $res = $this->api->update_progress($params_api);

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
    public function provider_update_progress()
    {
        return [
            [['flag' => 'invalid_params', 'video_id'=> '', 'cookie_id' => '', 'second' => '', 'session_id' => '']],
            [['flag' => 'invalid_params', 'video_id'=> 999999999]],

            [['flag' => 'success',  'cookie_id' => 'cookie_id', 'second' => 123, 'session_id' => 'session_id']],
        ];

    }

    /**
     * Test function get_progress
     * SPEC VD-022
     * @dataProvider provider_get_progress
     * @group video_get_progress
     */
    public function test_get_progress($params_api)
    {
        // Create data
        $this->user = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
       // $this->set_current_user($res_user->id);

        $this->video = new Video_builder();
        $p_video = $this->video->builder();
        $res_video = $this->video_model->create($p_video, ['return' => true]);

        $this->video_progress = new Video_progress_builder();
        $p_video_progress['video_id'] = $res_video->id;
        $p_video_progress = $this->video_progress->builder($p_video_progress);
        $this->video_progress_model->create($p_video_progress);

        // Call API
        if (!isset($params_api['video_id'])) {

            $params_api['video_id'] = $res_video->id;;
        }

        $res = $this->api->get_progress($params_api);

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
    public function provider_get_progress()
    {
        return [
            [['flag' => 'invalid_params', 'video_id'=> '']],

            [['flag' => 'success']],
        ];

    }

    /**
     * Test function get_list
     * SPEC VD-030
     * @dataProvider provider_get_list
     * @group video_get_list
     */
    public function test_get_list($params_api)
    {
        // Create data
        $this->user = new User_builder();
        $p_user['id'] = 'tester';
        $p_user['type'] = 'student';
        $p_user = $this->user->builder($p_user);
        $res_user = $this->user_model->create($p_user, ['return' => true]);
        // $this->set_current_user($res_user->id);

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
        $p_textbook['year_id'] = $res_master_year->id;
        $p_textbook = $this->master_subject->builder($p_textbook);
        $res_textbook = $this->textbook_model->create($p_textbook, ['return' => true]);

        $this->deck = new Deck_builder();
        $p_deck = $this->deck->builder();
        $res_deck = $this->deck_model->create($p_deck, ['return' => true]);

        $this->video = new Video_builder();
        $p_video = $this->video->builder();
        $res_video = $this->video_model->create($p_video, ['return' => true]);

        $this->deck_video_inuse = new Deck_video_inuse_builder();
        $p_deck_video_inuse['deck_id'] = $res_deck->id;
        $p_deck_video_inuse['video_id'] = $res_video->id;
        $p_deck_video_inuse = $this->deck_video_inuse->builder($p_deck_video_inuse);
        $this->deck_video_inuse_model->create($p_deck_video_inuse);

        $this->video_view_count = new Video_view_count_builder();
        $p_video_view_count['video_id'] = $res_video->id;
        $p_video_view_count = $this->video_view_count->builder($p_video_view_count);
        $this->video_view_count_model->create($p_video_view_count);

        $this->textbook_content = new Textbook_content_builder();
        $p_textbook_content['deck_id'] = $res_deck->id;
        $p_textbook_content['textbook_id'] = $res_textbook->id;
        $p_textbook_content['video_id'] = $res_video->id;
        $p_textbook_content = $this->textbook_content->builder($p_textbook_content);
        $res_textbook_content = $this->textbook_content_model->create($p_textbook_content, ['return' => true]);

        $this->video_progress = new Video_progress_builder();
        $p_video_progress['video_id'] = $res_video->id;
        $p_video_progress = $this->video_progress->builder($p_video_progress);
        $this->video_progress_model->create($p_video_progress);

        // Call API
        if (!isset($params_api['subject_id'])) {

            $params_api['subject_id'] = $res_master_subject->id;;
        }

        if (!isset($params_api['grade_id'])) {

            $params_api['grade_id'] = $res_master_grade->id;;
        }

        if (!isset($params_api['textbook_id'])) {

            $params_api['textbook_id'] = $res_textbook->id;;
        }

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
            //[['flag' => 'invalid_params', 'subject_id'=> 12]],
            //[['flag' => 'invalid_params', 'video_id'=> 999999999]],

            [['flag' => 'success']],
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
