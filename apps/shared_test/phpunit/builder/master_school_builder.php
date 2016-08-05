<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_school_builder
{
    public function builder($params = array()){
        $default = array(
            'type' => 'elementary',
            'name' =>  generate_unique_key(8),
            'kind' => 'national',
            'short_name' => 'short_name',
            'students' => 2,
            'kcode' => generate_unique_key(5),

        );

        return array_merge($default, $params);
    }
}