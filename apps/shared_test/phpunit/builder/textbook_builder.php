<?php
/**
 * Created by PhpStorm.
 * User: DungNguyen
 * Date: 12/3/2015
 * Time: 9:38 AM
 */
class Textbook_builder
{
    public function builder($params = array()){
        $default = array(
            //'doc_id' => 2,
            //'publisher_id' => 2,
            //'subject_id' => 2,
            'name' => 'p_name',
            'created_at' => date('Y-m-d h:i:s'),
            'created_by' => 'test',
            'updated_at' => date('Y-m-d h:i:s'),
            'updated_by' => 'test',
        );

        return array_merge($default, $params);
    }
}