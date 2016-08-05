<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Google Spreadsheet Library
 *
 * Currently, this class isn't extend Google_base class because google spreadsheet library required Request authorization from the user
 *
 * @author Duy Ton That <duytt@nal.vn>
 */

// Autoload google client
require_once SHAREDPATH . 'libraries/Google/Google_base.php';

// Load google spreadsheet api.
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Worksheet.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/ServiceRequestInterface.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/DefaultServiceRequest.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/ServiceRequestFactory.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/SpreadsheetFeed.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/SpreadsheetService.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Exception.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/UnauthorizedException.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Spreadsheet.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Util.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/WorksheetFeed.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Worksheet.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/ListFeed.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/ListEntry.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/CellFeed.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/CellEntry.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Batch/BatchRequest.php';
require_once SHAREDPATH . 'third_party/php-google-spreadsheet-client-2.3.6/src/Google/Spreadsheet/Batch/BatchResponse.php';
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

// Define google scope for Oauth2 to requrest google spreadsheet api
define('GOOGLE_SPREAD_SCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE,
        Google_Service_Drive::DRIVE_METADATA_READONLY,
        Google_Service_Drive::DRIVE_FILE,
        'https://spreadsheets.google.com/feeds'
    )
));

class Google_Spreadsheet
{
    /**
     * @var Google_Client client object
     */
    private $client;

    /**
     * @var Google SpreadsheetService object
     */
    private $spreadsheet_service;

    /**
     * @var array config
     */
    protected $config;

    /**
     * Google_Spreadsheet constructor.
     * @param array $params
     *
     * @throws Google_Exception_api
     */
    public function __construct($params = [])
    {
        // Load config file
        $files = array(
            SHAREDPATH . "config/google.php",
            SHAREDPATH . "config/" . ENVIRONMENT . "/google.php",
            APPPATH . "config/google.php",
            APPPATH . "config/" . ENVIRONMENT . "/google.php"
        );

        foreach ($files as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        /** @var array $google */
        $this->config = $google;

        // Init client and spreadsheet service
        $this->init_client();

        $this->init_service();

    }

    /**
     * Expand home directory
     * @param string $path
     * @return string
     */
    private function expand_home_directory($path = '')
    {
        $home_direactory = getenv('HOME');
        if (empty($home_direactory)) {
            $home_direactory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        return str_replace('~', realpath($home_direactory), $path);
    }

    /**
     * Init client
     * @return bool
     *
     * @throws Google_Exception_api
     */
    private function init_client()
    {
        // Init
        $this->client = new Google_Client();
        $this->client->setApplicationName($this->get_app_name());
        $this->client->setScopes(GOOGLE_SPREAD_SCOPES);
        $this->client->setAuthConfigFile($this->config['server_key_location']);
        $this->client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentials_path = $this->expand_home_directory($this->config['credentials_location']);

        // Load access token. If token file isn't exist, terminal will show you a link to grant access token, you should
        // to copy that link and open in your browser. Then get verification code and paste into terminal for authorization
        // This access token will store to disk ( file location is in config ) for next request
        if (file_exists($credentials_path)) {
            $access_token = file_get_contents($credentials_path);
        } else {
            // Request authorization from the user.
            $auth_url = $this->client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $auth_url);
            print 'Enter verification code: ';

            $auth_code = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $access_token = $this->client->authenticate($auth_code);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentials_path))) {
                mkdir(dirname($credentials_path), 0700, true);
            }
            file_put_contents($credentials_path, $access_token);
            printf("Credentials saved to %s\n", $credentials_path);
        }

        $this->client->setAccessToken($access_token);

        // Refresh the token if it's expired.
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($this->client->getRefreshToken());
            file_put_contents($credentials_path, $this->client->getAccessToken());
        }

        return TRUE;
    }

    /**
     * Init spreadsheet service instance base on access token
     * @return bool
     */
    private function init_service()
    {
        $token_obj = json_decode($this->client->getAccessToken());
        $service_request = new DefaultServiceRequest($token_obj->access_token);
        ServiceRequestFactory::setInstance($service_request);
        $this->spreadsheet_service = new Google\Spreadsheet\SpreadsheetService();
        return TRUE;
    }

    /**
     * application Name
     *
     * @return string
     */
    protected function get_app_name()
    {
        return $this->config['app_name'];
    }

    /**
     * Get spreadsheet by title
     * @param string $title
     * @return object SpreadsheetFeed
     *
     * @throws Google_Exception_api
     */
    public function get_spreadsheet_by_title($title = '')
    {
        $spreadsheet_feed = $this->spreadsheet_service->getSpreadsheets();

        $spreadsheet = $spreadsheet_feed->getByTitle($title);

        if (empty($spreadsheet)) {
            throw new Google_Exception_api('Spreadsheet with title "' . $title . '" is not exist', -1);
        }

        return $spreadsheet;
    }

    /**
     * Get spreadsheet by Id
     * @param string $id of spreadsheet
     *
     * @return object SpreadsheetFeed
     *
     * @throws Google_Exception_api
     */
    public function get_spreadsheet_by_id($id = '')
    {

        $spreadsheet = $this->spreadsheet_service->getSpreadsheetById($id);

        if (empty($spreadsheet)) {
            throw new Google_Exception_api('Spreadsheet with Id="' . $id . '" is not exist', -1);
        }

        return $spreadsheet;

    }

    /**
     * Get all sheet titles in spreadsheet
     * @param object|string $spreadsheet required Spreadsheet object, if param is string , get spreadsheet object by title
     * @return array
     * @throws Google_Exception_api
     */
    public function get_list_sheet_titles($spreadsheet = '')
    {
        $data = [];

        if (is_string($spreadsheet)) {
            $spreadsheet = $this->get_spreadsheet_by_title($spreadsheet);
        }

        $worksheet_feed = $spreadsheet->getWorksheets();

        foreach($worksheet_feed as $worksheet) {
            $data[] = $worksheet->getTitle();
        }

        return $data;
    }

    /**
     * Get list entry values in sheet by sheet title
     * @param object|string $spreadsheet required Spreadsheet object, if param is string , get spreadsheet object by title
     * @param string $sheet_title
     * @return array
     *
     * @throws Google_Exception_api
     */
    public function get_list_entries_in_sheet($spreadsheet = '', $sheet_title = '')
    {
        $data = [];

        if (is_string($spreadsheet)) {
            $spreadsheet = $this->get_spreadsheet_by_title($spreadsheet);
        }

        $worksheet_feed = $spreadsheet->getWorksheets();

        $worksheet = $worksheet_feed->getByTitle($sheet_title);

        if (empty($worksheet)) {
            throw new Google_Exception_api('Spreadsheet with title "' . $sheet_title . '" is not exist', -1);
        }

        $list_feed = $worksheet->getListFeed();

        foreach ($list_feed->getEntries() as $entry) {
            $data[] = $entry->getValues();
        }
        return $data;
    }

    /**
     * Update sheet by batch
     * @param string $spreadsheet
     * @param string $sheet_title
     * @param array $batch_datas (['row' => 1, 'col' => 1, 'value' => 'abc'])
     *
     * @return bool
     * @throws Google_Exception_api
     */
    function update_sheet_by_batch($spreadsheet = '', $sheet_title = '', $batch_datas = [])
    {
        if (empty($batch_datas)) {
            return true;
        }

        if (is_string($spreadsheet)) {
            $spreadsheet = $this->get_spreadsheet_by_title($spreadsheet);
        }

        $worksheet_feed = $spreadsheet->getWorksheets();

        $worksheet = $worksheet_feed->getByTitle($sheet_title);

        if (empty($worksheet)) {
            throw new Google_Exception_api('Spreadsheet with title "' . $sheet_title . '" is not exist', -1);
        }

        $cell_feed = $worksheet->getCellFeed();

        $batch_request = new Google\Spreadsheet\Batch\BatchRequest();

        foreach ($batch_datas AS $data) {
            $batch_request->addEntry($cell_feed->createInsertionCell($data['row'], $data['col'], $data['value']));
        }

        $batch_reponse = $cell_feed->insertBatch($batch_request);

        return $batch_reponse->hasErrors() ? FALSE : TRUE;
    }

    /**
     * Get all google sheet in folder
     * @param string $folder_id
     * @param string $return_type (title, spread) title or spread object
     *
     * @return array
     */
    function get_list_gsheet_in_drive($folder_id = '', $return_type = 'title')
    {
        $service = new Google_Service_Drive($this->client);

        $results = $service->files->listFiles([
            'q' => "'$folder_id' in parents and mimeType='application/vnd.google-apps.spreadsheet'"
        ]);

        $return = [];

        foreach ($results->getItems() as $item) {
            switch ($return_type) {
                case 'title':
                    $return[] = $item->title;
                    break;

                case 'spread':
                    $return[] = $this->get_spreadsheet_by_id($item->id);
                    break;
            }
        }

        return $return;
    }

    /**
     * Get Google Drive instance
     * 
     * @return Google_Service_Drive
     */
    public function get_drive_instance()
    {
        $instance = new Google_Service_Drive($this->client);

        return $instance;
    }
}
