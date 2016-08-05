<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Group_invite_builder
{
    public function builder($params = array()){
        $default = array(
            'email' =>  generate_unique_key(18).'@interest-marketing.com',
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'tester',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'tester',
        );

        return array_merge($default, $params);
    }
}