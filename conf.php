<?php

ini_set('display_errors', 1);

/** ドメイン名 */
define('DOMAIN_NAME', '');
/** DB種別(oracle固定) */
define('DB_TYPE', 'oracle');
/** DBユーザー */
define('DB_USER', 'GL');
/** DBパスワード */
define('DB_PASSWORD', 'GL');
/** DBサーバーIPアドレス */

//define('DB_SERVER', '192.168.254.16');
define('DB_SERVER', '192.168.254.17');

// 本番　192.168.10.10

/** DBサーバーポート番号 */
define('DB_PORT', '1521');
/** DBサービス名 */
define('DB_NAME', 'ORCL.WORLD');
/** DB接続文字列定義 */
define('DB_CONNECTION_STRING', DB_SERVER . ':' . DB_PORT . '/' . DB_NAME);
/** DB出力文字コード指定 */
define('DB_CHARSET', 'AL32UTF8');

// === home ボタン URL
define('HOME_URL', 'http://192.168.254.226/gr_picking_01/top_menu.php?back_menu=ok&id=');
//define('HOME_URL', 'http://192.168.11.254:8080/p/top_menu.php?back_menu=ok&id=');
