<?php

ini_set('display_errors', 1);

require_once __DIR__ . "./class/function.php";

// セッションスタート
session_start();

// セッションIDを取得
$sid = session_id();
$_SESSION["sid"] = session_id();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submitted_token = $_POST['csrf_token'];

    $input_login_id = $_POST['input_login_id'];
} else {
    header("Location: index.php");
    exit; // リダイレクト後にスクリプトの実行を終了するために必要
}


?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- css -->
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">
    <link rel="stylesheet" href="./css/top_menu.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <title>トップメニュー</title>
</head>

<body>

    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="#"><i class="fa-solid fa-house"></i></a>
            </span>

            <span class="App_name">
                APP ピッキングアプリ
            </span>
        </div>
    </div>


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <i class="fa-solid fa-thumbtack"></i>
            </span>

            <span class="page_title">
                ログイン
            </span>
        </div>
    </div>

    <!-- ====================  メニュー ===================== -->
    <div class="container_menu">

        <p class="login_view">ログインID:<span id="login_id">
                <?php print h($input_login_id); ?>
            </span>
        </p>

        <div class="content_menu">

            <div>
                <a href="./first.php">ピッキング</a>
            </div>

            <div>
                <a href="#">ピッキング実績照会</a>
            </div>


        </div> <!-- content_menu END -->
    </div>

</body>

</html>