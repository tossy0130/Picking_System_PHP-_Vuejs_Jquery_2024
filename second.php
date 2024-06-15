<?php

ini_set('display_errors', 1);

require __DIR__ . "/conf.php";

require_once(dirname(__FILE__) . "/class/init_val.php");
require(dirname(__FILE__) . "/class/function.php");

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
        //echo $selected_day;
    } else {
        // === トークンが無い場合
        header("Location: $err_url");
    }

    // セッション削除
    unset($_SESSION['souko_code']);
    unset($_SESSION['souko_name']);
    unset($_SESSION['selectedSouko']);

    // ============================= DB 処理 =============================
    // === 接続準備
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
    }
    //24/05/24
    //    $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名
    //	            FROM SJTR SJ,SKTR SK,SOMF SO
    //	               WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
    //	               AND SK.倉庫Ｃ = SO.倉庫Ｃ
    //	               AND SJ.出荷日 = :POST_DATE
    //	            GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名";
    $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称 AS 倉庫名
              FROM SJTR SJ,SKTR SK,SLTR SL,SOMF SO
             WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
               AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
               AND SK.伝票行番号 = SL.伝票行番号
               AND SL.倉庫Ｃ = SO.倉庫Ｃ
               AND SJ.出荷日 = :POST_DATE
             GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($stid);
    }

    oci_bind_by_name($stid, ":POST_DATE", $selected_day);

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
        // 2024/06/12 該当しない出荷日をパラメータで渡す
        header("Location: ./first.php?souko_Flg={$souko_Flg}&error_day={$selected_day}");
        exit(); // リダイレクト後にスクリプトの実行を終了するために必要
    } else {
        $souko_Flg = 1;
    }


    if (isset($_GET['back_flg'])) {
        $back_flg = $_GET['back_flg'];
        // print($back_flg);
    }

    // 戻るボタンを押したときに選択した日付を戻す
    if (isset($_GET['back_first']) && $_GET['back_first'] === 'ok') {
        $url = "first.php?back_first=ok&day=" . urlencode($selected_day);
        header("Location: $url");
        exit;
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

    <link href="./css/all.css" rel="stylesheet">

    <title>ピッキング 倉庫選択</title>

</head>

<body>


    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="#"><img src="./img/home_img.png"></a>
            </span>

            <span class="App_name">
                グリーンライフ ピッキング
            </span>
        </div>
    </div>


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <a href="#"><img src="./img/page_img.png"></a>
            </span>

            <span class="page_title">
                ピッキング倉庫
            </span>
        </div>
    </div>

    <div id="app">
        <div class="container">
            <div class="content_02">

                <p id="syuka_day">出荷日：<?= $arr_souko_data[0]["syuka_day"]; ?></p>

                <div class="souko_box">
                    <?php
                    // 配列内の要素をループしてボタンを生成
                    $idx = 0;
                    foreach ($arr_souko_data as $souko) {
                        echo '<div><button type="button" value="' . $souko["souko_code"] . '" @click="handleButtonClick(\'' . $souko["souko_code"] . '\', \'' . $souko["souko_name"] . '\')" :class="{\'selected_souko\' : selectedValue === \'' . $souko["souko_code"] . '\'}">' . $souko["souko_name"] . '</button></div>';
                    }
                    ?>
                </div>

                <div class="error_message" v-show="error">
                    倉庫を選択してください。
                </div>


                <div id="next_btn">
                    <button @click="submitForm">次へ</button>
                </div>



            </div>
        </div> <!-- END container -->


        <div>
            <!-- フッターメニュー -->
            <footer class="footer-menu_fixed">
                <ul>
                    <?php $back_flg = 1; ?>
                    <?php $url = "./first.php?back_first=ok&day=" . $selected_day; ?>
                    <li><a href="<?php print h($url); ?>">戻る</a></li>
                    <li><a href="#">更新</a></li>
                </ul>
            </footer>
        </div>

    </div> <!-- END app -->

    <script src="./js/vue@2.js"></script>

    <script>
        new Vue({
            el: '#app',
            data: {
                selectedValue: null, // 選択された値を保持
                selectedName: '',
                error: false
            },
            methods: {
                // ボタンがクリックされたら
                handleButtonClick(value, name_val) {
                    this.selectedValue = value; // 選択した値を格納
                    this.selectedName = name_val;

                    console.log("選択した値:::" + this.selectedValue);
                    console.log("選択した値:::" + this.selectedName);
                },
                // フォームを送信する
                submitForm() {
                    const selectedSouko = this.selectedValue;
                    const selectedSouko_name = this.selectedName;

                    const selectedDay = '<?php echo $selected_day; ?>';
                    if (selectedSouko === null) { // 倉庫が選択されていない場合
                        this.error = true; // エラーメッセージを表示

                    } else {
                        this.error = false; // エラーメッセージを非表示に
                        // get送信
                        const url = `./third.php?selectedSouko=${selectedSouko}&selected_day=${selectedDay}&souko_name=${selectedSouko_name}`; // リダイレクト
                        window.location.href = url;
                    }
                }
            }
        });
    </script>

</body>

</html>