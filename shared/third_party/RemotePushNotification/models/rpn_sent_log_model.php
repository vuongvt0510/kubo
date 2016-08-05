<?php

require_once "rpn_queue_base_model.php";

/**
 * プッシュ通知ログモデル
 *
 * @author Yoshikazu Ozawa
 */
class Rpn_sent_log_model extends Rpn_queue_base_model {
    var $database_name = "remote_push_notification";
    var $table_name = "rpn_sent_log";
    var $primary_key = "id";
}

