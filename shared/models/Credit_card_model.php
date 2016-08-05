<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once SHAREDPATH . 'core/APP_Model.php';

/**
 * Class Credit_card_model
 *
 * @copyright Interest Marketing,inc. (CONTACT info@interest-marketing.net)
 * @author IMVN Team
 */
class Credit_card_model extends APP_Model
{
    public $database_name = DB_MAIN;
    public $table_name = 'credit_card';
    public $primary_key = 'id';

    /**
     * Encrypt password
     *
     * @access public
     *
     * @param string $password
     *
     * @return string
     */
    public function encrypt_password($password)
    {
        return base64_encode(hash_hmac('sha256', $password, 'xE98#beD4fLd2qP3', TRUE));
    }

    /**
     * Encrypt password
     *
     * @access public
     *
     * @param string $password
     *
     * @return string
     */
    public function check_password($id, $user_id, $password)
    {

        $check_password = $this->credit_card_model->find_by([
            'id' => $id,
            'user_id' => $user_id,
            'password' => $this->encrypt_password($password)
        ]);
        return !$check_password ? FALSE : TRUE;
    }

    /**
     * @param int $user_id
     *
     * @return string
     */
    public function generate_gmo_member_id($user_id)
    {
        return md5('elearning_'. $user_id . '_xE98beD4fLd2qP3');
    }

    /**
     * Detect credit card type by credit card number
     *
     * @param string $number of credit card
     *
     * @return bool|string
     *
     * @see http://en.wikipedia.org/wiki/Bank_card_number#Issuer_Identification_Number_.28IIN.29
     */
    public function detect_credit_card($number = '')
    {
        $strlen = strlen($number);

        // Provide a check on the first digit (and card length if applicable).
        switch (substr($number, 0, 1)) {
            case '3':
                // American Express begins with 3 and is 15 numbers.
                if ($strlen == 15) {
                    return 'AMEX';
                }

                // JCB begins with 3528-3589 and is 16 numbers.
                if ($strlen == 16) {
                    return 'JCB';
                }

                // Carte Blanche begins with 300-305 and is 14 numbers.
                // Diners Club International begins 36 and is 14 numbers.
                if ($strlen == 14) {
                    $initial = (int) substr($number, 0, 3);

                    if ($initial >= 300 && $initial <= 305) {
                        return 'CB';
                    }

                    if (substr($number, 0, 2) == '36') {
                        return 'DCI';
                    }
                }

                return FALSE;

            case '4':
                $initial = (int) substr($number, 0, 4);
                $return = FALSE;

                if ($strlen == 16) {
                    // Visa begins with 4 and is 16 numbers.
                    $return = 'VISA';

                    // Visa Electron begins with 4026, 417500, 4256, 4508, 4844, 4913, or
                    // 4917 and is 16 numbers.
                    if (in_array($initial, array(4026, 4256, 4508, 4844, 4913, 4917)) || substr($number, 0, 6) == '417500') {
                        $return = 'VISAELECTRON';
                    }
                }

                // Switch begins with 4903, 4905, 4911, or 4936 and is 16, 18, or 19
                // numbers.
                if (in_array($strlen, array(16, 18, 19)) &&
                    in_array($initial, array(4903, 4905, 4911, 4936))) {
                    $return = 'SWITCH';
                }

                return $return;

            case '5':
                // MasterCard begins with 51-55 and is 16 numbers.
                // Diners Club begins with 54 or 55 and is 16 numbers.
                if ($strlen == 16) {
                    $initial = (int) substr($number, 0, 2);

                    if ($initial >= 51 && $initial <= 55) {
                        return 'MASTERCARD';
                    }

                    if ($initial >= 54 && $initial <= 55) {
                        return 'DC';
                    }
                }

                // Switch begins with 564182 and is 16, 18, or 19 numbers.
                if (substr($number, 0, 6) == '564182' && in_array($strlen, array(16, 18, 19))) {
                    return 'SWITCH';
                }

                // Maestro begins with 5018, 5020, or 5038 and is 12-19 numbers.
                if ($strlen >= 12 && $strlen <= 19 && in_array(substr($number, 0, 4), array(5018, 5020, 5038))) {
                    return 'MAESTRO';
                }

                return FALSE;

            case '6':
                // Discover begins with 6011, 622126-622925, 644-649, or 65 and is 16
                // numbers.
                if ($strlen == 16) {
                    if (substr($number, 0, 4) == '6011' || substr($number, 0, 2) == '65') {
                        return 'DISCOVER';
                    }

                    $initial = (int) substr($number, 0, 6);

                    if ($initial >= 622126 && $initial <= 622925) {
                        return 'DISCOVER';
                    }

                    $initial = (int) substr($number, 0, 3);

                    if ($initial >= 644 && $initial <= 649) {
                        return 'DISCOVER';
                    }
                }

                // Laser begins with 6304, 6706, 6771, or 6709 and is 16-19 numbers.
                $initial = (int) substr($number, 0, 4);

                if ($strlen >= 16 && $strlen <= 19 && in_array($initial, array(6304, 6706, 6771, 6709))) {
                    return 'LASER';
                }

                // Maestro begins with 6304, 6759, 6761, or 6763 and is 12-19 numbers.
                if ($strlen >= 12 && $strlen <= 19 && in_array($initial, array(6304, 6759, 6761, 6763))) {
                    return 'MAESTRO';
                }

                // Solo begins with 6334 or 6767 and is 16, 18, or 19 numbers.
                if (in_array($strlen, array(16, 18, 19)) && in_array($initial, array(6334, 6767))) {
                    return 'SOLO';
                }

                // Switch begins with 633110, 6333, or 6759 and is 16, 18, or 19 numbers.
                if (in_array($strlen, array(16, 18, 19)) && (in_array($initial, array(6333, 6759)) || substr($number, 0, 6) == 633110)) {
                    return 'SWITCH';
                }

                return FALSE;
        }

        return FALSE;
    }
}