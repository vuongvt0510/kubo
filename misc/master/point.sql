USE schooltv_main;

REPLACE INTO `point` (`id`, `case`, `title_modal`, `base_point`, `condition`, `type`, `campaign`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES
(1, 'new_registration', '会員登録完了', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 06:48:53', '', NULL, NULL),
(2, 'new_registration_by_forceclub', 'フォルスクラブ紹介ボーナス', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:56', '', NULL, NULL),
(3, 'watch_tutorial', 'はじめてスクールTVの動画を視聴', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:56', '', NULL, NULL),
(4, 'join_family', '家族グループに参加', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:56', '', NULL, NULL),
(5, 'both_register', '親子で同時会員登録', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(6, 'invite_friend', '友達を招待', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(7, 'become_friend', '友達ができた', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(8, 'more_friends', '友達10人達成', 1000, 10, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(9, 'more_friends', '友達20人達成', 1000, 20, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(10, 'more_friends', '友達30人達成', 1000, 30, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(11, 'more_friends', '友達50人達成', 1000, 50, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(12, 'more_friends', '友達100人達成', 1000, 100, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(13, 'create_team', 'チーム作成', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(14, 'invite_team', '友達がチームに参加', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(15, 'join_team', 'チームに参加', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(16, 'trial_play', 'お試しプレイボーナス', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:03:57', '', NULL, NULL),
(17, 'ask_coin', 'コインをおねだり', 5000, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(18, 'register_profile', 'プロフィール登録', 200, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(19, 'dashboard', 'ダッシュボードにアクセス', 200, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(20, 'send_message', 'メッセージを送信', 500, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-24 09:21:04', '', NULL, NULL),
(21, 'send_good', '友達にGood', 500, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(22, 'watch_video', '動画をウォッチ（初回）', 200, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-24 03:49:32', '', NULL, NULL),
(23, 'watch_2nd_video', '動画をウォッチ（本日2回目）', 100, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-24 03:53:08', '', NULL, NULL),
(24, 'watch_video_every_time', '動画をウォッチ', 50, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(25, 'first_login', 'ログインボーナス', 50, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(26, 'watch_video_continuously', '動画をウォッチ（連日）', 50, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-24 03:52:19', '', NULL, NULL),
(27, 'video_score', '動画ドリル初回プレイボーナス', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(28, 'video_correct_answer', '動画ドリル正解ボーナス', 3, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(29, 'video_score_every_time', '動画ドリルプレイボーナス', 100, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(30, '1st_ranking', 'ランキング1位', 1500, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(31, '2nd_10th_ranking', 'ランキング2～10位以内', 1000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(32, '11th_50th_ranking', 'ランキング11～50位以内', 500, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(33, '51th_100th_ranking', 'ランキング51～100位以内', 200, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(34, 'score_video_2nd', '動画ドリルプレイボーナス（本日2回目）', 100, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(35, 'play_battle_1st', 'スクールTVドリル初回プレイボーナス', 50, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(36, 'play_battle_continuously', 'スクールTVドリル連日プレイボーナス', 25, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(37, 'play_all_answers_correct', 'スクールTVドリル ステージ全問正解ボーナス', 2000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(38, 'play_battle_high_score', 'ハイスコアボーナス', 20, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(39, 'play_battle_rank_up', 'ランクアップボーナス', 20, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(40, 'play_battle_win', 'スクールTVドリル バトル勝利ボーナス', 1, NULL, 'continuous', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(41, 'download_decks', 'ドリルをゲット', 2000, 10, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(42, 'download_decks', 'ドリルをゲット', 2000, 20, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(43, 'download_decks', 'ドリルをゲット', 2000, 30, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(44, 'monthly_payment', 'スクールTV Plusに入会', 5000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(45, 'monthly_payment_1week', 'スクールTV Plusに入会（早期入会ボーナス）', 5000, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-03 07:05:04', '', NULL, NULL),
(46, 'admin_creation', 'スクールTVから付与', 0, NULL, 'single', 1, '2016-06-01 15:20:32', '', '2016-06-24 03:56:17', '', NULL, NULL);
