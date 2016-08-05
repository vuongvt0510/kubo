<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class User_profile_builder
{
    public function builder($params = array()){
        $default = array(
            'avatar_id' => 1111,
            'birthday' => date('Y-m-d h:i:s'),
            'gender' => 0,
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'tester',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'tester',
        );

        return array_merge($default, $params);
    }
}