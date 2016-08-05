<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'libraries/API/Base_api.php';

/**
 * Page Control API
 *
 * @property Page_model Page_model
 * @copyright 2015- Interest Marketing, inc. (CONTACT info@interest-marketing.net)
 *
 * @author DiepHQ
 * @author IMVN Team
 */
class Page_api extends Base_api
{
    /**
     * Get page API Spec OT10, OT20, OT40, OT60, OT70, OT80
     *
     * @param array $params
     * @internal param string $key (OT10|OT20|OT40|OT60|OT70|OT80)    *
     * @return array
     */
    public function get_detail($params = [])
    {   

        // Validate
        $v = $this->validator($params);
        $v->set_rules('key', 'ページのキー', 'required');

        if (FALSE === $v->run()) {
            return $v->error_json();
        }

        // Load model
        $this->load->model('page_model');

        // Get list message
        $res = $this->page_model
            ->select('id, key, title, content, status')
            ->where('status =', 'public')
            ->where('key =', $params['key'])
            ->first();

        // Return
        return $this->true_json($this->build_responses($res));
    }   
}
