<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Deck_video_inuse_builder
{
    public function builder($params = array()){
        $default = array(
           // 'deck_id' => 'name',
           // 'video_id' => 'description',
        );

        return array_merge($default, $params);
    }
}