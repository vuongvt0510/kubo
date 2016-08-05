<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Cache_textbook_count_builder
{
    public function builder($params = array()){
        $default = array(
            'count' => rand(3,10),
        );

        return array_merge($default, $params);
    }
}