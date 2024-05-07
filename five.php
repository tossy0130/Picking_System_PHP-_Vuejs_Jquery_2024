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

    $table_Flg = 0;
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    $sql = "SELECT table_name FROM user_tables WHERE table_name = 'HTPK'";
    // $sql = "SELECT table_name FROM user_tables WHERE table_name = 'SLTR'";
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);

    if ($row = oci_fetch_assoc($stmt)) {
        $HTPK_table_Flg = 1;
        //    echo "テーブルが存在します。";
    } else {
        $HTPK_table_Flg = 0;
        //    echo "テーブルが存在しません。";

        // ============================= DB 処理 =============================
        // === HTPK テーブル作成
        $createSql = "CREATE TABLE HTPK (
        処理ＳＥＱ                     NUMBER(10) NOT NULL,
        伝票番号                       NUMBER(9) NOT NULL,
        伝票行番号                     NUMBER(5) NOT NULL,
        伝票行枝番                     NUMBER(9) NOT NULL,
        入力担当                       NUMBER(4),
        商品Ｃ                         VARCHAR2(14),
        倉庫Ｃ                         VARCHAR2(2),
        運送Ｃ                         NUMBER(2),
        出荷元                         VARCHAR2(3),
        特記事項                       VARCHAR2(20),
        出荷予定数量                   NUMBER(10,2) DEFAULT 0,
        ピッキング数量                 NUMBER(10,2) DEFAULT 0,
        処理開始日時                   DATE,
        処理終了日時                   DATE,
        登録日                         DATE,
        登録端末                       VARCHAR2(15),
        処理Ｆ                         NUMBER(1) DEFAULT 0,
        商品名                         VARCHAR2(150)
    )";

        // テーブルを作成
        $createStmt = oci_parse($conn, $createSql);
        if (oci_execute($createStmt)) {
            // テーブル作成完了

        } else {
            // テーブル作成失敗
            $e = oci_error($createStmt);
            echo "テーブル作成エラー: " . htmlentities($e['message'], ENT_QUOTES);
        }
    }

    oci_free_statement($stmt);
    oci_close($conn);

    // 値取得
    if (isset($_GET['select_day'])) {

        // =============== スキャンからのGETパラメータ ===============
        if (isset($_GET['scan_b'])) {
            $scan_b = $_GET['scan_b'];
        }

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
        $shouhin_jan = $_GET['shouhin_jan'];

        // 倉庫名
        $souko_name = $_SESSION['soko_name'];

        //    print("セッション倉庫名:::" . $souko_name . "<br />");

        // === 40バイトで分ける
        // 商品名
        $Shouhin_name_part1 = mb_substr($Shouhin_name, 0, 20);
        // 品番
        $Shouhin_name_part2 = mb_substr($Shouhin_name, 20);

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
            $shouhin_jan,      // 商品コード 01 （JAN）
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

        $sql = " SELECT SL.伝票番号, SL.伝票行番号, SL.伝票行枝番, SL.商品Ｃ, SL.倉庫Ｃ,SL.出荷元,SL.数量
    FROM SLTR SL
    WHERE SL.商品Ｃ = :syouhin_Code and SL.倉庫Ｃ = :souko_Code
   ORDER BY SL.伝票ＳＥＱ DESC
    FETCH FIRST 1 ROW ONLY";

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

        oci_free_statement($stid);
        oci_close($conn);


        // ========================== HTPK テーブル重複処理
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);
        if (!$conn) {
            $e = oci_error();
        }

        // データ重複　確認
        $check_sql = "SELECT COUNT(*) AS num_duplicates FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code";

        $check_stid = oci_parse($conn, $check_sql);
        if (!$check_stid) {
            $e = oci_error($conn);
            // エラーハンドリングを行う
        }

        // パラメータをバインド
        oci_bind_by_name($check_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
        oci_bind_by_name($check_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
        oci_bind_by_name($check_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);

        $result_check = oci_execute($check_stid);
        if (!$result_check) {
            $e = oci_error($check_stid);
            // エラーハンドリングを行う
        }

        $row = oci_fetch_assoc($check_stid);
        // 重複件数
        $num_duplicates = $row['NUM_DUPLICATES'];
        // print($num_duplicates);

        // 重複がある場合は削除
        if ($num_duplicates > 0) {
            $delete_sql = "DELETE FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code";

            $delete_stid = oci_parse($conn, $delete_sql);
            if (!$delete_stid) {
                $e = oci_error($conn);
                // エラーハンドリングを行う
            }

            // パラメータをバインド
            oci_bind_by_name($delete_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
            oci_bind_by_name($delete_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
            oci_bind_by_name($delete_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);

            $result_delete = oci_execute($delete_stid);
            if (!$result_delete) {
                $e = oci_error($delete_stid);
                // エラーハンドリングを行う
            }

            oci_free_statement($delete_stid);
        }

        oci_free_statement($check_stid);

        // ========================== 処理ＳＥＱ プラス 1 処理
        $select_max = " SELECT MAX(処理ＳＥＱ) AS max_seq FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code AND 商品名 = :IN_Syouhin_Name";

        $select_seq_stid = oci_parse($conn, $select_max);
        if (!$select_seq_stid) {
            $e = oci_error($conn);
        }

        oci_bind_by_name($select_seq_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
        oci_bind_by_name($select_seq_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
        oci_bind_by_name($select_seq_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
        oci_bind_by_name($select_seq_stid, ':IN_Syouhin_Name', $Shouhin_name);


        $result_select_seq = oci_execute($select_seq_stid);
        if (!$result_select_seq) {
            $e = oci_error($select_seq_stid);
        }

        $row = oci_fetch_assoc($select_seq_stid);
        $max_seq = $row['MAX_SEQ'];

        $new_seq = $max_seq + 1;

        oci_free_statement($select_seq_stid);

        // ========================== 処理 F 「作業中処理」
        $sql = "INSERT INTO HTPK (処理ＳＥＱ, 伝票番号, 伝票行番号, 伝票行枝番,
                                            入力担当, 商品Ｃ, 倉庫Ｃ, 運送Ｃ, 出荷元,
                                            特記事項,出荷予定数量, ピッキング数量,
                                            処理開始日時,登録日,登録端末,処理Ｆ,商品名)
                            VALUES(:NEW_SEQ, :IN_Dennpyou_num, :IN_Dennpyou_Gyou_num, :IN_Dennpyou_Eda_num, 
                            1111, :IN_Syouhin_Code ,:IN_Souko_Code, :unsoucode,
                                    :shippingmoto, 0,:IN_Syuka_Yotei_num,:shouhinnum, :start_time,
                                    :selectday,:touroku_sikibetu,2, :Shouhin_name)";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
        }

        // 処理開始日時
        $start_time = date('Y-m-d');

        $start_time_day = date('Y-m-d');
        // 登録端末　代わり 
        $touroku_sikibetu = '1111_' . $start_time_day;

        // パラメータをバインド
        oci_bind_by_name($stid, ':NEW_SEQ', $new_seq);
        oci_bind_by_name($stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
        oci_bind_by_name($stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
        oci_bind_by_name($stid, ':IN_Dennpyou_Eda_num', $IN_Dennpyou_Eda_num);

        oci_bind_by_name($stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
        oci_bind_by_name($stid, ':IN_Souko_Code', $IN_Souko_Code);
        oci_bind_by_name($stid, ':unsoucode', $unsou_code);
        oci_bind_by_name($stid, ':shippingmoto', $shipping_moto);

        oci_bind_by_name($stid, ':IN_Syuka_Yotei_num', $IN_Syuka_Yotei_num);
        oci_bind_by_name($stid, ':shouhinnum', $Shouhin_num);
        oci_bind_by_name($stid, ':start_time', $start_time);
        oci_bind_by_name($stid, ':selectday', $select_day);
        oci_bind_by_name($stid, ':touroku_sikibetu', $touroku_sikibetu);
        oci_bind_by_name($stid, ':Shouhin_name', $Shouhin_name);

        $result_insert = oci_execute($stid);
        if (!$result_insert) {
            $e = oci_error($stid);
            echo "[HTPKテーブル] ::: insertエラー:" . $e["message"];
        }

        oci_free_statement($stid);
        oci_close($conn);
    } // ======================================= END isset($_GET['select_day']



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

                <span id="detail_var_code"><?php print $Shouhin_Detail_DATA[4]; ?></span>
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
            <?php $url = "./four.php?unsou_code=" . urlencode($unsou_code) . '&unsou_name=' . urlencode($unsou_name) . '&day=' . urlencode($select_day) . '&souko=' . urlencode($souko_code) . '&get_souko_name=' . urlencode($souko_name) . '&shouhin_code=' . urlencode($Shouhin_code) . '&shouhin_name=' . urlencode($Shouhin_name) . '&denpyou_num=' . $IN_Dennpyou_num . '&denpyou_Gyou_num=' . $IN_Dennpyou_Gyou_num . '&five_back=111' ?>
            <li><a href="<?php echo $url; ?>" id="five_back_btn">戻る</a></li>
            <li><a href="#">確定</a></li>
            <li><a href="#">全数完了</a></li>
        </ul>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            // ====================== ハンディー処理
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

            // ======================= 「戻る」ボタン

            var select_day = '<?php echo $select_day; ?>';
            var souko_code = '<?php echo $souko_code; ?>';
            var unsou_code = '<?php echo $unsou_code; ?>';
            var unsou_name = '<?php echo $unsou_name; ?>';
            //     var shipping_moto = <?php echo $shipping_moto; ?>;
            var shipping_moto_name = '<?php echo $shipping_moto_name; ?>';
            var Shouhin_code = '<?php echo $Shouhin_code; ?>';
            var Shouhin_name = '<?php echo $Shouhin_name; ?>';
            var Shouhin_num = '<?php echo $Shouhin_num; ?>';
            var Tokuisaki_name = '<?php echo $Tokuisaki_name; ?>';
            var Tana_num = '<?php echo $Tana_num; ?>';
            var case_num = '<?php echo $Shouhin_Detail_DATA[7]; ?>'; // ケース数
            var bara_num = '<?php echo $Shouhin_Detail_DATA[8]; ?>'; // バラ数

            var unsou_code = '<?php echo $unsou_code; ?>';
            var souko_name = '<?php echo $souko_name; ?>';

            /*
            $('#five_back_btn').on('click', function() {
                console.log("戻るボタン_click");
                window.location.href = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + select_day + '&souko=' + souko_code + '&get_souko_name=' + souko_name;
            });
            */

        });
    </script>


</body>

</html>