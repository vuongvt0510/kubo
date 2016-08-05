<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Admin_builder
{
    public function builder($params = array()){
        $default = array(
            'name' => 'name',
            'class' => 'admin',
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