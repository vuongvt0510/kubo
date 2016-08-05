<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/Application_controller.php';

/**
 * Pay_service controller
 *
 * @author Duy Phan <yoshikawa@interest-marketing.net>
 */
class Static_page extends Application_controller
{   
    public $layout = "layouts/base";

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->_before_filter('_require_login', [
            'except' => ['about', 'rules', 'faq', 'about_payment', 'attention']
        ]);
    }

    // OT10 temp
    public function rules()
    {
        $view_data['meta'] = [
            'title' => "利用規約 | 無料の動画で授業の予習・復習をするならスクールTV",
            'description' => 'スクールTVの利用規約です。'
        ];
        $this->_render($view_data);
    }

    // OT20 temp
    public function about()
    {
        $view_data['meta'] = [
            'title' => "スクールTVの使い方 | 無料の動画で授業の予習・復習をするならスクールTV",
            'description' => 'スクールTVの使い方です。いつの間にか学習習慣が身につく新感覚の学習スタイルが登場!'
        ];
        $this->_render($view_data);
    }

    // OT40 temp
    public function faq()
    {
        $view_data['meta'] = [
            'title' => "FAQ | 無料の動画で授業の予習・復習をするならスクールTV",
            'description' => 'スクールTVのよくあるご質問です。'
        ];
        $this->_render($view_data);
    }
    
    // OT70 temp
    public function about_payment()
    {
        $view_data['meta'] = [
            'title' => "資金決済法 | 無料の動画で授業の予習・復習をするならスクールTV",
            'description' => 'スクールTVの資金決済法です。'
        ];
        $this->_render($view_data);
    }

    // OT80 temp
    public function attention()
    {
        $view_data['meta'] = [
            'title' => "特定商取引法 | 無料の動画で授業の予習・復習をするならスクールTV",
            'description' => 'スクールTVの特定商取引法です。'
        ];
        $this->_render($view_data);
    }
}
