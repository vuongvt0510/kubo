<?php

require_once SHAREDPATH . "third_party/AWS/aws.phar";

use Aws\Sns\SnsClient;

/**
 * エラー通知ドライバ - Email
 *
 * @author Yoshikazu Ozawa
 */
class APP_Error_notifier_driver_amazon_sns {

    /**
     * 通知先トピック
     * @var string
     */
    public $topic = NULL;


    private $client = NULL;


    public function __construct($params)
    {
        $this->client = SnsClient::factory(array(
            'key' => $params['key'],
            'secret' => $params['secret'],
            'region' => $params['region']
        ));

        $this->topic = $params['topic'];
    }


    /**
     * 送信
     *
     * @access public
     * @param string $subject
     * @param string $contents
     * @param array $options
     * @return bool
     */
    public function send($subject, $contents, $options = array())
    {
        if (strlen($subject) > 50) {
            $subject = substr($subject, 0, 50) . "...";
        }

        $result = $this->client->publish(array(
            'TopicArn' => $this->topic,
            'Message' => $contents,
            'Subject' => $subject,
        ));
    }
}


