<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";

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

// 結果を取得して表示
$data = array();
while ($row = oci_fetch_assoc($stid)) {
    // カラム名を指定して値を取得
    $login_id = $row['ログインＩＤ'];
    $pass = $row['暗証番号'];

    // ここで取得した値を処理する（例えば、表示する）
    // *** コメントアウトを外したら、値がでます **** //
    echo "ログインＩＤ: " . $login_id . ", 暗証番号: " . $pass . "<br/>";

    // 取得した値を配列に追加
    $user_data[] = array(
        'ID' => $login_id,
        'pass' => $pass
    );
}

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


    <title>ログイン</title>
</head>

<body class="body_01">

    <div class="app_name_box">
        <h1 class="app_name">
            APP アプリ名
        </h1>
    </div>

    <div class="page_title_box">
        <h2 class="page_title">
            ログイン
        </h2>
    </div>

    <div>
        <form method="post" action="./top_menu.php">

            <div class="top_input_box">
                <div>
                    <input type="number" class="text_box_01" name="input_login_id">
                </div>

                <div>
                    <button type="submit" name="login_btn" id="login_btn">ログイン</button>
                </div>
            </div>
        </form>

    </div>

</body>

</html>