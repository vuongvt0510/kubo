<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . "/core/APP_Image_model.php";

/**
 * Class Image_model
 *
 * Manipulate image binary
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Image_model extends APP_Image_model
{
    public $database_name = DB_IMAGE;

    public $image_types = [
        'tiny' => ['type' => 'resize', 'width' => 90, 'height' => 60, 'quality' => 75],
        'small' => ['type' => 'resize', 'width' => 420, 'height' => 280, 'quality' => 75],
        'medium' => ['type' => 'resize', 'width' => 540, 'height' => 360, 'quality' => 75],
        'large' => ['type' => 'resize', 'width' => 700, 'height' => 467, 'quality' => 90],
        'header' => ['type' => 'max_width', 'max_width' => 700, 'quality' => 90],
        'original' => ['type' => 'max_width', 'max_width' => 700, 'max_height' => 700, 'quality' => 100]
    ];

    /**
     * Create image from data
     *
     * @access public
     * @param array $attributes 登録内容
     * @param array $options オプション
     * @param array $images_type
     * @return false|object
     */
    public function create_from_data($attributes, $options = [], $images_type = [])
    {
        $options = array_merge(array(
            'hold_file' => TRUE,
            'only_original' => TRUE
        ), $options);

        $attributes['key'] = empty($attributes['key']) ? $this->_generate_unique_key('key', 64) : $attributes['key'];
        $attributes['type'] = 'original';

        $images_types = !empty($images_type) ? $images_type : $this->image_types;

        $original_mode = empty($images_types['original']) ? $this->image_types['original'] : $images_types['original'];

        $original = $this->_create_image_from_data($attributes['data'], $attributes, $original_mode, $options);

        if (FALSE == $options['only_original']) {
            // Auto dump images by types
            $attributes['parent_id'] = record_id($this, $original);

            foreach ($images_types AS $type => $mode) {

                if ($type == 'original') continue;

                $attributes['type'] = $type;
                $this->_create_image_from_data($attributes['data'], $attributes, $mode, $options);
            }
        }

        return $original;
    }

    /**
     * Update image from data
     *
     * @access public
     * @param string $key of image
     * @param array $attributes 登録内容
     * @param array $options オプション
     * @param array $images_type
     *
     * @return false|object
     */
    public function update_from_data($key = '', $attributes, $options = [], $images_type = [])
    {
        $options = array_merge(array(
            'hold_file' => TRUE,
            'only_original' => TRUE
        ), $options);

        if (empty($key)) {
            return FALSE;
        }

        // Check image key is exist in DB
        $res = $this
            ->select('id, type, key')
            ->key_is($key)
            ->type_is('original')
            ->first();

        if (empty($res)) {
            return FALSE;
        }

        $attributes['key'] = $key;
        $attributes['type'] = 'original';

        /** @var array $images_types Config all images type for dump into DB */
        $images_types = !empty($images_type) ? $images_type : $this->image_types;

        $original = $this->_update_image_from_data($res->id, $attributes['data'],
            $attributes, empty($images_types['original']) ? $this->image_types['original'] : $images_types['original'], $options);

        if (FALSE == $options['only_original']) {
            $images_res = $this
                ->select('id, type, key')
                ->where('parent_id', $res->id)
                ->all();

            // Auto dump images by types
            $attributes["parent_id"] = record_id($this, $original);

            foreach ($images_types AS $type => $mode) {

                if ($type == 'original') continue;

                $attributes['parent_id'] = $res->id;

                $attributes['type'] = $type;

                $child_id = null;

                foreach ($images_res AS $image) {
                    if ($image->type == $type) {
                        $child_id = $image->id;
                    }
                }

                if ($child_id) {
                    $this->_update_image_from_data($child_id, $attributes['data'], $attributes, $mode, $options);
                } else {
                    $this->_create_image_from_data($attributes['data'], $attributes, $mode, $options);
                }
            }
        }

        return $original;
    }

    /**
     * Update from image data
     *
     * @param $id
     * @param $data
     * @param $attributes
     * @param array $mode
     * @param array $options
     * @return false|object
     * @throws APP_Image_model_exception
     */
    protected function _update_image_from_data($id,  & $data, $attributes, $mode = array(), $options = array())
    {
        $imagick = null;

        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($data);

            $result = $this->_update_image_from_imagick($id, $imagick, $attributes, $mode, $options);

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
     * Update image from imagick
     * @param $id of image
     * @param $imagick
     * @param $attributes
     * @param array $mode
     * @param array $options
     * @return false|object
     */
    protected function _update_image_from_imagick($id, & $imagick, $attributes, $mode = array(), $options = array())
    {

        $mode['image_type'] = isset($options['image_type']) ? $options['image_type'] : 'jpeg';

        $this->_adjust_image_size($imagick, $mode);

        $update_data = [
            'content_type' => (isset($options['image_type']) && $options['image_type'] == 'png') ? 'image/png' : 'image/jpeg',
            'type' => $attributes['type'],
            'data' => $imagick->getImageBlob(),
            'size' => $imagick->getImageLength(),
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight()
        ];

        foreach (['name', 'holder_type', 'holder_id', 'secure', 'source_url'] AS $field) {
            if (isset($attributes[$field])) {
                $update_data[$field] = $attributes[$field];
            }
        }

        return parent::update($id, $update_data, $options);
    }
}
