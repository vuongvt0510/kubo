<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Video_rabi_count_api
 *
 * @property object html_purifier
 * @version $id$
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Video_rabi_count_api extends Base_api
{
    /**
     * Standard Validator Class
     * @var string
     */
    public $validator_name = 'Video_rabi_count_validator';

    /**
     * Get List rabbit count
     *
     * @param array $params
     * @internal param $video_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Validate params
        $v = $this->validator($params);
        $v->set_rules('video_id', '動画ID', 'required|valid_video_id');

        if (FALSE === $v->run()) {
            // Return errors
            return $v->error_json();
        }

        $params['user_id'] = $this->operator()->id;

        // Load model
        $this->load->model('video_rabi_count_model');

        // Build default query
        $res = $this->video_rabi_count_model
            ->calc_found_rows()
            ->select('
            video_rabi_count.id,
            video_rabi_count.button_id,
            video.name as video_name,
            u.nickname as user_name,
            count(video_rabi_count.id) as count,
            video_rabi_count.created_at,
            video_rabi_count.updated_at
            ')
            ->join('video', 'video.id = video_rabi_count.video_id')
            ->join('schooltv_main.user as u', 'u.id = video_rabi_count.user_id')
            ->where('video.id', $params['video_id'])
            ->where('u.id', $params['user_id'])
            ->group_by('video_id, button_id')
            ->all();

        $result = [];
        if (!empty($res)) {
            foreach ($res as $record) {
                $result[] = $this->build_response($record);
            }
        }

        // Return
        return $this->true_json([
            'items' => $result,
            'total' => (int)$this->video_rabi_count_model->found_rows()
        ]);
    }

    /**
     * Created new count
     *
     * @param array $params
     * @internal param int video_id Video Id
     * @internal param int button_id Button Id
     * @internal param int second Second
     *
     * @return array
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        //$v->require_login();
        $v->set_rules('video_id', 'ビデオID', 'required|integer|valid_video_id');
        $v->set_rules('button_id', 'ボタンID', 'required|integer');
        $v->set_rules('second', '秒', 'required|integer');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('video_rabi_count_model');

        $condition = [
            'user_id' => isset($this->operator()->id) ? $this->operator()->id : 0,
            'video_id' => $params['video_id'],
            'button_id' => $params['button_id'],
            'second' => $params['second']
        ];

        // Start transaction
        $this->video_rabi_count_model->transaction(function () use (&$result, $condition) {
            // Insert data
            $result = $this->video_rabi_count_model->create($condition);
        });

        // Return error if user does not exist
        if (!$result) {
            return $this->false_json([]);
        }

        // Return
        return $this->true_json([
            'submit' => TRUE
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
    protected function build_response($res, $options = [])
    {

        if (!$res) {
            return [];
        }

        return [
            'id' => isset($res->id) ? (int)$res->id : NULL,
            'video_name' => !empty($res->video_name) ? $res->video_name : NULL,
            'button_id' => !empty($res->button_id) ? $res->button_id : NULL,
            'button_name' => !empty($res->button_name) ? $res->button_name : NULL,
            'user_id' => !empty($res->user_id) ? $res->user_id : NULL,
            'user_name' => !empty($res->user_name) ? $res->user_name : NULL,
            'count' => $res->count,
            'created_at' => $res->created_at,
            'updated_at' => $res->updated_at
        ];
    }
}

class Video_rabi_count_validator extends Base_api_validation
{
    public function valid_video_id($video_id)
    {
        // Load model
        $this->base->load->model('video_model');

        // Get the user ID
        $video = $this->base->video_model
            ->find($video_id);

        // If existing return error
        if (!$video) {
            $this->set_message('valid_video_id', '動画IDが存在しません');
            return FALSE;
        }

        return TRUE;
    }
}
