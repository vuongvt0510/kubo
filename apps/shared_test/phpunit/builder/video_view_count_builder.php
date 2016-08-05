<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Video_view_count_builder
{
    public function builder($params = array()){
        $default = array(
            'count' => 1,
        );

        return array_merge($default, $params);
    }
}