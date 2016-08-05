<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Timeline_model
 *
 * @property User_buying_model user_buying_model
 * @property User_playing_stage_model user_playing_stage_model
 * @property User_video_count_second_model user_video_count_second_model
 * @property User_model user_model
 * @property Deck_model deck_model
 * @property Stage_model stage_model
 * @property Trophy_model trophy_model
 * @property User_trophy_model user_trophy_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Timeline_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'timeline';
    public $primary_key = 'id';

    /**
     * Create timeline
     *
     * @param string $timeline_key
     * @param string $type
     * @param int $target_id
     * @param string $title
     *
     * @return array|bool
     */
    public function create_timeline($timeline_key = '', $type = '', $target_id = null, $title = null, $play_id = null, $play_type = null)
    {
        $return = FALSE;

        if ($this->operator()->primary_type == 'parent') {
            return $return;
        }

        $current_user_id = $this->operator()->id;

        switch ($timeline_key) {

            case 'deck_downloads':
                $this->load->model('user_buying_model');

                $total_res = $this->user_buying_model
                    ->select('id')
                    ->where('user_id', $current_user_id)
                    ->where('type', 'deck')
                    ->all();

                $total = empty($total_res) ? 0 : (int) count($total_res);

                if ($total) {
                    $total = $total - ($total % 10);
                }

                $return = $this->create_trophy($timeline_key, $type, 'special', $total);

                break;

            case 'play_day':

                $this->load->model('user_playing_stage_model');

                $plays = $this->user_playing_stage_model
                    ->select('created_at')
                    ->where('user_id', $current_user_id)
                    ->order_by('created_at', 'desc')
                    ->all();

                $play_time = [];

                foreach ($plays as $play) {
                    $play_time[business_date('Y-m-d', strtotime($play->created_at))] = business_date('Y-m-d', strtotime($play->created_at));
                }

                $count = 1;
                foreach ($play_time as $item) {
                    if (isset($play_time[business_date('Y-m-d', strtotime('-24 hours', strtotime($item)))])) {
                        $count++;
                    } else {
                        break 1;
                    }
                }

                $return = $this->create_trophy($timeline_key, $type, 'special', $count - ($count % 10));

                break;

            case 'play_minute':

                $this->load->model('user_playing_stage_model');

                $playing_second = $this->user_playing_stage_model
                    ->select('SUM(second) as total_second')
                    ->where('user_id', $current_user_id)
                    ->first();

                $playing_minutes = (int) ($playing_second->total_second / 60);

                $return = $this->create_trophy($timeline_key, $type, 'special', $playing_minutes - ($playing_minutes % 60));

                break;

            case 'video_minute':

                $this->load->model('user_video_count_second_model');

                $video_second = $this->user_video_count_second_model
                    ->select('second')
                    ->where('user_id', $this->operator()->id)
                    ->first();

                $video_minutes = (int) ($video_second->second / 60);

                $return = $this->create_trophy($timeline_key, $type, 'special', $video_minutes - ($video_minutes % 60));

                break;

            case 'deck_download_timeline':

                $this->load->model('deck_model');

                // Set query
                $deck = $this->deck_model
                    ->select('deck.name')
                    ->where('deck.id', $target_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $target_id, 'ドリルをゲット', $deck->name.'をゲットしました', 'badge_design11.png');

                break;

            case 'play_deck':

                $this->load->model('stage_model');

                // Set query
                $deck = $this->stage_model
                    ->select('deck.name, deck.id')
                    ->join('deck', 'deck.id = stage.deck_id')
                    ->where('stage.id', $target_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $deck->id, 'ドリルをプレイ', $deck->name.'をプレイしました', 'badge_design12.png', $play_id, $play_type);

                break;

            case 'achieve_ranking' :

                $this->load->model('user_model');
                $this->load->model('deck_model');

                $ranking = $this->user_model->get_ranking_position('global', $current_user_id);

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id)
                    ->first();

                if ($ranking->rank < 101) {
                    $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, 'ランキング' . $ranking->rank . '位', $stage->name . 'で' . $ranking->rank . '位になりました', 'badge_design14.png');
                }

                break;

            case 'higher_ranking' :

                $this->load->model('user_model');
                $this->load->model('stage_model');

                $ranking = $this->user_model->get_ranking_position('global', $current_user_id);

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, 'ランキング上昇！' . $ranking->rank . '位', $stage->name . 'で' . $ranking->rank . '位になりました', 'badge_design15.png');

                break;

            case 'play_score' :

                $this->load->model('stage_model');

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id->stage_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, 'スコア' . $target_id->score . '点', $stage->name.'で' . $target_id->score . '点獲得しました', 'badge_design16.png');

                break;

            case 'higher_score' :

                $this->load->model('stage_model');

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id->stage_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, 'ハイスコア記録！' . $target_id->score . '点', $stage->name.'でハイスコアを記録しました', 'badge_design17.png');

                break;

            case 'make_friend_timeline' :

                $this->load->model('user_model');

                $target = $this->user_model->find_by([
                    'id' => $target_id
                ]);

                // Create timeline for target_id
                $this->create([
                    'user_id' => $target_id,
                    'type' => $type,
                    'target_id' => $this->operator()->id,
                    'extra_data' => json_encode([
                        'timeline_key' => $timeline_key,
                        'name' => $target->nickname.'と'.$this->operator()->nickname.'が友達になりました',
                        'description' => '',
                        'type' => 'timeline',
                        'category' => $timeline_key,
                        'image_key' => 'badge_design18.png'
                    ])
                ]);

                $return = $this->create_timeline_record($timeline_key, $type, $target_id, $this->operator()->nickname.'と'.$target->nickname.'が友達になりました', '', 'badge_design18.png');

                break;

            case 'video_timeline' :

                $return = $this->create_timeline_record($timeline_key, $type, $target_id, '動画で学習しました', $title.'を学習しました', 'badge_design19.png', $play_id, $play_type);

                break;

            case 'memorization_done' :

                $this->load->model('stage_model');

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, 'ステージ全てを覚えました', $stage->name.'を全てを覚えました', 'badge_design24.png');

                break;

            case 'memorization' :

                $this->load->model('stage_model');

                // Set query
                $stage = $this->stage_model
                    ->select('name, deck_id')
                    ->where('id', $target_id)
                    ->first();

                $return = $this->create_timeline_record($timeline_key, $type, $stage->deck_id, '覚えたチェックをしました', $stage->name.'の覚えたチェックをしました', 'badge_design24.png', $play_id, $play_type);

                break;

            default:
                $return = $this->create_trophy($timeline_key, $type, 'tutorial');
                break;
        }

        return is_object($return) ? get_object_vars($return) : $return ;
    }

    /**
     * @param $timeline_key
     * @param $type
     * @param $trophy_type (tutorial, special)
     * @param null $trophy_target_id
     * @return bool
     * @throws Exception
     */
    private function create_trophy($timeline_key, $type, $trophy_type, $trophy_target_id = null) {

        $this->load->model('trophy_model');
        $this->load->model('user_trophy_model');

        if (isset($trophy_target_id)) {
            $this->trophy_model->where('trophy.target_id', $trophy_target_id);
        }

        $trophy = $this->trophy_model
            ->select('trophy.id, trophy.name, trophy.description, trophy.type, trophy.category, trophy.image_key, trophy.target_id, trophy.created_at')
            ->select('user_trophy.user_id')
            ->join('user_trophy', 'user_trophy.trophy_id = trophy.id AND user_trophy.user_id = '.$this->operator()->id, 'left')
            ->where('trophy.category', $timeline_key)
            ->where('trophy.type', $trophy_type)
            ->first();

        if (empty($trophy->user_id) && !empty($trophy->id)) {

            // Create timeline
            $this->create([
                'user_id' => $this->operator()->id,
                'type' => $type,
                'target_id' => $trophy->id,
                'extra_data' => json_encode([
                    'timeline_key' => $timeline_key,
                    'name' => $trophy->name,
                    'description' => $trophy->description,
                    'trophy_type' => $trophy_type,
                    'category' => $timeline_key,
                    'image_key' => $trophy->image_key
                ])
            ]);

            // Create user trophy
            $this->user_trophy_model->create([
                'user_id' => $this->operator()->id,
                'trophy_id' => $trophy->id
            ], [
                'mode' => 'replace'
            ]);
        }

        return empty($trophy->user_id) && !empty($trophy->id) ? $trophy : FALSE;
    }

    /**
     * Create record of timeline type
     *
     * @param string $timeline_key
     * @param string $type
     * @param null $target_id
     * @param string $name
     * @param string $description
     * @param string $image
     *
     * @return bool
     *
     * @throws Exception
     */
    private function create_timeline_record($timeline_key = '', $type = '', $target_id = null, $name = '', $description = '', $image = '', $play_id = '', $play_type = '') {

        $this->create([
            'user_id' => $this->operator()->id,
            'type' => $type,
            'target_id' => $target_id,
            'play_id' => !empty($play_id) ? $play_id : null,
            'play_type' => !empty($play_type) ? $play_type : null,
            'extra_data' => json_encode([
                'timeline_key' => $timeline_key,
                'name' => $name,
                'description' => $description,
                'type' => 'timeline',
                'category' => $timeline_key,
                'image_key' => $image
            ])
        ]);

        return TRUE;
    }
}