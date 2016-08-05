<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_area_builder
{
    public function builder($params = array()){
        $default = array(
            'name' => generate_unique_key(8),
            'area_code' => generate_unique_key(5),
        );

        return array_merge($default, $params);
    }
}