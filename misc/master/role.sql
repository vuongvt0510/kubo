USE schooltv_main;

REPLACE INTO `role` (`id`, `identifier`, `name`, `order`, `created_by`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'administrator', '企画権限', 1, 'system', '2016-06-01 00:00:00', '2016-06-01 00:00:00', 'system'),
(2, 'operator', '営業権限 ', 2, 'system', '2016-06-01 00:00:00', '2016-06-01 00:00:00', 'system');

