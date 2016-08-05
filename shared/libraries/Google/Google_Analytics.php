<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/Google/Google_base.php';

/**
 * Google Analytics Library
 *
 * @author Norio Ohata
 */
class Google_Analytics extends Google_base
{
    /**
     * @var int
     */
    private $view_id;

    /**
     * Google_Analytics constructor.
     * @param array $params
     * @throws Google_Exception_api
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $role = array();
        if( isset($params['role']) ){
            $role = $params['role'];
        }
        $role[] = Google_Service_Analytics::ANALYTICS_READONLY;

        $this->authorize(array_unique($role));

        if( isset($this->config['ga']['view_id']) && !empty($this->config['ga']['view_id']) ){
            $this->view_id = $this->config['ga']['view_id'];
        }
        else{
            throw new Google_Exception_api('GA の View_ID が設定されていません', -1);
        }
    }

    /**
     * @return mixed
     */
    protected function get_app_name()
    {
        return $this->config['ga']['app_name'];
    }

    /**
     * データ一覧の取得
     *
     * @param string $start 集計開始日
     * @param string $end 集計終了日
     * @param string $metrics 取得対象の種別
     * @param array $params 送付パラメータ
     * @return array
     *
     * @throws Google_Exception_api
     */
    public function get_report_list($start = 'today', $end = 'today', $metrics = 'ga:pageviews', $params = [])
    {
        $service = $this->getInstance('Google_Service_Analytics');
        if( empty($service) ){
            throw new Google_Exception_api('Google Analytics Instance is empty', -1);
        }

        // 最大数を設定する
        if( !isset($params['max-results']) || $params['max-results'] < 0 ){
            $params['max-results'] = 25;
        }

        // 取得データ位置を設定
        if( !isset($params['start-index']) || empty($params['start-index']) ){
            $params['start-index'] = 1;
        }

        $data = $service->data_ga->get(
            "ga:{$this->view_id}",
            $start,
            $end,
            $metrics,
            $params
        );

        // エラー判定
        if( isset($data['error']) && !empty($data['error']) ){
            throw new Google_Exception_api($data['error']['messages'], $data['error']['code']);
        }

        // ヘッダ情報を取得
        $header = $data->getColumnHeaders();

        // 取得したデータを加工して返却する
        return [
            'summary' => $data->getTotalsForAllResults(),
            'total' => $data->getTotalResults(),
            'result' => array_map(function($row) use($header) {

                $tmp = [];
                foreach( $row as $key => $val ){
                    $tmp[ $header[$key]->getName() ] = $val;
                }

                return $tmp;
            }, $data->getRows()),
        ];
    }
}

