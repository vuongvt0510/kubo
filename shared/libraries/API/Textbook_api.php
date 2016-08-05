<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Textbook_api
 *
 * @property Master_textbook_inuse_model master_textbook_inuse_model
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Textbook_api extends Base_api
{

    /**
     * Textbook search API Spec TB-010
     *
     * @param array $params
     * @internal param string $keyword search keyword
     * @internal param int $offset number of record
     * @internal param int $limit number of max record
     *
     * @return array
     */
    public function search($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');
        $v->set_rules('grade_id', '学年ID', 'integer');
        $v->set_rules('subject_id', '教科ID', 'integer');

        // Use when search_keyword
        if (isset($params['keyword'])) {
            $v->set_rules('keyword', 'キーワード', 'required|max_length[128]');
        }

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset , limit
        $this->_set_default($params);

        // Load model
        $this->load->model('textbook_model');
        $this->load->model('master_textbook_inuse_model');

        // Set sort order for textbook
        (isset($params['most_view']) && TRUE == $params['most_view']) ?
            $this->textbook_model->with_textbook_cache()->order_by('cache_textbook_count.count', 'DESC') :
            $this->textbook_model->order_by('master_subject.name', 'ASC');

        // Set the default for $keyword
        if (!isset($params['keyword'])) {
            $params['keyword'] = NULL;
        }

        // Get textbook belong to school
        if (isset($params['school_id'])) {
            $res = $this->master_textbook_inuse_model->calc_found_rows()->search($params);
            $total = $this->master_textbook_inuse_model->found_rows();
        } else {
            // Get textbook info
            $res = $this->textbook_model->calc_found_rows()->search($params);
            $total = $this->textbook_model->found_rows();
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $total
        ]);
    }

    /**
     * Textbook search API Spec TB-015
     *
     * @param array $params
     * @internal param int $subject_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('subject_id', '教科書ID', 'integer');
        $v->set_rules('grade_id', '教科書ID', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset , limit
        $this->_set_default($params);

        // Load model
        $this->load->model('textbook_model');

        if(isset($params['subject_id'])) {
            $this->textbook_model ->where('master_subject.id', $params['subject_id']);
        }
        if(isset($params['grade_id'])) {
            $this->textbook_model ->where('master_grade.id', $params['grade_id']);
        }
        if(isset($params['type'])) {
            $this->textbook_model ->where('master_subject.type', $params['type']);
        }

        $res = $this
            ->textbook_model
            ->calc_found_rows()
            ->with_master_subject()
            ->with_master_grade()
            ->with_publisher()
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res),
            'total' => (int) $this->textbook_model->found_rows()
        ]);
    }

    /**
     * Textbook get detail API Spec TB-010
     *
     * @param array $params
     * @internal param int $textbook_id Get textbook details by Textbook ID
     *
     * @return array
     */
    public function get_detail($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('textbook_id', '教科書ID', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('textbook_model');

        /** @var object $res */
        $res = $this->textbook_model
            ->with_publisher()
            ->with_master_subject()
            ->with_master_grade()
            ->find($params['textbook_id']);

        return $this->true_json($this->build_responses($res));
    }

    /**
     * Build the API Response
     *
     * @param object $res
     * @param array $options
     *
     * @return array
     */
    public function build_response($res, $options = [])
    {
        if (!$res) {
            return [];
        }

        return $this->build_user_textbook_response($res, $options = []);
    }

}
