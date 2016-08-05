<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_grade_builder
{
    public function builder($params = array()){
        $default = array(
            'name' => 'mg_name',
        );

        return array_merge($default, $params);
    }
}