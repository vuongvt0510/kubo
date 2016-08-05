<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_subject_builder
{
    public function builder($params = array()){
        $default = array(
            //'grade_id' => 2,
            'name' => 'ms_name',
        );

        return array_merge($default, $params);
    }
}