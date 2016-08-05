<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Video_progress_builder
{
    public function builder($params = array()){
        $default = array(
          //  'video_id' => 'video_id',
            //'user_id' => 'name',
            'cookie_id' => 'cookie_id',
            'session_id' => 'session_id',
            'second' => 12.3,
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
            'done_flag' => '1',
        );

        return array_merge($default, $params);
    }
}