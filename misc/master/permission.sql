USE schooltv_main;

REPLACE INTO `permission` (`id`, `identifier`, `name`, `description`, `created_by`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'ALL', 'All permission', '', '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(10, 'NEWS_LIST', 'Can list all news', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(11, 'NEWS_CREATE', 'Can add news', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(12, 'NEWS_UPDATE', 'Can edit news', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(13, 'NEWS_DELETE', 'Can delete news', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(20, 'USER_LIST', 'Can list all users', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(21, 'USER_CREATE', 'Can add user', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(22, 'USER_UPDATE', 'Can edit user', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(23, 'USER_DELETE', 'Can delete user', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(24, 'USER_UPDATE_PROMOTION', 'Can update user promotion code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(25, 'USER_UPDATE_CAMPAIGN', 'Can update user campaign code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(26, 'USER_UPDATE_GROUP', 'Can update user group', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(30, 'SEARCH_INVITER', 'Can search list user inviting', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(31, 'SEARCH_PROMOTION', 'Can search list user by promotion code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(32, 'SEARCH_CAMPAIGN', 'Can search list user by campaign', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(40, 'RABIPOINT_LIST', 'Can list rabipoint user', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(41, 'RABIPOINT_CREATE', 'Can add rabipoint for user', NULL, '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', ''),
(100, 'POINT_EXCHANGE_LIST', 'Can access to list point exchange request', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(101, 'POINT_EXCHANGE_ACCEPT', 'Can accept point exchange to netmile', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(102, 'POINT_EXCHANGE_REJECT', 'Can reject point exchange to netmile', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(150, 'ADMINS_LIST', 'Can view list of administrators', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(151, 'ADMIN_CREATE_ACOUNT', 'Can create acount', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(152, 'ADMIN_EDIT_ACCOUNT', 'Can edit acount', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(153, 'ADMIN_DELETE_ACCOUNT', 'Can delete acount', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(200, 'CAMPAIGN_CODE_LIST', 'Can list campaign code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(201, 'CAMPAIGN_CODE_CREATE', 'Can add campaign code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(202, 'CAMPAIGN_CODE_UPDATE', 'Can update campaign code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', ''),
(203, 'CAMPAIGN_CODE_DELETE', 'Can delete campaign code', NULL, '', '2016-06-01 00:00:00', '2016-06-01 00:00:00', '');


