<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class News_builder
{
    public function builder($params = array()){
        $default = array(
            'title' => 'title',
            'content' => 'content',
            'status' => 'public',
            'started_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'ended_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'tester',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'tester',

        );

        return array_merge($default, $params);
    }
}