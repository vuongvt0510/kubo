<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Import trophy
 *
 * @package Controller
 * @version $id$
 * @copyright 2014- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 */

require_once SHAREDPATH . "core/APP_Cli_controller.php";

/**
 * Class Trophy
 *
 * @property Google_Spreadsheet google_spreadsheet
 */
class Trophy extends APP_Cli_controller
{
    // Entry error when importing
    var $errors = [];

    function __construct()
    {
        parent::__construct();

        ini_set('memory_limit', '2048M');
        set_time_limit(-1);

        // Load library
        $this->load->library('Google/Google_Spreadsheet');
    }

    /**
     * Execute
     */
    function execute()
    {

    }

    /**
     * Import trophy image function
     * @param int $allow_update
     */
    public function import_images($allow_update = 0)
    {
        $this->load->helper('file');
        $this->load->model('image_model');

        $drive = $this->google_spreadsheet->get_drive_instance();

        $results = $drive->files->listFiles([
            'q' => "'0BxsyNfEw-vpiTjJKYXBTbGJxNGc' in parents "
        ]);

        $image_types = [
            'small' => ['type' => 'resize', 'width' => 120, 'height' => 120, 'quality' => 90],
            'original' => ['type' => 'max_width', 'max_width' => 300, 'quality' => 100]
        ];


        foreach ($results->getItems() AS $file) {

            if (strpos($file->mimeType, 'image') !== 0) {
                continue;
            }

            // Check key
            $key = mb_trim($file->title);

            log_message('info', '[Import][TrophyImage] ' . $key);

            $request = new Google_Http_Request($file->downloadUrl, 'GET', null, null);

            $httpRequest = $drive->getClient()->getAuth()->authenticatedRequest($request);

            $image = $this->image_model->find_by([
                'key' => $key,
                'holder_type' => 'trophy'
            ]);

            if (empty($image)) {
                $this->image_model->create_from_data([
                    'data' => $httpRequest->getResponseBody(),
                    'key' => $key,
                    'holder_type' => 'trophy'
                ], [
                    'image_type' => 'png',
                    'only_original' => FALSE
                ], $image_types);
            } else if ($allow_update) {
                $this->image_model->update_from_data($image->key, [
                    'data' => $httpRequest->getResponseBody(),
                    'key' => $key,
                    'holder_type' => 'trophy'
                ], [
                    'image_type' => 'png',
                    'only_original' => FALSE
                ], $image_types);
            }
        }
    }
}
