<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Stage_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Stage_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'stage';
    public $primary_key = 'id';

    /**
     * fetch with with_play
     *
     * @access public
     * @return Stage_model
     */
    public function with_play()
    {
        return $this->join('deck', 'deck.id = stage.deck_id')
                    ->join(DB_MAIN. '.user_playing_stage', 'stage.id = '.DB_MAIN.'.user_playing_stage.stage_id');
    }

    /**
     * fetch with with_group_play
     *
     * @access public
     * @return Stage_model
     */
    public function with_group_play()
    {
        return $this->join('deck', 'deck.id = stage.deck_id')
                    ->join(DB_MAIN. '.user_group_playing', DB_MAIN.'.user_group_playing.target_id = stage.id')
                    ->join(DB_MAIN. '.group_playing', DB_MAIN.'.group_playing.id = '.DB_MAIN.'.user_group_playing.group_playing_id');
    }
}
