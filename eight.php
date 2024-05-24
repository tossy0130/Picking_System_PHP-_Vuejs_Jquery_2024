<?php

ini_set('display_errors', 1);

require __DIR__ . "\conf.php";

require_once(dirname(__FILE__) . "\class/init_val.php");
require(dirname(__FILE__) . "\class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

// セッションスタート
session_start();



// セッションIDが一致しない場合はログインページにリダイレクト
if (!isset($_SESSION["sid"])) {
    header("Location: index.php");
    exit;
} else {
    $session_id = $_SESSION["sid"];
}


// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {
    // ========= 通常処理 =========
    if (isset($_GET['selected_day'])) {

        // === 日付
        $selected_day = $_GET['selected_day'];
        // === 運送便
        $selected_shippingname = $_GET['selected_shippingname'];
        $selected_shippingcode = $_GET['selected_shippingcode'];
    } else {
        // === トークンが無い場合
        header("Location: $err_url");
    }


    // ============================= DB 処理 =============================
    // === 接続準備
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
    }

    $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名
            FROM SJTR SJ, SKTR SK, SOMF SO, USMF US
            WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                AND SK.倉庫Ｃ = SO.倉庫Ｃ
                AND SK.運送Ｃ = US.運送Ｃ
                AND SK.出荷日 = :POST_DATE
                AND SK.運送Ｃ = :POST_CODE
            GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名
            ORDER BY SK.倉庫Ｃ,SO.倉庫名";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($stid);
    }

    oci_bind_by_name($stid, ":POST_DATE", $selected_day);
    oci_bind_by_name($stid, ":POST_CODE", $selected_shippingcode);

    oci_execute($stid);

    //========= 結果を取得して表示
    $data = array();
    while ($row = oci_fetch_assoc($stid)) {
        // カラム名を指定して値を取得
        $syuka_day = $row['出荷日'];
        $souko_code = $row['倉庫Ｃ'];
        $souko_name = $row['倉庫名'];

        /*
        print "出荷日：" . $syuka_day . "<br />";
        print "倉庫Ｃ：" . $souko_code . "<br />";
        print "倉庫名：" . $souko_name . "<br />";
        */

        // 取得した値を配列に追加
        $arr_souko_data[] = array(
            'syuka_day' => $syuka_day,
            'souko_code' => $souko_code,
            'souko_name' => $souko_name
        );
    }

    $souko_Flg = 0;
    if (empty($arr_souko_data)) {
        $souko_Flg = 0;
        header("Location: ./first.php?souko_Flg={$souko_Flg}");
        exit(); // リダイレクト後にスクリプトの実行を終了するために必要
    } else {
        $souko_Flg = 1;
    }


    // ============================= DB 処理 END =============================
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/common.css">

    <!--
    <link rel="stylesheet" href="./css/second.css">
-->
    <link rel="stylesheet" href="./css/second_02.css">

    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forth.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <title>ピッキング実績照会倉庫選択</title>

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
                倉庫
            </span>
        </div>
    </div>

    <div id="app">
        <div class="container">
            <div class="content_02">

                <p>出荷日：<?= $selected_day; ?></p>
                <p>運送便：<?= $selected_shippingname; ?></p>
                <p>運送コード：<?= $selected_shippingcode; ?></p>
                <div class="souko_box">
                    <?php
                    // 配列内の要素をループしてボタンを生成
                    $idx = 0;
                    foreach ($arr_souko_data as $souko) {
                        echo '<div><button type="button" value="' . $souko["souko_code"] . '" @click="handleButtonClick(\'' . $souko["souko_name"] . '\', \'' . $souko["souko_code"] . '\')" :class="{\'selected_souko\' : selectedValue === \'' . $souko["souko_name"] . '\'}">' . $souko["souko_name"] . '</button></div>';
                    }
                    ?>
                </div>

                <div class="error_message" v-show="error">
                    倉庫を選択してください。
                </div>


                <div id="next_btn">
                    <button @click="submitForm">次へ</button>
                </div>

                <!-- フッターメニュー -->
                <footer class="footer-menu_fixed">
                    <ul>
                        <li><a href="#">戻る</a></li>
                        <li><a href="#">更新</a></li>
                    </ul>
                </footer>

            </div>
        </div> <!-- END container -->
    </div> <!-- END app -->

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

    <script>
        new Vue({
            el: '#app',
            data: {
                selectedValue: null, // 選択された値を保持
                selectedCode: null,
                error: false
            },
            methods: {
                // ボタンがクリックされたら
                handleButtonClick(value, code_val) {
                    this.selectedValue = value; // 選択した値を格納
                    this.selectedCode =code_val;
                    console.log("選択した値:::" + this.selectedValue);
                    console.log("選択した値:::" + this.selectedCode);
                },
                // フォームを送信する
                submitForm() {
                    const selectedSoukoName = this.selectedValue;
                    const selectedSoukoCode = this.selectedCode;
                    const selectedDay = '<?php echo $selected_day; ?>';
                    const selectedShippingName = '<?php echo $selected_shippingname; ?>';
                    const selectedShippingCode = '<?php echo $selected_shippingcode; ?>';
                    if (selectedSoukoName === null) { // 倉庫が選択されていない場合
                        this.error = true; // エラーメッセージを表示

                    } else {
                        this.error = false; // エラーメッセージを非表示に
                        // get送信
                        const url = `./nine.php?selected_day=${selectedDay}&selected_shippingname=${selectedShippingName}&selected_shippingcode=${selectedShippingCode}&selected_soukocode=${selectedSoukoCode}&selected_soukoname=${selectedSoukoName}`; // リダイレクト
                        window.location.href = url;
                    }
                }
            }
        });
    </script>

</body>

</html>