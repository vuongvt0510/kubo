<?php
require_once dirname(__FILE__) . "/../builder/user_builder.php";
require_once dirname(__FILE__) . "/../builder/video_builder.php";
require_once dirname(__FILE__) . "/../builder/deck_builder.php";
require_once dirname(__FILE__) . "/../builder/deck_video_inuse_builder.php";

/**
 * Test Deck_api
 *
 * @author dung.nguyen@interest-marketing
 */
class Deck_api_test extends CIUnit_TestCase
{

    // Set model
    protected $models = array(
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
        $this->CI->load->library("API/Deck_api");

        // Create object api
        $this->api =& $this->CI->deck_api;

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
     * SPEC D-010
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

        $this->deck = new Deck_builder();
        $p_deck = $this->deck->builder();
        $res_deck = $this->deck_model->create($p_deck, ['return' => true]);

        $this->deck_video_inuse = new Deck_video_inuse_builder();
        $p_deck_video_inuse['deck_id'] = $res_deck->id;
        $p_deck_video_inuse['video_id'] = $res_video->id;
        $p_deck_video_inuse = $this->deck_video_inuse->builder($p_deck_video_inuse);
        $this->deck_video_inuse_model->create($p_deck_video_inuse);

        // Call API
        if (!isset($params_api['deck_id'])) {

            $params_api['deck_id'] = $res_video->id;;
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
            [['flag' => 'invalid_params', 'deck_id'=> '']],
           // [['flag' => 'invalid_params', 'deck_id'=> 999999999]],

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
