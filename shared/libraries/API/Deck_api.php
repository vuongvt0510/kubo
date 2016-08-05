<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Deck_api
 *
 * @property Deck_video_inuse_model deck_video_inuse_model
 * @property Video_question_timeline_model video_question_timeline_model
 * @property Deck_model deck_model
 * @property Deck_image_model deck_image_model
 *
 * @version $id$
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_api extends Base_api
{

    /**
     * Standard Validator Class
     *
     * @var string
     */
    public $validator_name = 'Deck_api_validator';

    /**
     * Get list of grade API Spec D-010
     *
     * @param array $params
     * @internal param array $deck_id
     * @param array $options
     *
     * @return array
     */
    public function get_detail($params = [], $options = [])
    {
        $options = array_merge([
            'get_questions' => TRUE
        ], $options);
        // Validate
        $v = $this->validator($params);

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('deck_model');
        $this->load->model('deck_video_inuse_model');
        $this->load->model('video_question_timeline_model');

        $ids = [];
        if (!is_array($params['deck_id'])) {
            $ids[] = $params['deck_id'];
        } else {
            $ids = $params['deck_id'];
        }

        // Get list of video_id
        $video = $this
            ->deck_video_inuse_model
            ->select('video.id, video.name, video.type, video.description, video.created_at, deck_video_inuse.deck_id')
            ->select('video.brightcove_id, video.brightcove_thumbnail_url, video.image_key')
            ->with_video()
            ->where_in('deck_video_inuse.deck_id', $ids)
            ->all();

        // Build video
        $d_video = [];
        $d_ids = [];
        $d_video_question = [];

        if ($video) {
            foreach ($video as $v) {
                $d_video[$v->deck_id] = $v;
                $d_ids[] = (int) $v->id;
            }

            // Get list of question related to deck
            if (!empty($d_ids) && $options['get_questions']) {
                $d_video_question = $this
                    ->video_question_timeline_model
                    ->with_question()
                    ->where_in('video_question_timeline.video_id', $d_ids)
                    ->all();
            }
        }

        // Get list of question related to deck

        $d_question = [];
        if ($options['get_questions']) {
            $question = $this
                ->deck_video_inuse_model
                ->with_question()
                ->where_in('deck_video_inuse.deck_id', $ids)
                ->all();

            // Build question

            if ($question) {
                foreach ($question as $v) {
                    $d_question[$v->deck_id][] = $v;
                }
            }
        }

        // Return
        return $this->true_json([
            'items' => $this->build_responses($ids, [
                    'videos' => $d_video,
                    'videos_questions' => $d_video_question,
                    'questions' => $d_question
                ]
            ),
            'total' => count($ids)
        ]);
    }

    /**
     * Get information of deck - Spec D-014
     * 
     * @param array $params
     * @internal param int $deck_id
     * 
     * @return array
     */
    public function get_infor($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('deck_id', 'デッキID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('deck_model');
        $this->load->model('deck_image_model');

        // Set query
        $res = $this->deck_model
            ->select('deck.id, deck.name, deck.description, deck.image_key, deck.coin')
            ->select('deck_category.id as category_id, deck_category.title as category_title')
            ->select('master_subject.short_name, master_subject.color, master_subject.type')
            ->with_category()
            ->with_subject()
            ->where('deck.id', $params['deck_id'])
            ->first();

        if (empty($res)) {
            return $this->false_json(self::BAD_REQUEST);
        }

        $result = [
            'id' => (int) $res->id,
            'name' => $res->name,
            'image_key' => $res->image_key,
            'description' => $res->description,
            'coin' => (int) $res->coin,
            'category' => [
                'id' => (int) $res->category_id,
                'title' => $res->category_title
            ],
            'subject' => [
                'short_name' => $res->short_name,
                'color' => $res->color,
                'type' => $res->type
            ],
            'deck_captures' => []
        ];

        // Get all deck capture image
        $capture_res = $this->deck_image_model
            ->where('deck_id', $params['deck_id'])
            ->all();

        foreach ($capture_res AS $capture) {
            $result['deck_captures'][] = $capture->image_key;
        }

        // Return
        return $this->true_json([
            'items' => $result
        ]);
    }
    /**
     * Get list of deck API Spec D-020
     *
     * @param array $params
     * @internal param int $category_id
     * @internal param int $package_id
     * @internal param int $grade_id
     * @internal param int $subject_id
     * @internal param string $sort_by
     * @internal param string $sort_position
     * @internal param int $offset Default: 0
     * @internal param int $limit Default: 20
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('category_id', 'カテゴリーID', 'integer');
        $v->set_rules('package_id', 'パッケージID', 'integer');
        $v->set_rules('grade_id', 'グレードID', 'integer');
        $v->set_rules('subject_id', 'デッキID', 'integer');
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('deck_model');

        // Set default limit
        $this->_set_default($params);

        // Filter by sort
        if(empty($params['sort_by']) || !in_array($params['sort_by'], ['id', 'category_id', 'package_id'])) {
            $params['sort_by'] = 'order';
        }

        // Filter by sort position
        if(empty($params['sort_position']) || !in_array($params['sort_position'], ['asc', 'desc'])) {
            $params['sort_position'] = 'asc';
        }

        // Set default query
        $this->deck_model
            ->calc_found_rows()
            ->select('deck.id, deck.name, deck.image_key, deck_package.subject_id, deck_package.grade_id, master_subject.short_name, master_subject.color')
            ->with_subject()
            ->order_by($params['sort_by'], $params['sort_position'])
            ->limit($params['limit'])
            ->offset($params['offset']);

        // Filter by package Id
        if (!empty($params['package_id'])) {
            $this->deck_model
                ->where('deck.package_id', $params['package_id']);
        }

        // Filter by category Id
        if (!empty($params['category_id'])) {
            $this->deck_model
                ->where('deck.category_id', $params['category_id']);
        }

        // Filter by grade Id
        if (!empty($params['grade_id'])) {
            $this->deck_model
                ->where('deck_package.grade_id', $params['grade_id']);
        }

        // Filter by subject Id
        if (!empty($params['subject_id'])) {
            $this->deck_model
                ->where('deck_package.subject_id', $params['subject_id']);
        }

        $res = $this->deck_model->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['list']),
            'total' => (int) $this->deck_model->found_rows()
        ]);
    }

    /**
     * Get list deck related by category - Spec D-011
     * 
     * @param  array  $params
     * @internal  param int $deck_id
     * @internal  param string $sort_by
     * @internal  param string $sort_position
     * 
     * @return array
     */
    public function get_related_category($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('deck_id', 'デッキID', 'required|integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Sort by param
        if (empty($params['sort_by'])) {
            $params['sort_by'] = 'deck.order';
        }

        // Sort position param
        if (empty($params['sort_position'])) {
            $params['sort_position'] = 'asc';
        }

        // Load model
        $this->load->model('deck_model');

        // Get category of deck
        $category_id = $this->deck_model
            ->select('category_id')
            ->where('deck.id', $params['deck_id'])
            ->first()
            ->category_id;

        // Filter
        $res = $this->deck_model
            ->calc_found_rows()
            ->select('deck.id, deck.name, deck.image_key, deck_category.title, master_subject.short_name, master_subject.color')
            ->with_subject()
            ->with_category()
            ->where('category_id', $category_id)
            ->where('deck.id !=', $params['deck_id'])
            ->order_by($params['sort_by'], $params['sort_position'])
            ->all();

        // Return
        return $this->true_json([
            'items' => $this->build_responses($res, ['categories']),
            'total' => (int) $this->deck_model->found_rows()
        ]);
    }

    /**
     * Get list deck related by subject - Spec D-012
     * 
     * @param  array  $params
     * @internal  param int $deck_id
     * @internal  param int $category_id
     * @internal  param string $sort_by
     * @internal  param string $sort_position
     * 
     * @return array
     */
    public function get_related_subject($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('deck_id', 'デッキID', 'integer');
        $v->set_rules('category_id', 'カテゴリーID', 'integer');

        // Run validate
        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Sort by param
        if (empty($params['sort_by'])) {
            $params['sort_by'] = 'deck.order';
        }

        // Sort position param
        if (empty($params['sort_position'])) {
            $params['sort_position'] = 'asc';
        }

        // Load model
        $this->load->model('deck_model');

        // Get subject of deck
        $subject_id = $this->deck_model
            ->select('deck_package.subject_id')
            ->with_package()
            ->group_by('deck_package.subject_id');

        // Filter deck_id
        if (!empty($params['deck_id'])) {
            $subject_id->where('deck.id', $params['deck_id']);
        }

        $subjects = '';
        foreach ($subject_id->all() as $key => $value) {
            $subjects[] = (int) $value->subject_id;
        }

        $subjects = implode(',', $subjects);

        // Filter
        $res = $this->deck_model
            ->calc_found_rows()
            ->select('deck_category.title, deck_category.id as category_id, deck.id, deck.name, deck.image_key, master_subject.short_name, master_subject.color')
            ->with_subject()
            ->with_category()
            ->where_in('deck_package.subject_id', $subjects, FALSE)
            ->where('deck.id !=', $params['deck_id']);

        // Filter category_id
        if (!empty($params['category_id'])) {
            $res = $res->where('deck_category.id !=', $params['category_id']);
        }

        $res = $res->order_by($params['sort_by'], $params['sort_position'])
            ->all();

        // Check not_found
        if (empty($res)) {
            return $this->false_json(self::NOT_FOUND);
        }

        // Build response
        $result = [];
        foreach ($res as $key => $value) {
            $decks = [];
            $flag = TRUE;

            foreach ($result as $k1 => $v1) {
                if ($v1['category']['id'] == $value->category_id) {
                    $flag = FALSE;
                    break;
                }
            }

            if ($flag) {
                foreach ($res as $k => $v) {
                    // Merge decks if deck have same category_id
                    if ($v->category_id == $value->category_id) {
                        $decks[] = [
                            'id' => (int) $v->id,
                            'name' => $v->name,
                            'image_key' => $v->image_key,
                            'subject' => [
                                'short_name' => $value->short_name,
                                'color' => $value->color,
                            ]
                        ];
                    }
                }

                $result[] = [
                    'category' => [
                        'id' => (int) $value->category_id,
                        'title' => $value->title,
                    ],
                    'decks' => $decks
                ];
            }
        }

        // Return
        return $this->true_json([
            'items' => $result,
            'total' => (int) $this->deck_model->found_rows()
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
    public function build_response($res, $options = [])
    {

        $result = [];

        // Build the video response
        if (isset($options['videos'][$res])) {
            $result['id'] = (int)$res;

            $video = $options['videos'][$res];
            $result['video'] = $this->build_video_response($video);
            $result['video']['thumbnail_url'] = !empty($video->brightcove_thumbnail_url) ?
                $video->brightcove_thumbnail_url : null;

            // Image key is high priority than brightcove_thumbnail_url
            if (!empty($video->image_key)) {
                $result['video']['thumbnail_url'] = '/image/show/' . $video->image_key;
            }

            if (is_array($options['videos_questions'])) {
                foreach ($options['videos_questions'] AS $v) {
                    if ($video->id != $v->video_id) {
                        continue;
                    }

                    $ans = json_decode($v->data, TRUE);

                    // Shuffle answer
                    if ((isset($ans['random']) && $ans['random'] == 'on') || !isset($ans['random'])) {
                        shuffle($ans['answers']);
                    }

                    $result['video']['questions'][] = array_merge($ans, [
                        'id' => isset($v->id) ? (int) $v->id : null,
                        'second' => isset($v->second) ? (float) $v->second : null,
                        'type' => isset($v->type) ? $v->type : null
                    ]);
                }
            }

        } else {
            $result['video'] = [];
        }

        // Build the question response
        if (isset($options['questions'][$res])) {
            $result['id'] = (int)$res;

            $q = $options['questions'][$res];
            foreach ($q as $v) {
                $ans = json_decode($v->data, TRUE);

                // Shuffle answer
                if (!empty($ans['answers'])) {
                    if ((isset($ans['random']) && $ans['random'] == 'on') || !isset($ans['random'])) {
                        shuffle($ans['answers']);
                    }
                }

                $result['questions'][] = array_merge($ans, [
                    'id' => isset($v->id) ? (int) $v->id : null,
                    'type' => isset($v->type) ? $v->type : null
                ]);
            }

        } else {
            $result['questions'] = [];
        }

        // Build the related_subject response
        if (isset($options['decks'][$res])) {
            $result = [];
            foreach ($res->all() as $key => $value) {
                $decks = [];
                $flag = TRUE;

                foreach ($result as $k => $v) {
                    if ($v['category_id'] == $value->category_id) {
                        $flag = FALSE;
                        break;
                    }
                }

                if ($flag) {

                    foreach ($res->all() as $k => $v) {
                        // Merge decks if deck have same category_id
                        if ($v->category_id == $value->category_id) {
                            $decks[] = array(
                                'id' => (int) $v->id,
                                'name' => $v->name,
                            );
                        }
                    }

                    $result[] = array(
                        'title' => $value->title,
                        'category_id' => (int) $value->category_id,
                        'decks' => $decks
                    );
                }
            }
        }

        // Build the categories response
        if (in_array('categories', $options)) {
            $result = [];

            $result['id'] = $res->id;
            $result['name'] = $res->name;
            $result['image_key'] = $res->image_key;
            $result['category']['title'] = $res->title;
            $result['subject']['short_name'] = $res->short_name;
            $result['subject']['color'] = $res->color;
        }

        // Build the list deck response
        if (in_array('list', $options)) {
            $result = [];

            $result['id'] = $res->id;
            $result['name'] = $res->name;
            $result['image_key'] = $res->image_key;
            $result['grade_id'] = $res->grade_id;
            $result['subject']['id'] = $res->subject_id;
            $result['subject']['short_name'] = $res->short_name;
            $result['subject']['color'] = $res->color;
        }
        
        // Build the list response
        if (empty($options) && !empty($res)) {
            $result = get_object_vars($res);
        }

        return $result;
    }

}

/**
 * Class Deck_api_validator
 *
 * @property Deck_api $base
 */
class Deck_api_validator extends Base_api_validation
{
    /**
     * Validate type
     *
     * @param int $deck_id
     *
     * @return bool
     */
    function valid_deck_id($deck_id)
    {

        $is_type = TRUE;

        $deck_id = explode(',', $deck_id);

        foreach ($deck_id as $k) {
            if (!is_numeric($k)) {
                $is_type = FALSE;
            }
        }

        if (!$is_type) {
            $this->set_message('valid_deck_id', 'デッキIDが間違っています');
            return FALSE;
        }

        return TRUE;
    }
}
