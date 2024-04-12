<?php

ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

// セッションIDを取得
$session_id = session_id();

// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {
    // ========= 通常処理 =========
    if (isset($_GET['selectedSouko'])) {
        $selectedSouko = $_GET['selectedSouko'];
        print($selectedSouko . "<br />");

        $selected_day = $_GET['selected_day'];
        print($selected_day . "<br />");
    } else {
    }
}




?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ピッキング 03</title>
</head>

<body>

</body>

</html>