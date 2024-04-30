<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";
require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

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

    // 値取得
    if (isset($_GET['select_day'])) {

        // ベタ打ち　テストデータ
        $test_data = [
            "L-4",
            "1000",
            "ホースリール",
            "PRQC-30",
            "4971715",
            "123456",
            "4",
            "1",
            "1",
            "1",
            "取扱注意",
            "甲信越福山",
            "アヤハ × 1",
            "カインズ × 2",
            "ムサシ × 1"
        ];

        // GET urlパラメーター取得
        $select_day = $_GET['select_day'];
        $souko_code = $_GET['souko_code'];
        $unsou_code = $_GET['unsou_code'];
        $unsou_name = $_GET['unsou_name'];
        $shipping_moto = $_GET['shipping_moto'];
        $shipping_moto_name = $_GET['shipping_moto_name'];
        $Shouhin_code = $_GET['Shouhin_code'];
        $Shouhin_name = $_GET['Shouhin_name'];
        $Shouhin_num = $_GET['Shouhin_num'];
        $Tokuisaki_name = $_GET['Tokuisaki'];
        $Tana_num = $_GET['tana_num'];
        $case_num = $_GET['case_num'];
        $bara_num = $_GET['bara_num'];

        // === 40バイトで分ける
        // 商品名
        $Shouhin_name_part1 = mb_substr($Shouhin_name, 0, 40);
        // 品番
        $Shouhin_name_part2 = mb_substr($Shouhin_name, 40);

        print("01:::" . $Shouhin_name_part1 . "<br />");
        print("02:::" . $Shouhin_name_part2 . "<br />");

        $arr_Tokuisaki_name = [];
        $arr_Tokuisaki_name = SplitString_FUNC($Tokuisaki_name);

        print_r($arr_Tokuisaki_name);

        // 取得データ
        $Shouhin_Detail_DATA = [
            $Tana_num,
            "",
            $Shouhin_name_part1,
            $Shouhin_name_part2,
            "4971715",      // 商品コード 01 （JAN）
            $Shouhin_code, // 商品コード 02
            $Shouhin_num,  // 数量
            $case_num,    // ケース数
            $bara_num,    // バラ数
            "1",
            $shipping_moto_name,
            $unsou_name,
            "アヤハ × 1",
            "カインズ × 2",
            "ムサシ × 1"
        ];


        // ============================= インサート用データ 取得 =============================

        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        $sql = "SELECT SL.伝票番号, SL.伝票行番号, SL.伝票行枝番, SL.商品Ｃ, SL.倉庫Ｃ,SL.出荷元,SL.数量
    FROM SLTR SL
    WHERE SL.商品Ｃ = :syouhin_Code and SL.倉庫Ｃ = :souko_Code
    AND ROWNUM = 1
        ORDER BY SL.伝票番号 DESC";


        $stid = oci_parse(
            $conn,
            $sql
        );
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":syouhin_Code", $Shouhin_code);
        oci_bind_by_name($stid, ":souko_Code", $souko_code);

        oci_execute($stid);

        $arr_Insert_Picking = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $IN_Dennpyou_num = $row['伝票番号'];
            $IN_Dennpyou_Gyou_num = $row['伝票行番号'];
            $IN_Dennpyou_Eda_num = $row['伝票行枝番'];
            $IN_Syouhin_Code = $row['商品Ｃ'];
            $IN_Souko_Code = $row['倉庫Ｃ'];
            $IN_Syukamoto = $row['出荷元'];
            $IN_Syuka_Yotei_num = $row['数量'];

            print("伝票番号:::" . $IN_Dennpyou_num . "<br />");
            print("伝票行番号:::" . $IN_Dennpyou_Gyou_num . "<br />");
            print("伝票行枝番:::" . $IN_Dennpyou_Eda_num . "<br />");
            print("商品Ｃ:::" . $IN_Syouhin_Code . "<br />");
            print("倉庫Ｃ:::" . $IN_Souko_Code . "<br />");
            print("出荷元:::" . $IN_Syukamoto . "<br />");
            print("数量:::" . $IN_Syuka_Yotei_num . "<br />");
        }
    }

    // スキャンからのGETパラメータ
    if (isset($_['scan_b'])) {
        $scan_b = $_GET['scan_b'];
    }
}



?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">
    <link rel="stylesheet" href="./css/five.css">
    <link rel="stylesheet" href="./css/common.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <title>ピッキング 05（詳細）</title>

    <style>
        #scan_val_box {
            display: block;
        }
    </style>

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
    </div> <!-- ===============  head_box END =============== -->


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <i class="fa-solid fa-thumbtack"></i>
            </span>

            <span class="page_title">
                ピッキング詳細画面
            </span>
        </div>
    </div> <!-- ===============  head_box_02 END =============== -->


    <div class="container_detail">
        <div class="content_detail">

            <div class="detail_item_01_box">
                <p><span class="detail_midashi">ロケ：</span><?php print $Shouhin_Detail_DATA[0]; ?></p>
                <p><span class="detail_midashi">在庫数：</span><?php print $Shouhin_Detail_DATA[1]; ?></p>
            </div>

            <p class="detail_item_02">
                <span class="detail_midashi">品名：</span><?php print wordwrap($Shouhin_Detail_DATA[2], 40, "<br />"); ?>
            </p>

            <p class="detail_item_03">
                <span class="detail_midashi">品番：</span><?php print $Shouhin_Detail_DATA[3]; ?>
            </p>

            <p class="detail_item_04">
                <span class="detail_midashi">JAN：</span>

                <span id="detail_var_code"><?php print $test_data[4]; ?></span>
                <span id="detail_data_code"><?php print $Shouhin_Detail_DATA[5]; ?></span>
                <span id="scan_val_box">
                    <input type="text" id="scan_val" name="scan_val">
                </span>

                <span id="result_val"></span>
            </p>

            <p>

            </p>

            <div class="detail_item_05_box">
                <p><span class="detail_midashi">数量：</span><?php print $Shouhin_Detail_DATA[6]; ?></p>
                <p><span class="detail_midashi">ケース：</span><?php print $Shouhin_Detail_DATA[7]; ?></p>
                <p><span class="detail_midashi">バラ：</span><?php print $test_data[8]; ?></p>
            </div>

            <p class="detail_item_06_count">
                <span class="detail_midashi">カウント：</span>

                <?php if (!empty($scan_b)) :  ?>
                    <?php $count_num = 1; ?>
                    <?php print $count_num; ?>
                <?php else : ?>
                    <?php $count_num = 0; ?>
                    <?php print $count_num; ?>
                <?php endif; ?>
            </p>

            <p class="detail_item_07">
                <span class="detail_midashi">備考：</span>
            </p>

            <p class="detail_item_08">
                <span class="detail_midashi">特記：</span><?php print $Shouhin_Detail_DATA[10]; ?>
            </p>

            <p class="detail_item_09">
                <span class="detail_midashi">
                    運送便：
                </span><?php print $Shouhin_Detail_DATA[11]; ?><br />

            </p>

            <p class="detail_item_10">
                <span class="detail_midashi">得意先：</span>
                <?php foreach ($arr_Tokuisaki_name as $arr_Tokusaki_VAL) : ?>
                    <span><?= $arr_Tokusaki_VAL ?></span><br />
                <?php endforeach; ?>
            </p>

        </div>
    </div> <!-- ===============  container_detail END =============== -->

    <!-- フッターメニュー -->
    <footer class="footer-menu_02">
        <ul>
            <li><a href="#">戻る</a></li>
            <li><a href="#">確定</a></li>
            <li><a href="#">全数完了</a></li>
        </ul>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // scan_val要素にフォーカスを設定する
            $("#scan_val").focus();

            // バーコード、　商品コード　取得
            var detail_data_code = $("#detail_data_code").text();
            var detail_var_code = $('#detail_var_code').text();

            console.log(detail_data_code);
            console.log(scan_val);

            $("#scan_val").change(function() {

                if ($("#scan_val").val() === detail_var_code || $("#scan_val").val() === detail_data_code) {
                    $("#result_val").text("OK:::" + $("#scan_val").val());
                } else {
                    $("#result_val").text("NG:::" + $("#scan_val").val());
                }

            });

        });
    </script>


</body>

</html>