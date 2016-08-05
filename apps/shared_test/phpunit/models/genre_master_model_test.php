<?php

require_once dirname(__FILE__) . "/../builder/genre_master_builder.php";



/**
 * Test genre master model
 * @author dung.nguyen@interest-marketing
 */
class Genre_master_model_test extends CIUnit_TestCase
{
    protected $models = array(
        "genre_master_model"
    );

    public function setUp(){
        foreach ($this->models as $m){
            $this->CI->load->model($m);
            $this->{$m} =& $this->CI->{$m};
        }

        $this->tearDown();
        parent::setUp();

        $this->builder = new Genre_master_builder();
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
     * Test function get list
     */
    public function test_get_list()
    {
        //Create data
        $params = $this->builder->build_genre_master_params();

        $params['identifier'] = generate_unique_key(10);
        $this->genre_master_model->create($params);

        $res = $this->genre_master_model->get_list(['return' => TRUE]);

        //Check data
        $this->assertNotEmpty($res);
        $this->assertEquals($res[0]->identifier, $params['identifier']);
        $this->assertEquals($res[0]->created_at, $params['created_at']);
        $this->assertEquals($res[0]->created_by, $params['created_by']);
        $this->assertEquals($res[0]->updated_at, $params['updated_at']);
        $this->assertEquals($res[0]->updated_by, $params['updated_by']);
        $this->assertEquals($res[0]->updated_by, $params['updated_by']);

    }

}