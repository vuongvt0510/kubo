<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "libraries/Aws/APP_Aws.php";


/**
 * AWS SQSクラス
 *
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Norio Ohata <ohata@interest-marketing.net>
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Aws_sqs extends APP_Aws
{
    /**
     * キューURL
     * @var string
     */
    protected $queue_url = NULL;

    /**
     * キュー名
     * @var string
     */
    protected $queue_name = NULL;


    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!empty($this->queue_name)) {
            $this->queue_url = $this->inquire_queue_url($this->queue_name);
        }
    }

    /**
     * インスタンス名を取得
     *
     * @access public
     * @return string
     */
    public function instance_name()
    {
        return "sqs";
    }

    /**
     * キュー名を取得
     *
     * @access public
     * @return string
     */
    public function queue_name()
    {
        return $this->queue_name;
    }

    /**
     * キュー名を設定
     *
     * @access public
     * @param string $name
     * @return string
     */
    public function set_queue_name($name)
    {
        $this->queue_name = $name;
        $this->queue_url = $this->inquire_queue_url($name);

        return $this->queue_name;
    }

    /**
     * キューへメッセージを送信
     *
     * @access public
     * @param mixed $data
     * @param array $options
     * @return mixed
     */
    public function send_message($data, $options = array())
    {
        $data = json_encode($data);

        try {
            $args = array_merge(array(
                'QueueUrl' => $this->queue_url,
                'MessageBody' => $data,
                'DelaySeconds' => 0
            ), $options);

            $result = $this->instance()->sendMessage($args);
        } catch (Exception $e) {
            throw new APP_Aws_exception(get_class($this) . '::receive_message is failed.', APP_Aws_exception::UNKNOWN_ERROR, $e);
        }

        return $result;
    }

    /**
     * キューからメッセージを取得
     *
     * @access public
     * @param array $options
     * @return mixed
     */
    public function receive_message($options = array())
    {
        try {
            $args = array_merge(array(
                'QueueUrl' => $this->queue_url,
                'AttributeNames' => array('All'),
                'WaitTimeSeconds' => 0
            ), $options);

            $result = $this->instance()->receiveMessage($args);

            $data = $result->getPath("Messages/*");
            if (!empty($data)) {
                if (is_array($data['Body'])) {
                    $converted_data = array();

                    for ($i = 0; $i < count($data['Body']); $i++) {
                        $converted_data[] = $this->convert_result_to_array($data, $i);
                    }

                    $data = $converted_data;

                    foreach ($data as &$d) {
                        $d['Body'] = json_decode($d['Body'], TRUE);
                    }
                } else {
                    $data['Body'] = json_decode($data['Body'], TRUE);
                }
            }
        } catch (Exception $e) {
            throw new APP_Aws_exception(get_class($this) . '::receive_message is failed.', APP_Aws_exception::UNKNOWN_ERROR, $e);
        }

        return $data;
    }

    /**
     * キューからメッセージを削除
     *
     * @access public
     * @param string $handle
     * @param array $options
     * @return mixed
     */
    public function delete_message($handle, $options = array())
    {
        try {
            $args = array(
                'QueueUrl' => $this->queue_url,
            );
            
            if ($handle) {
                $args['ReceiptHandle'] = $handle;
            }

            $args = array_merge($args, $options);

            $result = $this->instance()->deleteMessage($args);

        } catch (Exception $e) {
            throw new APP_Aws_exception(get_class($this) . '::delete_message is failed.', APP_Aws_exception::UNKNOWN_ERROR, $e);
        }

        return $result;
    }

    /**
     * キューURLを問い合わせる
     *
     * @access protected
     * @param string $name
     * @return string
     */
    protected function inquire_queue_url($name = NULL)
    {
        if (empty($name)) {
            $name = $this->queue_name;
        }

        $url = $this->instance()->getQueueUrl(array(
            'QueueName' => $name
        ));

        if (!$url) {
            throw new APP_Aws_exception('queue url was not got.', APP_Aws_exception::UNKNOWN_ERROR);
        }

        return $url['QueueUrl'];
    }


    /**
     * キューの受信結果を整形
     *
     * @access protected
     * @param array $data;
     * @param int $idx
     * @return array
     */
    protected function convert_result_to_array($data, $idx)
    {
        $item = array();

        foreach (array_keys($data) as $key) {

            if (is_hash($data[$key])) {
                $item[$key] = $this->convert_result_to_array($data[$key], $idx);

            } else if (is_strict_array($data[$key])) {
                $item[$key] = $data[$key][$idx];
            } else {
                $item[$key] = $data[$key];
            }
        }

        return $item;
    }
}

