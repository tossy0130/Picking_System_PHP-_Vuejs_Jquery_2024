<?php

ini_set('display_errors', 1);

require __DIR__ . "/conf.php";
require_once(dirname(__FILE__) . "/class/init_val.php");
require(dirname(__FILE__) . "/class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

// セッションIDが一致しない場合はログインページにリダイレクト
if (!isset($_SESSION["sid"])) {
    header("Location: index.php");
    exit;
} else {
    $session_id = $_SESSION['sid'];
}

// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {
    // ========= 通常処理 =========
    if (isset($_GET['selected_day'])) {
        $selected_day = $_GET['selected_day'];
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
/* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/21
    $sql = "SELECT SJ.出荷日,SJ.運送Ｃ,US.運送略称
            FROM SJTR SJ,SKTR SK,USMF US, HTPK PK, SLTR SL
            WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
            AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
            AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
            AND SJ.運送Ｃ = US.運送Ｃ
            AND SJ.運送Ｃ = PK.運送Ｃ
            AND SJ.出荷日 = :POST_DATE
            AND PK.処理Ｆ = 9
            GROUP BY SJ.出荷日,SJ.運送Ｃ,US.運送略称
            ORDER BY SJ.運送Ｃ";
*/
    $sql = "SELECT SK.出荷日,SK.運送Ｃ,US.運送略称
              FROM SJTR SJ,SKTR SK,USMF US, HTPK PK, SLTR SL
             WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
               AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
               AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
               AND SK.運送Ｃ = US.運送Ｃ
               AND SK.運送Ｃ = PK.運送Ｃ
               AND SK.出荷日 = :POST_DATE
               AND PK.処理Ｆ = 9
             GROUP BY SK.出荷日,SK.運送Ｃ,US.運送略称
             ORDER BY SK.運送Ｃ";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($stid);
    }
    
    oci_bind_by_name($stid, ":POST_DATE", $selected_day);

    oci_execute($stid);

    // 結果を取得して表示
    $data = array();
    while ($row = oci_fetch_assoc($stid)) {
        // カラム名を指定して値を取得
        $syuka_day = $row['出荷日'];
        $unsou_code = $row['運送Ｃ'];
        $unsou_name = $row['運送略称'];

        // 取得した値を配列に追加
        $arr_unsou_data[] = array(
        'syuka_day' => $syuka_day,
        'unsou_code' => $unsou_code,
        'unsou_name' => $unsou_name
        );
    }

    $unsou_Flg = 0;
    if (empty($arr_unsou_data)) {
        $unsou_Flg = 0;

        header("Location: ./six.php?unsou_Flg={$unsou_Flg}&error_day={$selected_day}");
        exit(); // リダイレクト後にスクリプトの実行を終了するために必要
    } else {
        $unsou_Flg = 1;
    }
}


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/seven.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forth.css">

    <link href="./css/all.css" rel="stylesheet">

    <title>ピッキング実績照会運送便選択</title>

</head>

<body>

    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="./top_menu.php"><img src="./img/home_img.png"></a>
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
                運送便選択(ピッキング実績照会)
            </span>
        </div>
    </div>



    <div id="app">
        <div class="container">

            <div class="unsou_box">
                <?php
                $idx = 1;
                foreach ($arr_unsou_data as $unsou) {
                    echo '<div><button type="button" value="' . $unsou["unsou_name"] . '" @click="handleButtonClick(\'' . $unsou["unsou_name"] . '\', \'' .$unsou["unsou_code"] . '\')" :class="{\'selected_unsou\' : selectedValue === \'' . $unsou["unsou_name"] . '\'}">' . $unsou["unsou_name"] . '</button></div>';
                }
                $idx++;
                ?>
            </div>
        
            <div class="error_message" v-show="error">
                運送便を選択してください。
            </div>

            <div id="next_btn">
                <button @click="submitForm">次へ</button>
            </div>

            <!-- ページの表示内容 -->
            <div class="selected-item">
                <!-- 選択された項目の表示 -->
            </div>

            <!-- フッターメニュー -->
            <footer class="footer-menu_fixed">
                <ul>
                    <?php $back_flg = 1; ?>
                    <?php $url = "./six.php?back_six=ok&day=" . $selected_day; ?>
                    <li><a href="<?php print h($url); ?>">戻る</a></li>
                    <?php $url = "./seven.php?selected_day=" . UrlEncode_Val_Check($selected_day);?>
                    <li><a href="<?php print $url; ?>">更新</a></li>
                </ul>
            </footer>
        </div> <!-- END container -->
    </div> <!-- END app -->

    <script src="./js/vue@2.js"></script>

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
                    this.selectedCode = code_val;
                },
                // フォームを送信する
                submitForm() {
                    const selectedShippingName = this.selectedValue;
                    const selectedDay = '<?php echo $selected_day; ?>';
                    const selectedShippingCode = this.selectedCode;
                    if (selectedShippingName === null) { // 運送便が選択されていない場合
                        this.error = true; // エラーメッセージを表示

                    } else {
                        this.error = false; // エラーメッセージを非表示に
                        // get送信
                        const url = `./eight.php?selected_day=${selectedDay}&selected_shippingname=${selectedShippingName}&selected_shippingcode=${selectedShippingCode}`; // リダイレクト
                        window.location.href = url;
                    }
                }
            }
        });
    </script>

</body>

</html>