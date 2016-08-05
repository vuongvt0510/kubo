<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';
/**
 * Class Battle_room_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Battle_room_model extends APP_Model
{
    /**
     * Mod group play
     */
    const GROUP_BATTLE_TYPE = 'battle';
    const GROUP_QUEST_TYPE = 'quest';
    const GROUP_EVENT_TYPE = 'event';

    const BATTLE_ROOM_TIME_LIMIT = 1200; // second
    const BATTLE_ROOM_OPEN_STATUS = 'open';
    const BATTLE_ROOM_CLOSE_STATUS = 'close';

    const RESULT_WIN = 'WIN';
    const RESULT_LOSE = 'LOSE';
    const RESULT_DRAW = 'DRAW';

    public $database_name = DB_MAIN;
    public $table_name = 'battle_room';
    public $primary_key = 'id';

    /**
     * Limit response
     * @param  int $limit 
     * @param  int $offset
     * @return battle_room_model
     */
    public function with_history_limit($limit, $offset)
    {
        $sql = "(SELECT *
                FROM battle_room
                WHERE battle_room.status = 'close'
                LIMIT ". $offset. ", ".$limit. ") as room";

        return $this->join($sql, "room.id = battle_room.id")
            ->join('room_team', 'room_team.room_id = room.id')
            ->join('team_playing', 'team_playing.team_id = room_team.team_id AND team_playing.target_id = room.id');
    }

    /**
     * join with group of user
     * @return battle_room_model
     */
    public function with_group_user()
    {
        // Get detail room playing
        $sql = "(
            SELECT SUM(user_group_playing.score) AS score, `group`.name, 
                group_playing.group_id, group_playing.target_id
            FROM group_playing
                JOIN `group` ON `group`.id = group_playing.group_id
                LEFT JOIN user_group_playing ON user_group_playing.group_playing_id = group_playing.id
            WHERE group_playing.type = '".self::GROUP_BATTLE_TYPE ."'
            GROUP BY group_playing.id
            ) AS group_user";

        return $this->join($sql, 'group_user.group_id = battle_room.group_id AND group_user.target_id = battle_room.id');
    }

    /**
     * join with group of target
     * @return battle_room_model
     */
    public function with_target_group()
    {
        // Get detail room playing
        $sql = "(
            SELECT SUM(user_group_playing.score) AS score, `group`.name, 
                group_playing.group_id, group_playing.target_id
            FROM group_playing
                JOIN `group` ON `group`.id = group_playing.group_id
                LEFT JOIN user_group_playing ON user_group_playing.group_playing_id = group_playing.id
            WHERE group_playing.type = '".self::GROUP_BATTLE_TYPE ."'
            GROUP BY group_playing.id
            ) AS target_group";

        return $this->join($sql, 'target_group.group_id = battle_room.target_group_id AND target_group.target_id = battle_room.id');
    }
}