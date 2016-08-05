<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "libraries/Aws/APP_Aws.php";


/**
 * AWS S3クラス
 *
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Aws_s3 extends APP_Aws
{

    /**
     * インスタンス名を取得
     *
     * @access public
     * @return string
     */
    public function instance_name()
    {
        return "S3";
    }

    /**
     * インスタンスを取得
     *
     * same as $client = $aws->get('S3');
     *
     * @access public
     * @return mixed
     *
     * @throws APP_Aws_exception
     * @internal param string $endpoint
     *
     */
    protected function & instance()
    {
        unset($this->aws);

        return parent::instance();
    }

    /**
     * put Object to S3
     *
     * @param string $bucket
     * @param string $key
     * @param array $option
     *
     * @return object
     *
     */
    public function putObject($bucket, $key, $option = [])
    {
        $params = array_merge([
            'Bucket' => $bucket,
            'Key' => $key
        ], $option);

        return $this->instance()->putObject($params);
    }

    /**
     * Get Object from S3
     *
     * @param string $bucket
     * @param string $key
     * @param array $option
     *
     * @return object
     *
     */
    public function getObject($bucket, $key, $option = [])
    {
        $params = array_merge([
            'Bucket' => $bucket,
            'Key' => $key
        ], $option);

        return $this->instance()->getObject($params);
    }
}
