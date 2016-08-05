<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Textbook_content_builder
{
    public function builder($params = array()){
        $default = array(
            //'textbook_id' => 2,
            //'deck_id' => 2,
            'chapter_name' => 'chapter_name',
            'name' => 'p_name',
            'order' => 11,
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
        );

        return array_merge($default, $params);
    }
}