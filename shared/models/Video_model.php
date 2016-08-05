<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Video_model
 */
class Video_model extends APP_Paranoid_model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'video';
    public $primary_key = 'id';

    const TYPE_VIDEO = 'video';
    const TYPE_ACTIVE_LEARNING_VIDEO = 'active_learning';

    /**
     * Get most viewer v
     *
     * @access public
     * @param array $deck_id
     *
     * @return object|bool
     */
    function get_most_viewer($deck_ids){

        // Get most viewer video
        return $this->video_model
            ->select('deck.id as deck_id, video.id, video.name, video.description, video.brightcove_id, video.type')
            ->select('video.image_key, video.brightcove_thumbnail_url')
            ->join('deck_video_inuse', 'deck_video_inuse.video_id = video.id')
            ->join('deck', 'deck.id = deck_video_inuse.deck_id')
            ->join('video_view_count', 'video_view_count.video_id = video.id', 'left')
            ->where_in('deck.id', $deck_ids)
            ->order_by('video_view_count.count', 'DESC')
            ->all();
    }

    /**
     * Get most viewer v
     *
     * @access public
     * @param array $deck_id
     *
     * @return object|bool
     */
    function get_video_detail($video_id){
        // Get deck
        return $this->video_model
            ->select('deck.id as deck_id, video.id, video.name, video.description')
            ->select('video.image_key, video.brightcove_thumbnail_url, video.type')
            ->join('deck_video_inuse', 'deck_video_inuse.video_id = video.id')
            ->join('deck', 'deck.id = deck_video_inuse.deck_id')
            ->where('video.id', $video_id)
            ->first();
    }
}
