<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class User_builder
{
    public function builder($params = array()){
        $default = array(
            'primary_type' => 'student',
            'email' => generate_unique_key(18).'@interest-marketing.com',
            'nickname' => generate_unique_key(8),
            'current_school' => '',
            'current_grade' => '',
            'email_verified' => 1,
            'status' => 'active',
            'password' => 'password',
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
        );

        return array_merge($default, $params);
    }
}