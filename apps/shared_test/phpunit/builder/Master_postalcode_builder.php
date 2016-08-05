<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_postalcode_builder
{
    public function builder($params = array()){
        $default = array(
            'postalcode' =>  generate_unique_key(4).'４',
        );

        return array_merge($default, $params);
    }
}