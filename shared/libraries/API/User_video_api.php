<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * User Video Control API
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class User_video_api extends Base_api
{
    /**
     * Get list progressing of video API Spec UV-010
     *
     * @param array $params
     * @internal param int $offset
     * @internal param int $limit
     * @internal param int $user_id
     *
     * @return array
     */
    public function get_progressing($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->require_login();
        $v->set_rules('limit', '取得件数', 'integer');
        $v->set_rules('offset', '取得開始', 'integer');
        $v->set_rules('user_id', 'Usrer ID', 'integer|required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Set default offset limit
        $this->_set_default($params);

        // Load model
        $this->load->model('video_progress_model');
        $this->load->model('textbook_content_model');

        $d_id = [];

        // Get the video detail
        $res = $this->video_progress_model
            ->select('deck_video_inuse.deck_id')
            ->select('video.brightcove_id, video.id, video.brightcove_thumbnail_url, video.image_key, video.name, video.type, video.description')
            ->join('deck_video_inuse', 'deck_video_inuse.video_id = video_progress.video_id')
            ->join('video', 'video_progress.video_id = video.id')
            ->where('video_progress.user_id', $params['user_id'])
            ->is_done(FALSE)
            ->order_by('video_progress.updated_at', 'DESC')
            ->limit($params['limit'])
            ->offset($params['offset'])
            ->all();

        // Return null if not exist
        if(!$res) {
            return $this->true_json([
                'items' => [],
                'total' => 0
            ]);
        }

        // Read the deck_id
        foreach ($res as $v) {
            $d_id[$v->deck_id] = $v;
        }

        $order_video = sprintf('FIELD(textbook_content.deck_id, %s)', implode(', ', array_keys($d_id)));
        // Get the chapter info
        $res = $this->textbook_content_model
            ->calc_found_rows()
            ->select('textbook_content.deck_id, textbook_content.id, textbook_content.name, textbook_content.description, textbook_content.chapter_name')
            ->select('master_subject.short_name As subject_short_name, master_subject.color As subject_color')
            ->join('textbook', 'textbook.id = textbook_content.textbook_id')
            ->join('master_subject', 'textbook.subject_id = master_subject.id And master_subject.display_flag = 1')
            ->where_in('textbook_content.deck_id', array_keys($d_id))
            ->group_by('textbook_content.deck_id')
            ->order_by($order_video)
            ->all();

        // Return response
        return $this->true_json([
            'items' => $this->build_responses($res, ['videos' => $d_id]),
            'total' => $this->video_progress_model->found_rows()
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

        $video['video'] = [];

        if (isset($options['videos'][$res->deck_id])) {
            $v = $options['videos'][$res->deck_id];

            $video['video'] = $this->build_video_response($v);
            $video['video']['thumbnail_url'] = !empty($v->brightcove_thumbnail_url) ?
                $v->brightcove_thumbnail_url : null;

            // Image key is high priority than brightcove_thumbnail_url
            if (!empty($v->image_key)) {
                $video['video']['thumbnail_url'] = '/image/show/' . $v->image_key;
            }
        }

        return array_merge([
            'chapter' => [
                'id' => isset($res->id) ? (int) $res->id : null,
                'name' => isset($res->name) ? $res->name : null,
                'chapter_name' => isset($res->chapter_name) ? $res->chapter_name : null,
                'description' => isset($res->description) ? $res->description : null,
                'subject_short_name' => isset($res->subject_short_name) ? $res->subject_short_name : null,
                'subject_color' => isset($res->subject_color) ? $res->subject_color : null,
            ]
		], $video) ;
    }

}
