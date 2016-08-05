<?php

/**
 * 業務時間を計算するクラス
 *
 * @author Yoshikazu Ozawa
 */
class APP_Business_time_calculator {

    /**
     * @return int
     */
    public function now()
    {
        $time = time();

        $CI =& get_instance();
        if (FALSE !== $CI->config->item('business_time_since')) {
            $time += $CI->config->item('business_time_since');
        }

        return $time;
    }
}

/**
 * DateTimeクラスの業務時間考慮版
 *
 * @author Yoshikazu Ozawa
 */
class APP_Business_date_time extends DateTime {

    /**
     * @param string $time
     * @param string $timezone
     */
    public function __construct ($time = "now", $timezone = NULL)
    {
        // TODO: もうちょっと丁寧な実装が必要
        $time = business_strtotime($time);

        return parent::__construct($time, $timezone);
    }
}

