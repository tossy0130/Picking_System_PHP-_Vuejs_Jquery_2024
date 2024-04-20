<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";

// セッションスタート
session_start();

// セッションIDを取得
$sid = session_id();
print("session_id:::" . $sid . "<br />");

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


    <title>ログイン</title>
</head>

<body class="body_01">

 

    <div id="app">
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
            <!--
            <form method="post" action="./top_menu.php">        
            -->
            <form @submit.prevent="submitForm">

                <div class="top_input_box">
                    <div>
                        <input type="number" class="text_box_01" name="input_login_id" v-model="loginId">
                    </div>

                    <div>
                        <button type="submit" name="login_btn" id="login_btn">ログイン</button>
                        <p v-if="error !== ''" style="color:red">{{ error }} </p>
                    </div>
                </div>
            </form>

        </div>

    </div> <!-- END app -->

    <!-- Vue.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
    <script>
        // PHPで取得した$user_dataをJavaScriptの変数に変換
        const userData = <?php echo json_encode($user_data); ?>

        new Vue({
            el: '#app',
            data: {
                loginId: '',
                user_data: userData,
                error: ''
            },
            methods: {
                submitForm() {
                    // ログインIDが空かどうかチェック
                    if (this.loginId.trim() == '') {
                        this.error = 'ログインIDを入力してください。';
                        return;
                    }

                    // $user_data IDが含まれているかどうかの判定
                    if (this.user_data.some(user => user.ID === this.loginId)) {

                        // フォームデータをPOST
                        const formData = new FormData();
                        formData.append('input_login_id', this.loginId);
                        fetch('./top_menu.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                console.log('リダイレクト成功。レスポンス:', response);
                                window.location.href = './top_menu.php';
                            })
                            .catch(error => {
                                console.error('リダイレクトエラー:', error);
                            });

                    } else {
                        this.error = 'ログインIDが不正です。';
                    }
                }
            }
        });
    </script>

</body>

</html>