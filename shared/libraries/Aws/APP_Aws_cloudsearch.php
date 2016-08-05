<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "libraries/Aws/APP_Aws.php";


/**
 * AWS CloudSearchクラス
 *
 * @version $id$
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author Yoshikazu Ozawa <ozawa@interest-marketing.net>
 */
class APP_Aws_cloudsearch extends APP_Aws
{
    /**
     * 検索エンドポイント
     * @var string
     */
    protected $search_endpoint = NULL;

    /**
     * ドキュメントエンドポイント
     * @var string
     */
    protected $document_endpoint = NULL;

    /**
     * 検索エンドポイントを設定
     * 
     * @access public
     * @param string $base_url
     * @return void
     */
    public function set_search_endpoint($endpoint)
    {
        $this->search_endpoint = $endpoint;
        return $this;
    }

    /**
     * ドキュメントエンドポイントを設定
     * 
     * @access public
     * @param string $base_url
     * @return void
     */
    public function set_document_endpoint($endpoint)
    {
        $this->document_endpoint = $endpoint;
        return $this;
    }

    /**
     * インスタンス名を取得
     *
     * @access public
     * @return string
     */
    public function instance_name()
    {
        return "CloudSearchDomain";
    }

    /**
     * インスタンスを取得
     *
     * @access public
     * @return mixed
     */
    protected function & instance($endpoint = 'search')
    {
        unset($this->aws);
        return parent::instance(array(
            'base_url' => $endpoint === 'document' ? $this->document_endpoint : $this->search_endpoint
        ));
    }

    /**
     * 検索
     *
     * @access public
     * @param string $query
     * @param array $options
     *
     * @param array $config
     * @return object
     */
    public function search($query, $options = array(), $config = array())
    {
        $query_parser = empty($options['queryParser']) ? 'simple' : $options['queryParser'];

        log_message("DEBUG", "[cloudsearch][{$this->search_endpoint}] search ($query_parser) " . $query);

        return $this->instance()->search(array_merge(array(
            'query' => $query
        ), $options));
    }

    /**
     * ドキュメントをアップロードする
     *
     * @access protected
     * @param string $type
     * @param string $id
     * @param array $fields
     * @param array $options
     * @return object
     */
    public function upload($type, $id, $fields, $options = array())
    {
        $document = array(
            'type' => $type,
            'id' => $id,
            'fields' => $fields
        );

        if ($type == 'delete') {
            unset($document['fields']);
        }

        $document = json_encode(array($document));

        log_message("DEBUG", "[cloudsearch][{$this->document_endpoint}] upload ({$type}) " . $document);

        $result = $this->instance('document')->uploadDocuments(array(
            'contentType' => 'application/json',
            'documents' => $document
        ));

        return $result;
    }

    /**
     * ドキュメントをアップロードする
     *
     * @access public
     * @param mixed $data
     * @param array $options
     * @return mixed
     */
    public function bulk_upload($data, $content_type = "application/json", $options = array())
    {
        $result = $this->instance('document')->uploadDocuments(array(
            'contentType' => $content_type,
            'documents' => is_array($data) ? json_encode($data) : $data
        ));

        return $result;
    } 

    /**
     * 結果セットをstdObjectに変換
     *
     * @access public
     * @param array $hit
     * @param string $clazz
     * @return array
     */
    public function extract_to_object($hits, $clazz = 'stdClass')
    {
        foreach ($hits as &$h) {
            $obj = new $clazz;

            $obj->_document_id = $h['id'];

            foreach ($h['fields'] as $key => $value) {
                if (is_array($value)) {
                    if (count($value) > 1) {
                        $obj->{$key} = $value;
                    } else {
                        $obj->{$key} = $value[0];
                    }
                } else {
                    $obj->{$key} = $value;
                }
            }

            $h = $obj;
        }

        return $hits;
    }
}

