<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Deck_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Deck_model extends APP_Model
{
    public $database_name = DB_CONTENT;
    public $table_name = 'deck';
    public $primary_key = 'id';

    /**
     * fetch with with_buying
     *
     * @access public
     * @return Deck_model
     */
    public function with_buying()
    {
        return $this->join(DB_MAIN. '.user_buying', 'deck.id = '.DB_MAIN. '.user_buying.target_id')
                    ->where(DB_MAIN. '.user_buying.type', 'deck');
    }

    /**
     * fetch with with_package
     *
     * @access public
     * @return Deck_model
     */
    public function with_package()
    {
        return $this->join('deck_package', 'deck.package_id = deck_package.id');
    }

    /**
     * fetch with with_category
     *
     * @access public
     * @return Deck_model
     */
    public function with_category()
    {
        return $this->join('deck_category', 'deck.category_id = deck_category.id');
    }

    /**
     * fetch with with_subject
     *
     * @access public
     * @return Deck_model
     */
    public function with_subject()
    {
        return $this->join('deck_package', 'deck.package_id = deck_package.id')
                    ->join(DB_MAIN. '.master_subject', 'deck_package.subject_id = '.DB_MAIN. '.master_subject.id');
    }

    /**
     * fetch with with_image
     *
     * @access public
     * @return Deck_model
     */
    public function with_image()
    {
        return $this->join('deck_image', 'deck.id = deck_image.deck_id', 'left');
    }

    /**
     * fetch with with_play_stage
     *
     * @access public
     * @return Deck_model
     */
    public function with_play_stage()
    {
        return $this->join('stage', 'deck.id = stage.deck_id')
                    ->join(DB_MAIN. '.user_playing_stage', 'stage.id = '.DB_MAIN.'.user_playing_stage.stage_id', 'left');
    }

    /**
     * fetch with with_stage
     *
     * @access public
     * @return Deck_model
     */
    public function with_stage()
    {
        return $this->join('stage', 'deck.id = stage.deck_id');
    }

    /**
     * fetch with with_question
     *
     * @access public
     * @return Deck_model
     */
    public function with_question()
    {
        return $this->join('question', 'deck.id = question.deck_id');
    }

    /**
     * fetch with get_infor
     *
     * @access public
     * @return Deck_model
     */
    public function get_infor($deck_id)
    {
        $res = $this->select("deck.id, deck.name, deck.description, deck.image_key, deck.coin,
                deck_category.id as category_id, deck_category.title as category_title,
                master_subject.short_name, master_subject.color, master_subject.type,
                deck_image.image_key as deck_capture")
            ->with_image()
            ->with_category()
            ->with_subject()
            ->where('deck.id', $deck_id)
            ->first();

        return $res;
    }
}
