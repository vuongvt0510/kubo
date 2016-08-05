<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|   example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|   http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|   $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|   $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'top';
$route['404_override'] = '';

// Images
$route['images/(:key)'] = "image/show/$1/$2";
$route['images/(:key)/(:type)'] = "image/show/$1/$2";

$route['s/(:key)(/(:any))?'] = "subject/detail/$1/$2";
$route['g/(:key)(/(:key))?'] = "grade/detail/$1/$2";
$route['v/(:key)'] = "video/detail/$1";

// Sitemap
$route['sitemap.xml'] = "sitemap/index";
$route['sitemap/db10'] = 'sitemap/db10';
$route['sitemap/(:bucket_key)'] = "sitemap/download/$1";

$route['login/forget_id/complete'] = "login/forget_id_complete";
$route['login/forget_password/complete'] = "login/forget_password_complete";
$route['login/reset_password/complete'] = "/login/reset_password_complete";

$route['school/search/postalcode'] = "school/search_postalcode";
$route['school/search/keyword'] = "school/search_keyword";
$route['school/search/(:any)'] = "school/search/$1";
$route['school/search/postalcode/(:any)'] = "school/search_postalcode/$1";
$route['school/search/keyword/(:any)'] = "school/search_keyword/$1";

$route['textbook/(:id)'] = "textbook/index/$1";
$route['textbook/search/(:key)/keyword'] = "textbook/search_keyword/$1";
$route['textbook/search/(:key)/complete'] = "textbook/complete/$1";
$route['textbook/search/(:key)'] = "textbook/search/$1";

$route['group_setting/(:group_id)/add_student/confirm/(:student_id)'] = "group_setting/add_student_confirm/$1/$2";
$route['group_setting/(:group_id)/add_student/complete/(:student_id)'] = "group_setting/add_student_complete/$1/$2";
$route['group_setting/(:group_id)/add_student'] = "group_setting/add_student/$1";
$route['group_setting/(:group_id)/delete_student'] = "group_setting/delete_student/$1";
$route['group_setting/(:group_id)/delete_student/confirm/(:student_id)'] = "group_setting/delete_student_confirm/$1/$2";
$route['group_setting/(:group_id)/recommend_student'] = "group_setting/recommend_student/$1";
$route['group_setting/(:group_id)/recommend_student/sent'] = "group_setting/recommend_student_sent/$1";
$route['group_setting/(:group_id)/recommend_student/confirm'] = "group_setting/recommend_student_confirm/$1";
$route['group_setting/(:group_id)/add_parent'] = "group_setting/add_parent/$1";
$route['group_setting/(:group_id)/add_parent/confirm'] = "group_setting/add_parent_confirm/$1";
$route['group_setting/(:group_id)/student'] = "group_setting/student/$1";
$route['group_setting/(:group_id)/update_name'] = "group_setting/update_name/$1";

$route['dashboard/(:id)'] = "dashboard/index/$1";

$route['timeline/detail/(:id)'] = "timeline/detail/$1";
$route['timeline/(:id)'] = "timeline/index/$1";
$route['timeline/friend/(:id)'] = "timeline/friend/$1";

$route['friend/(:id)'] = "friend/index/$1";

$route['news/detail/{news_id}'] = "news/detail/$1";
$route['update_password'] = "login/reset_password";
$route['verify_email'] = "register/verify_email";

$route['p/(:any)'] = "profile/detail";
$route['p/(:any)/history'] = 'profile/history';

$route['message'] = "message/index";
$route['message/get_list_new_message'] = "message/get_list_new_message";
$route['message/friend_list'] = "message/friend_list";
$route['message/list_message_old'] = "message/list_message_old";
$route['message/(:id)'] = "message/timeline/$1";
$route['message/team_invite/(:id)/(:id)'] = "message/team_invite/$1/$2";
$route['message/(:id)/group_members'] = "message/group_members/$1";


$route['deck/(:num)'] = "deck/detail/$1";

$route['coin/(:id)'] = "coin/index/$1";
$route['coin/(:id)/password'] = "coin/password/$1";
$route['coin/(:id)/purchase'] = "coin/purchase/$1";

$route['pay_service/(:id)'] = "pay_service/index/$1";
$route['pay_service/(:id)/purchase'] = "pay_service/purchase/$1";
$route['pay_service/(:id)/cancel'] = "pay_service/cancel/$1";

$route['play/(:num)/(:any)'] = "play/index/$1/$2";
$route['play/(:num)'] = "play/index/$1";
$route['play/select_stage/(:num)'] = "play/select_stage/$1";
$route['play/select_player/(:num)'] = "play/select_player/$1";
$route['play/select_team/(:num)'] = "play/select_team/$1";
$route['play/select_quest/(:num)'] = "play/select_quest/$1";
$route['play/quest_detail/(:any)'] = "play/quest_detail/$1";
$route['play/result_match'] = "play/result_match";
$route['play/memorize_result/(:any)'] = "play/memorize_result/$1";

// Team battle
$route['play/team/battle/history'] = "play/history"; // VS240
$route['play/team/battle/result'] = "play/result_team_battle"; // VS240

$route['play/team/battle/room'] = "play/room/"; // VS230
$route['play/team/battle/room/(:num)'] = "play/room/$1"; // VS230

$route['play/team/battle/check_target_group'] = "play/check_target_group/"; // VS223
$route['play/team/battle/change_opponent'] = "play/change_opponent/"; // VS223
$route['play/team/battle/search_opponent'] = "play/search_opponent/"; // VS223
$route['play/team/battle/search_opponent/(:num)'] = "play/search_opponent/$1"; // VS223

$route['play/team/battle/opponent'] = "play/opponent/"; // VS220
$route['play/team/battle/opponent/(:num)'] = "play/opponent/$1"; // VS220

$route['play/team/battle/my_team'] = "play/my_team/"; // VS210
$route['play/team/battle/my_team/(:num)'] = "play/my_team/$1"; // VS210

$route['play/team/battle'] = "play/team_battle"; // VS200
$route['play/team'] = "play/team"; // VS100
// 

$route['rules']         = "static_page/rules";
$route['about']         = "static_page/about";
$route['faq']           = "static_page/faq";
$route['about_payment'] = "static_page/about_payment";
$route['attention']     = "static_page/attention";

$route['trophy/(:id)'] = "trophy/index/$1";

$route['inquiry'] = "inquiry/index";

$route['team'] = "team/index";
$route['team/(:id)'] = "team/menu/$1";
$route['team/create'] = "team/create";
$route['team/(:id)/invite_friend'] = "team/invite_friend/$1";
$route['team/(:id)/add_member'] = "team/add_member/$1";
$route['team/(:id)/update'] = "team/update/$1";
$route['team/(:id)/invite_new_user'] = "team/invite_new_user/$1";
$route['team/(:id)/leave'] = "team/leave/$1";

// exchange point PTXX
$route['rabipoint/(:num)'] = "rabipoint/index/$1";
$route['rabipoint/exchange/(:id)'] = "rabipoint/exchange/$1";
$route['content/(:num)'] = "content/index/$1";

