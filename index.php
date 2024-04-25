<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";

// セッションスタート
session_start();

// セッションIDを破棄してログアウトする
session_unset(); // セッション変数をクリア
session_destroy(); // セッションを破棄


// === 接続準備
$conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

if (!$conn) {
    $e = oci_error();
}

$sql = "SELECT ログインＩＤ, 暗証番号 FROM USRF";

$stid = oci_parse($conn, $sql);
if (!$stid) {
    $e = oci_error($stid);
}

oci_execute($stid);

//========= 結果を取得して表示
$data = array();
while ($row = oci_fetch_assoc($stid)) {
    // カラム名を指定して値を取得
    $login_id = $row['ログインＩＤ'];
    $pass = $row['暗証番号'];

    // ここで取得した値を処理する（例えば、表示する）
    // *** コメントアウトを外したら、値がでます **** //
    //  echo "ログインＩＤ: " . $login_id . ", 暗証番号: " . $pass . "<br/>";

    // 取得した値を配列に追加
    $user_data[] = array(
        'ID' => $login_id,
        'pass' => $pass
    );
}

// print_r($user_data);
// print($user_data[15]['ID']);

// ステートメントを解放
oci_free_statement($stid);
// 接続を閉じる
oci_close($conn);

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- css -->
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <title>ログイン</title>
</head>

<body class="body_01">

    <div id="app">

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

        <div class="container_index">
            <div>
                <!--
            <form method="post" action="./top_menu.php">        
            -->
                <form id="loginForm" action="./top_menu.php" method="post">
                    <div class="top_input_box">
                        <div>
                            <input type="number" class="index_text_box_01" name="input_login_id" id="input_login_id" placeholder="ログインIDを入力。">
                        </div>




                        <div>
                            <button type="submit" name="login_btn" id="login_btn">ログイン</button>
                            <p id="error" style="color:red"></p>
                        </div>
                    </div>
                </form>

            </div>
        </div> <!-- container_index END -->

    </div> <!-- END app -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        (function($) {
            $(document).ready(function() {

                $('#loginForm').submit(function(e) {

                    var input_login_id = $('#input_login_id').val();

                    if (input_login_id === '') {
                        $('#error').text('ログインIDを入力してください。');
                        return false;
                    } else {

                        const inputValue = $('#input_login_id').val().trim();
                        const userIDs = <?php echo json_encode(array_column($user_data, 'ID')); ?>;
                        if (userIDs.includes(inputValue)) {
                            // エラーがない場合、フォームを送信
                            return true;
                        } else {
                            $('#error').text('ログインIDが不正です。');
                            return false;
                        }
                    }

                });

            });
        })(jQuery);
    </script>

</body>

</html>