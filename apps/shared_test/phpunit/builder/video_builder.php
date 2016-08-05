<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Video_builder
{
    public function builder($params = array()){
        $default = array(
            'type' => 'textbook',
            'name' => 'name',
            'description' => 'description',
            'brightcove_id' => 'brightcove_id',
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
        );

        return array_merge($default, $params);
    }
}