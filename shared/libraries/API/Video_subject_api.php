<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Video Subject API
 *
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Video_subject_api extends Base_api
{

    /**
     * Get list of video API Spec VD-030
     *
     * @param array $params
     * @internal param array $subject_id
     *
     * @return array
     */
    public function get_list($params = [])
    {
        // Load model
        $this->load->model('video_model');
        $this->load->model('textbook_model');
        $this->load->model('textbook_content_model');

        $this->_set_default($params);

        // Get the textbook detail
        $chapters = $this->textbook_model->select('textbook.id')
            ->with_master_subject()
            ->with_master_grade()
            ->with_textbook_content()
            ->where_in('subject_id', $params['subject_id'])
            ->all();

        // Return null if not exist
        if(!$chapters) {
            return $this->true_json([
                'items' => [],
                'total' => 0
            ]);
        }

        // Read the deck_id
        $decks = [];
        $subjects = [];
        $res = [];
        foreach ($chapters as $v) {
            $decks[$v->subject_id][] = $v->deck_id;
            $res[$v->deck_id] = $v;
            $subjects[$v->subject_id] = $v->subject_id;
        }

        // Return response
        return $this->true_json([
            'items' => $this->build_responses($subjects, [
                'decks' => $decks,
                'limit' => $params['limit'],
                'res' => $res
            ])
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

        $result = [];
        if(isset($options['decks'][$res])) {
            $videos = $this->video_model->limit($options['limit'])->get_most_viewer($options['decks'][$res]);

            $result['id'] = (int) $res;
            foreach($videos as $k => $v) {
                $result['popular'][$k]['video'] = $this->build_video_response($v);
                $result['popular'][$k]['video']['thumbnail_url'] = !empty($v->brightcove_thumbnail_url) ?
                    $v->brightcove_thumbnail_url : null;

                // Image key is high priority than brightcove_thumbnail_url
                if (!empty($v->image_key)) {
                    $result['popular'][$k]['video']['thumbnail_url'] = '/image/show/' . $v->image_key;
                }
                $result['popular'][$k]['chapter'] = $this->build_chapter_response($options['res'][$v->deck_id]);
            }

        }

        return $result;
    }

}

