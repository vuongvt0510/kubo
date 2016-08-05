<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class User_grade_history_builder
{
    public function builder($params = array()){
        $default = array(
           //'user_id' => 1,
            //'grade_id' => 1,
            'registered_at' => date('Y-m-d h:i:s'),
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
        );

        return array_merge($default, $params);
    }
}