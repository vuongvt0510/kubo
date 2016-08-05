<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Video_textbook_api
 *
 * @property Deck_model deck_model
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Video_textbook_api extends Base_api
{

    /**
     * Search video list textbook API Spec TB-020
     *
     * @param array $params
     * @internal param int $grade_id
     * @internal param int $subject_id
     * @internal param int $offset number of record
     * @internal param int $limit number of max record
     *
     * @return array
     */
    public function search($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('grade_id', '学年ID', 'integer');
        $v->set_rules('subject_id', '教科ID', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('textbook_model');

        // Set default offset , limit
        $this->_set_default($params);

        // Add the grade_id condition
        if (isset($params['grade_id'])) {
            $this->textbook_model->where('master_grade.id', $params['grade_id']);
        }

        // Add the subject_id condition
        if (isset($params['subject_id'])) {
            $this->textbook_model->where('master_subject.id', $params['subject_id']);
        }

        // Get  textbook info
        $res = $this->textbook_model
            ->calc_found_rows()
            ->select('textbook.id, textbook.name')
            ->with_textbook_cache()
            ->with_master_subject()
            ->with_master_grade()
            ->order_by('cache_textbook_count.count', 'DESC')
            ->limit($params['limit'])
            ->offset($params['offset'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->textbook_model->found_rows()
        ]);
    }

    /**
     * Get the textbook chapters API Spec TB-021
     *
     * @param array $params
     * @internal param int $textbook_id Get chapter list by Textbook ID
     * @internal param string $chapter_name
     *
     * @return array
     */
    public function get_chapter($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('textbook_id', '教科書ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // If there is no params to find return
        if (empty($params['textbook_id']) && empty($params['chapter_name'])) {
            return $this->false_json(self::BAD_REQUEST);
        }

        // Load model
        $this->load->model('textbook_content_model');

        // Add the textbook_id condition
        if (!empty($params['textbook_id'])) {
            $this->textbook_content_model->where(
                'textbook_content.textbook_id', $params['textbook_id']
            );
        }

        // Add the textbook_name condition
        if (!empty($params['chapter_name'])) {
            $this->textbook_content_model->where(
                'textbook_content.chapter_name', $params['chapter_name']
            );
        }

        // Get textbook info
        $res = $this->textbook_content_model
            ->calc_found_rows()
            ->select('textbook_content.id, textbook_content.textbook_id, textbook_content.name, textbook_content.chapter_name ')
            ->select('textbook_content.description, textbook_content.order, textbook_content.deck_id, schooltv_content.deck_video_inuse.video_id')
            ->join('schooltv_content.deck_video_inuse', 'schooltv_content.deck_video_inuse.deck_id = textbook_content.deck_id', 'left')
            ->order_by('textbook_content.order', 'ASC')
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['chapter_detail']),
            'total' => (int) $this->textbook_content_model->found_rows()
        ]);
    }

    /**
     * Get the chapter detail API Spec TB-025
     *
     * @param array $params
     * @internal param int $chapter_id Get chapter detail by Chapter ID
     * @return array
     */
    public function get_chapter_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('chapter_id', '単元ID', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('textbook_content_model');

        // Get textbook info
        $res = $this->textbook_content_model
            ->calc_found_rows()
            ->select('textbook_content.id, textbook_content.textbook_id, textbook_content.name, textbook_content.chapter_name ')
            ->select('textbook_content.description, textbook_content.order, textbook_content.deck_id')
            ->where('textbook_content.id', (int) $params['chapter_id'])
            ->first();

        // Return
        return $this->true_json($this->build_responses($res, [
            'chapter_detail'
        ]));
    }



    /**
     * Get most popular for site map
     * TODO : This is temporary API, will remove it after check
     *
     * @param array $params
     * @internal param string $subject_id
     *
     * @return array
     */
    public function get_most_popular_for_sitemap($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('subject_id', '教科書ID', 'required|valid_multiple_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('textbook_model');

        if(isset($params['textbook_id'])) {
            $this->textbook_model->where_in('textbook.id', $params['textbook_id']);
        }

        // Get textbook info
        $textbook = $this->textbook_model
            ->join('cache_textbook_count', 'cache_textbook_count.textbook_id = textbook.id', 'left')
            ->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->select('cache_textbook_count.count')
            ->where_in('master_subject.id', explode(',', $params['subject_id']))
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($textbook, ['subject_detail', 'school_count']),
            'total' => count($textbook),
        ]);
    }


    /**
     * Get most popular Textbook API Spec TB-021
     *
     * @param array $params
     * @internal param string $subject_id
     *
     * @return array
     */
    public function get_most_popular($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('subject_id', '教科書ID', 'required|valid_multiple_id');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('textbook_model');

        if(isset($params['textbook_id'])) {
            $this->textbook_model->where_in('textbook.id', $params['textbook_id']);
        }

        // Get textbook info
        $textbook = $this->textbook_model
            ->join('cache_textbook_count', 'cache_textbook_count.textbook_id = textbook.id', 'left')
            ->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->select('cache_textbook_count.count')
            ->where_in('master_subject.id', explode(',', $params['subject_id']))
            ->all();

        // Build the textbook
        $res = [];
        if($textbook) {
            foreach ($textbook as $k) {

                // Get the most popular textbook by subject_id
                if(isset($res[$k->subject_id])) {
                    if($res[$k->subject_id]->count < $k->count) {
                        $res[$k->subject_id] = $k;
                    }
                    continue;
                }

                $res[$k->subject_id] = $k;
            }

            // Sort the most popular subject first
            usort($res, function($a, $b) {
                return $b->count - $a->count;
            });
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['subject_detail', 'school_count']),
            'total' => count($res),
        ]);
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = []){

        if(!$res) {
            return [];
        }

        $chapter = [];
        if(in_array('chapter_detail', $options)){
            $chapter = $this->build_chapter_response($res);
        }

		$subject = [];
		if(in_array('subject_detail', $options)) {
            $result = $this->build_user_textbook_response($res);
            if(in_array('subject_detail', $options)) {
                $result['count'] = $res->count;
            }

			return $result;
		}

        return array_merge([
            'textbook_id' => isset($res->textbook_id) ? (int) $res->textbook_id : null,
            'name' => isset($res->name) ? $res->name : null
        ], $chapter, $subject);
    }

}
