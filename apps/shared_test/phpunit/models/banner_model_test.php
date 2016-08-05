<?php

require_once dirname(__FILE__) . "/../builder/banner_place_master_builder.php";
require_once dirname(__FILE__) . "/../builder/banner_builder.php";



/**
* Test model banner
 *
 * @author dung.nguyen@interest-marketing
 */
class Banner_model_test extends CIUnit_TestCase
{
    protected $models = array(
        "banner_model",
        "banner_place_master_model",
    );

    public function setUp(){
        foreach ($this->models as $m){
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }

        $this->tearDown();
        parent::setUp();

        $this->builder = new Banner_builder();
        $this->banner_place_master_builder = new Banner_place_master_builder();
    }

    public function tearDown(){
        foreach ($this->models as $m) {
            $this->CI->{$m}->trans_force_rollback();
            $this->CI->{$m}->trans_reset_status();
            $this->CI->{$m}->empty_table();
        }

        parent::tearDown();
    }

    /**
     * @test
     * test function search with params
     */
    public function test_search()
    {
        //Create data
        $params_build_banner_place_master = $this->banner_place_master_builder->build_banner_place_master_params();

        $banner_place_master = $this
            ->banner_place_master_model
            ->create
        (
            $params_build_banner_place_master,
            ['return' => TRUE]
        );

        $params['place_id'] = $banner_place_master->id;
        $params['url'] = '/tmp/img/logo.jpg';
        $params = $this->builder->build_banner_params($params);

        $this->banner_model->create($params);

        $res = $this->banner_model->search($params, ['return' => TRUE]);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res[0]->url, $params['url']);
        $this->assertEquals($res[0]->place_id, $params['place_id']);
        $this->assertEquals($res[0]->created_at, $params['created_at']);
        $this->assertEquals($res[0]->created_by, $params['created_by']);
        $this->assertEquals($res[0]->updated_at, $params['updated_at']);
        $this->assertEquals($res[0]->updated_by, $params['updated_by']);
    }

    /**
     * Test function find detail
     */
    public function test_find_detail()
    {
        //Create data
        $params_build_banner_place_master = $this->banner_place_master_builder->build_banner_place_master_params();

        $params_build_banner_place_master['height'] = 320;
        $banner_place_master = $this->banner_place_master_model->create
        (
            $params_build_banner_place_master,
            ['return' => TRUE]
        );

        $params['place_id'] = $banner_place_master->id;
        $params['url'] = '/tmp/img/logo.jpg';
        $params = $this->builder->build_banner_params($params);

        $banner = $this->banner_model->create($params, ['return' => TRUE]);

        $res = $this->banner_model->find_detail($banner->id, ['return' => TRUE]);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res->id, $banner->id);
        $this->assertEquals($res->url, $params['url']);
        $this->assertEquals($res->place_id, $params['place_id']);
        $this->assertEquals($res->created_at, $params_build_banner_place_master['created_at']);
        $this->assertEquals($res->created_by, $params_build_banner_place_master['created_by']);
        $this->assertEquals($res->updated_at, $params_build_banner_place_master['updated_at']);
        $this->assertEquals($res->updated_by, $params_build_banner_place_master['updated_by']);
        $this->assertEquals($res->height, $params_build_banner_place_master['height']);

    }

    /**
     * Test function banner_exists
     * with display_count >= total row
     */
    public function test_banner_exists_false()
    {
        //Create data
        $params_build_banner_place_master['display_count'] = 3;
        $params_build_banner_place_master = $this
            ->banner_place_master_builder
            ->build_banner_place_master_params();

        $banner_place_master = $this
            ->banner_place_master_model
            ->create
        (
            $params_build_banner_place_master,
            ['return' => TRUE]
        );

        $params['place_id'] = $banner_place_master->id;
        $params['place_id'] = $banner_place_master->id;
        $params['closed_at'] = business_date('Y-m-d H:i:s', time()+2);
        $params = $this->builder->build_banner_params($params);

        $banner = $this->banner_model->create($params, ['return' => TRUE]);

        $res = $this->banner_model->banner_exists($banner->id);

        //Check data
        $this->assertFalse($res);
    }

    /**
     * Test function banner_exists
     * with display_count < total row
     */
    public function test_banner_exists_true()
    {
        //Create data
        $params_build_banner_place_master['display_count'] = 0;
        $params_build_banner_place_master = $this
            ->banner_place_master_builder
            ->build_banner_place_master_params($params_build_banner_place_master);

        $banner_place_master = $this
            ->banner_place_master_model
            ->create
        (
            $params_build_banner_place_master,
            ['return' => TRUE]
        );

        $params['place_id'] = $banner_place_master->id;
        $params['closed_at'] = business_date('Y-m-d H:i:s', time()+2);
        $params = $this->builder->build_banner_params($params);

        $banner = $this->banner_model->create($params, ['return' => TRUE]);

        $res = $this->banner_model->banner_exists($banner->id);

        //Check data
        $this->assertTrue($res);

    }

}