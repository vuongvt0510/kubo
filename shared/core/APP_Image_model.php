<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "/core/APP_Paranoid_model.php";


/**
 * 画像基底モデル
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Tomoyuki Kakuda <kakuda@interest-marketing.net>
 */
class APP_Image_model extends APP_Paranoid_model
{
    public $table_name = 'image';

    /**
     * 画像タイプ
     * @var array
     */
    public $image_types = array(
        'original' => array(
        )
    );

    /**
     * 画像キーから画像を取得する
     * @param string $key
     * @param string $type
     * @param array $options
     * @return stdClass
     * @throws APP_Model_exception
     */
    public function find_by_key($key, $type = 'original', $options = [])
    {
        $res = $this
            ->key_is($key)
            ->type_is($type)
            ->first($options);

        if (!empty($res)) {
            return $res;
        }

        if (!array_key_exists($type, $this->image_types)) {
            throw new APP_Model_exception(
                '画像キーでの画像取得に失敗しました'
            );
        }
        $res = $this
            ->key_is($key)
            ->type_is('original')
            ->first($options);

        $this->_create_image_from_data($res->data, [
            'parent_id' => $res->parent_id,
            'key' => $res->key,
            'type' => $type,
            'holder_type' => $res->holder_type,
            'holder_id' => $res->holder_id,
            'secure' => $res->secure,
            'source_url' => $res->source_url
        ], $this->image_types[$type], $options);

        $res = $this
            ->key_is($key)
            ->type_is($type)
            ->first($options);

        if (!empty($res)) {
            return $res;
        }

        throw new APP_Model_exception(
            '画像キーでの画像取得に失敗しました'
        );
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function key_is($key)
    {
        return $this->where($this->column_name('key'), $key);
    }

    /**
     * @param $type
     *
     * @return mixed
     */
    public function type_is($type)
    {
        return $this->where($this->column_name('type'), $type);
    }

    /**
     * 公開してして良い範囲を条件で指定する
     *
     * @access public
     * @param string $holder_type
     * @param string $holder_id
     * @return self
     */
    public function publish_to($holder_type, $holder_id)
    {
        return $this->where("secure = ? OR (secure = ? AND holder_type = ? AND holder_id = ?)", array(FALSE, TRUE, $holder_type, $holder_id));
    }

    /**
     * 登録
     *
     * @access public
     * @param array $attributes 登録内容
     * @param array $options オプション
     * @return false|object
     */
    public function create($attributes, $options = array())
    {
        $options = array_merge(array(
            'hold_file' => TRUE
        ), $options);

        $attributes['key'] = $this->_generate_unique_key('key', 64);
        $attributes['type'] = 'original';

        $original = $this->_create_image_from_path($attributes['path'],
            $attributes, empty($this->image_types['original']) ? array() : $this->image_types['original'], $options);

        // 必ず利用する画像と併せて登録
        // TODO: original 以外はバッチを回してバックグラウンドで作成する
        $attributes["parent_id"] = record_id($this, $original);

        foreach ($this->image_types AS $type => $mode) {
            if ($type === "original") continue;

            $attributes['type'] = $type;
            $this->_create_image_from_path($attributes['path'], $attributes, $mode, $options);
        }

        // 画像をファイルシステムに残す必要がない場合は削除する
        if ($options['hold_file'] === FALSE && !preg_match("/https?:\/\//", $attributes['path'])) {
            @unlink($attributes['path']);
        }

        return $original;
    }

    /**
     * ファイル画像の登録
     *
     * @access protected
     * @param mixed $data バイナリデータ
     * @param array $attributes
     * @param array $mode
     * @param array $options
     * @return mixed
     * @throws APP_Image_model_exception
     */
    protected function _create_image_from_data(& $data, $attributes, $mode = array(), $options = array())
    {
        $imagick = null;

        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($data);

            $result = $this->_create_image_from_imagick($imagick, $attributes, $mode, $options);

            $imagick->clear();
            unset($imagick);

        } catch (Exception $e) {
            $imagick->clear();
            unset($imagick);

            throw new APP_Image_model_exception("failed to create image from binary", 0, $e);
        }

        return $result;
    }

    /**
     * バイナリ画像の登録
     *
     * @access protected
     * @param string $path
     * @param array $attributes
     * @param array $mode
     * @param array $options
     * @return mixed
     * @throws APP_Image_model_exception
     */
    protected function _create_image_from_path($path, $attributes, $mode = array(), $options = array())
    {
        if (empty($attributes['source_url']) && preg_match("/https?:\/\//", $path)) {
            $attributes['source_url'] = $path;
        }

        try {
            // URL指定を考慮してオープンできるか判定する
            $f = @fopen($path, 'rb');
            if (FALSE === $f) {
                $e = error_get_last();
                throw new Exception("File ($path) open failed." . $e['message'], $e['type']);
            }

            $imagick = new Imagick();
            $imagick->readImageFile($f);
            fclose($f);

            $result = $this->_create_image_from_imagick($imagick, $attributes, $mode, $options);

            $imagick->clear();
            unset($imagick);

        } catch (Exception $e) {
            if (!empty($imagick)) {
                $imagick->clear();
                unset($imagick);
            }

            throw new APP_Image_model_exception("failed to create image from file ($path)", 0, $e);
        }

        return $result;
    }

    /**
     * 画像の登録
     *
     * @access protected
     * @param Imagick $imagick
     * @param array $attributes
     * @param array $mode
     * @param array $options
     * @return mixed
     */
    protected function _create_image_from_imagick(& $imagick, $attributes, $mode = array(), $options = array())
    {

        $mode['image_type'] = isset($options['image_type']) ? $options['image_type'] : 'jpeg';

        $this->_adjust_image_size($imagick, $mode);

        return parent::create(array(
            'parent_id' => empty($attributes['parent_id']) ? NULL : $attributes['parent_id'],
            'key' => $attributes['key'],
            'name' => isset($attributes['name']) ? $attributes['name'] : "",
            'content_type' => (isset($options['image_type']) && $options['image_type'] == 'png') ? 'image/png' : 'image/jpeg',
            'type' => $attributes['type'],
            'data' => $imagick->getImageBlob(),
            'size' => $imagick->getImageLength(),
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight(),
            'holder_type' => empty($attributes['holder_type']) ? NULL : $attributes['holder_type'],
            'holder_id' => empty($attributes['holder_id']) ? NULL : $attributes['holder_id'],
            'secure' => empty($attributes['secure']) ? FALSE : $attributes['secure'],
            'source_url' => empty($attributes['source_url']) ? NULL : $attributes['source_url']
        ), $options);
    }

    /**
     * 画像サイズを調整する
     *
     * @access public
     * @param Imagick $imagick
     * @param array $mode
     * @return void
     */
    protected function _adjust_image_size(& $imagick, $mode = array())
    {
        $orient_num = $imagick->getImageOrientation();

        switch ($orient_num) {
            case imagick::ORIENTATION_UNDEFINED:
            case imagick::ORIENTATION_TOPLEFT:
                break;

            case imagick::ORIENTATION_TOPRIGHT:
                $imagick->flopImage();
                break;

            case imagick::ORIENTATION_BOTTOMRIGHT:
                $imagick->rotateImage(new ImagickPixel(), 180);
                break;

            case imagick::ORIENTATION_BOTTOMLEFT:
            case imagick::ORIENTATION_RIGHTBOTTOM:
                $imagick->flopImage();
            case imagick::ORIENTATION_LEFTBOTTOM:
                $imagick->rotateImage(new ImagickPixel(), 270);
                break;

            case imagick::ORIENTATION_LEFTTOP:
                $imagick->flopImage();
            case imagick::ORIENTATION_RIGHTTOP:
                $imagick->rotateImage(new ImagickPixel(), 90);
                break;
        }

        $imagick->setImageOrientation(imagick::ORIENTATION_TOPLEFT);

        $mode = array_merge(array("type" => "original"), $mode);

        switch ($mode['type']) {

            // 最大幅を決める
            // Decide max width to output
            case 'max_width':
                if ($mode['max_width'] < $imagick->getImageWidth()) {
                    $imagick->scaleImage($mode['max_width'], 0);
                }
                break;

            // 指定されている画像サイズを最大としてリサイズ
            // Resize image size among suggested width or height
            case 'resize':
                $imagick->resizeImage($mode['width'], $mode['height'], Imagick::FILTER_LANCZOS, 1, TRUE);
                break;

            // 画像の縦横比の最小に合わせて、正方形に変更
            // sizeが指定されていれば、さらにそのサイズに変更
            // Making thumbnail image from size
            case 'thumbnail':
                $geo = $imagick->getImageGeometry();
                $size = min($geo);

                $imagick->cropThumbnailImage($size, $size);

                if (!empty($mode['size'])) {
                    $imagick->scaleImage($mode['size'], $mode['size']);
                }
                break;

            default:
                break;
        }

        if (isset($mode['image_type']) && $mode['image_type'] == 'png') {
            $imagick->setImageFormat('png');
        } else {
            $imagick->setImageFormat('jpeg');
            $imagick = $imagick->flattenImages();
        }

        $imagick->setImageResolution(72,72);
        $imagick->resampleImage(72,72,Imagick::FILTER_UNDEFINED,1);

        if (!empty($mode['quality'])) {
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setCompressionQuality($mode['quality']);
        }
    }
}


/**
 * 画像モデル例外クラス
 *
 * @copyright Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 * @author Tomoyuki Kakuda <kakuda@interest-marketing.net>
 */
class APP_Image_model_exception extends APP_Exception
{
}

