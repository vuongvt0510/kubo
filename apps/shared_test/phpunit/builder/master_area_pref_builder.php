<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Master_area_pref_builder
{
    public function builder($params = array()){
        $default = array(
            //'group_id' => 1,
            'name' =>  generate_unique_key(8),
        );

        return array_merge($default, $params);
    }
}