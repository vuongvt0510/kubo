<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Sitemap controller
 *
 * @author Duy Phan <duy.phan@interest-marketing.net>
 */
class Sitemap extends Application_controller
{
    public $layout = "layouts/base";

//    public function index()
//    {
//        return $this->_render_404();
//
//        // Get the Grade List
//        $grade =  $this->_api('grade')->get_list();
//
//        $result = [];
//        foreach($grade['result']['items'] as $k) {
//            // Get the subject list
//            $subject_list = $this->_api('subject')->get_list([
//                'grade_id' => $k['id']
//            ]);
//
//            $s_list = [];
//            foreach ($subject_list['result']['items'] as $s) {
//                $s_list[] = $s['id'];
//            }
//
//            // Get the most popular subject
//            $most_popular_subject = $this->_api('video_textbook')->get_most_popular_for_sitemap([
//                'subject_id' => implode(',', $s_list)
//            ]);
//
//            // Sort the result
//            usort($most_popular_subject['result']['items'], function($a, $b) {
//                return $b['count'] - $a['count'];
//            });
//
//            $most_subject = [];
//            foreach($most_popular_subject['result']['items'] as $sj) {
//                $most_subject[$sj['subject']['id']][] = $sj;
//            }
//
//            $result[$k['name']] = $most_subject;
//        }
//
//        $this->_render(['result' => $result]);
//    }

    public function download($file)
    {

    }

//    public function db10() {
//        $this->_render();
//    }
}