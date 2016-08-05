<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Class Score_api
 *
 * Do not action user has value of deleted_by or status is not active
 */
class Score_api extends Base_api
{
    /*
     * array $score
     */
    private $scores = [
        'bonus' => 2000,
        'sum' => 100,
        'total' => 20
    ];

    /**
     * @param array $params
     *
     * @internal param int $total
     * @internal param array $scores
     *
     * @return JSON
     */
    public function create($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('total', '合計', 'required');
        $v->set_rules('scores[]', 'スコア');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        if(empty($params['scores'])) {
            return $this->false_json(self::NOT_FOUND, 'スコアが見つかりません。');
        }

        foreach($params['scores'] as $k) {
            if($k == 0 || !is_numeric($k)) {
                return $this->false_json(self::NOT_FOUND, 'スコアが間違っています。');
            }
        }

        // Sum the total score
        $total = array_sum($params['scores']);

        // If all answer is correct add more bonus
        $total += ($params['total'] == count($params['scores'])) ? $this->scores['bonus'] : 0;

        // Return
        return $this->true_json(['total_score' => round($total) ]);
    }

    /**
     * @param array $params
     *
     * @internal param int $second
     * @internal param int $limit
     *
     * @return JSON
     */
    public function calculate($params = [])
    {
        // Validate
        $v = $this->validator($params);
        $v->set_rules('second', '秒', 'required|integer');
        $v->set_rules('limit', '取得件数', 'required|integer|is_natural_no_zero');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        $total = ( $this->scores['sum'] - ( $params['second'] / $params['limit'] ) * $this->scores['sum'] ) * $this->scores['total'] ;

        // Return
        return $this->true_json(['total' => round($total)]);
    }
}


