<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Paranoid_model.php';

/**
 * Class Point_exchange_model
 *
 */
class Point_exchange_model extends APP_model
{
    const PX_LIMIT = 50;

    const PX_WAIT_STATUS = 'wait';
    const PX_ERROR_STATUS = 'error';
    const PX_DONE_STATUS = 'done';
    const PX_REJECT_STATUS = 'reject';
    const PX_EXPIRED_STATUS = 'expired';

    const PX_WAIT_LABEL = 'ポイント交換申請中';
    const PX_ERROR_LABEL = '承認済み　ポイント交換前';
    const PX_DONE_LABEL = '承認済み　ポイント交換完了';
    const PX_REJECT_LABEL = 'ポイント交換非承認';
    const PX_EXPIRED_LABEL = '有効期限切れ';

    const PX_WAIT_LABEL_ADMIN = '未交換';
    const PX_ERROR_LABEL_ADMIN = '交換失敗';
    const PX_DONE_LABEL_ADMIN = '承認';
    const PX_REJECT_LABEL_ADMIN = '非承認';
    const PX_EXPIRED_LABEL_ADMIN = 'Expired_admin_site';

    public $database_name = DB_MAIN;
    public $table_name = 'point_exchange';
    public $primary_key = 'id';

    /**
     * Get list packs exchange point
     */
    public function get_packs()
    {
        $this->load->model('price_model');
        $res = $this->price_model
            ->select('price AS point, number AS mile')
            ->where('type', 'mile')
            ->all();

        $result = [];
        if (!empty($res)) {
            foreach ($res as $key => $value) {
                $k = "ex_" . ($key + 1);
                $result[$k] = get_object_vars($value);
            }
        }

        return $result;
    }

    /**
     * Return list type of status
     */
    public function get_list_status_admin()
    {
        return [
            self::PX_WAIT_STATUS => self::PX_WAIT_LABEL_ADMIN,
            self::PX_ERROR_STATUS => self::PX_ERROR_LABEL_ADMIN,
            self::PX_DONE_STATUS => self::PX_DONE_LABEL_ADMIN,
            self::PX_REJECT_STATUS => self::PX_REJECT_LABEL_ADMIN
        ];
    }

    /**
     * Return all type of status point
     */
    public function get_all_status()
    {
        return [
            self::PX_WAIT_STATUS => self::PX_WAIT_LABEL_ADMIN,
            self::PX_ERROR_STATUS => self::PX_ERROR_LABEL_ADMIN,
            self::PX_DONE_STATUS => self::PX_DONE_LABEL_ADMIN,
            self::PX_REJECT_STATUS => self::PX_REJECT_LABEL_ADMIN,
            self::PX_EXPIRED_STATUS => self::PX_EXPIRED_LABEL_ADMIN
        ];
    }

    /**
     * Count number records have status
     *
     * @param array $params
     * @param string $request_status
     *
     * @return int $total
     */
    public function count_status($params = [], $request_status = self::PX_WAIT_STATUS)
    {
        $this->select('COUNT(point_exchange.id) as total')
            ->with_user()
            ->with_target()
            ->with_group()
            ->with_user_netmile()
            ->with_user_contract()
            ->where_in('point_exchange.status', $params['status'])
            ->where('point_exchange.status', $request_status)
            ->where('ug.group_id = tg.group_id');

        // Filter user_id
        if (!empty($params['user_id'])) {
            $this->point_exchange_model->where("(
                point_exchange.user_id = '". $params['user_id']. 
                "' OR point_exchange.target_id = '". $params['user_id']. "'
                )"
            );
        }

        // Filter login_id
        if (!empty($params['login_id'])) {
            $this->point_exchange_model->where("(
                a.login_id = '". $params['login_id']. 
                "' OR b.login_id = '". $params['login_id']. "'
                )"
            );
        }

        // Filter enc_user_id
        if (!empty($params['enc_user_id'])) {
            $this->point_exchange_model->where('user_netmile.enc_user_id', $params['enc_user_id']);
        }

        // Filter ip_address
        if (!empty($params['ip_address'])) {
            $this->point_exchange_model->where('point_exchange.ip_address', $params['ip_address']);
        }

        // Filter time
        $this->point_exchange_model->where("(point_exchange.created_at BETWEEN '". $params['from_date']. "' AND '". $params['to_date']. "')");

        $res = $this->point_exchange_model->first();

        return (int) $res->total;
    }

    /**
     * join user table
     */
    public function with_user()
    {
        return $this->join('(SELECT * FROM user WHERE deleted_at IS NULL) AS a', 'a.id = point_exchange.user_id', 'left');
    }

    /**
     * join user table
     */
    public function with_target()
    {
        return $this->join('(SELECT * FROM user WHERE deleted_at IS NULL) AS b', 'b.id = point_exchange.target_id');
    }

    /**
     * join user_group table
     */
    public function with_group()
    {
        $sql = "(SELECT group_id, user_group.user_id
                FROM user_group
                JOIN point_exchange ON user_group.user_id = point_exchange.user_id
                GROUP BY user_group.group_id
                ) AS ug";

        $target_sql = "(SELECT group_id, user_group.user_id
                FROM user_group
                JOIN point_exchange ON user_group.user_id = point_exchange.target_id
                GROUP BY user_group.group_id
                ) AS tg";

        return $this->join($sql, 'ug.user_id = point_exchange.user_id')
            ->join($target_sql, 'tg.user_id = point_exchange.target_id')
            ->join('group', 'group.id = ug.group_id AND group.id = tg.group_id');
    }

    /**
     * join user_netmile
     */
    public function with_user_netmile()
    {
        return $this->join('user_netmile', 'user_netmile.user_id = point_exchange.user_id');
    }

    /**
     * Join user_contract
     */
    public function with_user_contract()
    {
        return $this->join('user_contract', 'user_contract.user_id = point_exchange.target_id');
    }

    /**
     * Create point exchange
     */
    public function create_point_exchange($params = [])
    {
        // Load model
        $this->load->model('user_rabipoint_model');

        // Update user_rabipoint
        // Call list user_rabipoint
        $points = $this->user_rabipoint_model
            ->select('id, rabipoint, point_remain')
            ->where('user_id', $params['target_id'])
            ->where('point_remain >', 0)
            ->order_by('created_at', 'ASC')
            ->all();

        $point_exchange = isset($params['point']) ? $params['point'] : 0;
        $extra_data = [];

        // Loop all record user_rabipoint
        while($point_exchange) {
            $point = current($points);

            // Update point remain
            if ($point->point_remain < $point_exchange) {
                // Update point_remain
                $this->user_rabipoint_model->update($point->id, [
                    'point_remain' => 0
                ]);

                $point_exchange -= $point->point_remain;

                $extra_data[] = [
                    'id' => (int) $point->id,
                    'rabipoint' => (int) $point->rabipoint,
                    'point_remain' => 0
                ];
            } else {
                // Update point remain
                $this->user_rabipoint_model->update($point->id, [
                    'point_remain' => $point->point_remain - $point_exchange
                ]);

                $extra_data[] = [
                    'id' => (int) $point->id,
                    'rabipoint' => (int) $point->rabipoint,
                    'point_remain' => $point->point_remain - $point_exchange
                ];

                $point_exchange = 0;
            }

            $point = next($points);
        }

        $this->load->model('user_netmile_model');

        // Update current enc_user_id of netmile
        $user_netmile = $this->user_netmile_model
            ->select('user_id, netmile_user_id, enc_user_id')
            ->where('user_id', $params['user_id'])
            ->first();

        if (!empty($user_netmile)) {
            $extra_data['enc_user_id_request'] = $user_netmile->enc_user_id;
        }

        // Insert extra_data
        $extra_data = json_encode($extra_data);

        $res = $this->create([
            'user_id' => isset($params['user_id']) ? $params['user_id'] : '',
            'target_id' => isset($params['target_id']) ? $params['target_id'] : '',
            'ip_address' => isset($params['ip_address']) ? $params['ip_address'] : '',
            'point' => isset($params['point']) ? $params['point'] : '',
            'mile' => isset($params['mile']) ? $params['mile'] : '',
            'status' => isset($params['status']) ? $params['status'] : '',
            'extra_data' => $extra_data,
        ], [
            'return' => TRUE
        ]);

        return $res;
    }
}
