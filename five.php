<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";
require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

// 倉庫名
$souko_name = $_SESSION['soko_name'];

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

        // ケース数
        $Case_Num_Val = $case_num;
        // バラ数

        // ケース数
        /*
        if ($Shouhin_num != 0) {
            $Case_Num_Val = floor($Shouhin_num / $Konpou_num);
        }
        // バラ数
        if ($Konpou_num != 0) {
            $bara_num_tmp = $Shouhin_num % $Konpou_num;
        }
        */

        print("倉庫名:::" . $souko_name . "<br>");

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
        $check_sql = "SELECT COUNT(*) AS num_duplicates FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 
            伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code AND 商品名 = :Syouhin_name";

        $check_stid = oci_parse($conn, $check_sql);
        if (!$check_stid) {
            $e = oci_error($conn);
            // エラーハンドリングを行う
        }

        // パラメータをバインド
        oci_bind_by_name($check_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
        oci_bind_by_name($check_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
        oci_bind_by_name($check_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
        oci_bind_by_name($check_stid, ':Syouhin_name', $Shouhin_name);

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
            $delete_sql = "DELETE FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 伝票行番号 = :IN_Dennpyou_Gyou_num 
                AND 商品Ｃ = :IN_Syouhin_Code AND  商品名 = :Syouhin_name";

            $delete_stid = oci_parse($conn, $delete_sql);
            if (!$delete_stid) {
                $e = oci_error($conn);
                // エラーハンドリングを行う
            }

            // パラメータをバインド
            oci_bind_by_name($delete_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
            oci_bind_by_name($delete_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
            oci_bind_by_name($delete_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
            oci_bind_by_name($delete_stid, ':Syouhin_name', $Shouhin_name);

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


        //  ====================================================================================
        // ============================= ピッキングデータ 作成部分　=============================
        //  ====================================================================================

        // === 接続準備


        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名	
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SK.出荷数量) AS 数量	
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SJ.得意先名
      ,SH.ＪＡＮ
 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
      ,RZMF RZ
      ,HTPK PK
 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
   AND SL.伝票番号   = PK.伝票番号(+)
   AND SL.伝票行番号 = PK.伝票行番号(+)
   AND SL.伝票行枝番 = PK.伝票行枝番(+)
   AND SK.倉庫Ｃ = SO.倉庫Ｃ
   AND SL.出荷元 = SM.出荷元Ｃ(+)
   AND SK.運送Ｃ = US.運送Ｃ
   AND SL.商品Ｃ = SH.商品Ｃ
   AND SK.倉庫Ｃ = RZ.倉庫Ｃ
   AND SK.商品Ｃ = RZ.商品Ｃ
   AND SJ.出荷日 = :SELECT_DATE
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
   AND SH.ＪＡＮ = :SELECT_JAN
   AND SK.商品Ｃ = :SELECT_SHOUHIN_CODE
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 --  AND SH.品名 = 'アルミベンチ型宅配ボックス レシーボ     ＴＲＡ－６４（ＴＧＹ）'
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $souko_code);
        oci_bind_by_name($stid, ":SELECT_UNSOU", $unsou_code);
        oci_bind_by_name($stid, ":SELECT_JAN", $shouhin_jan);
        oci_bind_by_name($stid, ":SELECT_SHOUHIN_CODE", $shouhin_jan);

        oci_execute($stid);
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
                <span class="detail_midashi">品名：</span><?php print wordwrap($Shouhin_Detail_DATA[2]); ?>
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
                <p><span class="detail_midashi" id="detail_num_box">数量：</span><span id="suuryou_num"><?php print $Shouhin_Detail_DATA[6]; ?></span></p>
                <p><span class="detail_midashi">ケース：</span><?php print $Shouhin_Detail_DATA[7]; ?></p>
                <p><span class="detail_midashi">バラ：</span><?php print $Shouhin_Detail_DATA[8]; ?></p>
            </div>

            <p class="detail_item_06_count">
                <span class="detail_midashi">カウント：</span>

                <?php if (!empty($scan_b)) :  ?>
                    <?php $count_num = 1; ?>
                <?php else : ?>
                    <?php $count_num = 0; ?>
                <?php endif; ?>

                <input type="number" name="count_num" id="count_num" value="<?php print $count_num; ?>">
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

    <!-- モーダル 「確定 用」 -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p style="font-size: 1.2em;">数量が足りていません。</p>
            <div class="modal_div">
                <div>
                    <button id="send_kakutei">確定する</button>
                </div>

                <div>
                    <button id="cancel_kakutei">キャンセル</button>
                </div>
            </div>
        </div>
    </div>

    <!-- モーダル 「確定 用」 -->
    <div id="myModal_02" class="modal_02">
        <div class="modal-content_02">
            <span class="close_02">&times;</span>
            <p style="font-size: 1.2em;">カウントが 0 です。前の画面へ戻りますか？</p>
            <div class="modal_div_02">
                <div>
                    <button id="send_back">戻る</button>
                </div>

                <div>
                    <button id="cancel_back">キャンセル</button>
                </div>
            </div>
        </div>
    </div>

    <!-- フッターメニュー -->
    <footer class="footer-menu_02">
        <ul>
            <?php $url = "./four.php?unsou_code=" . urlencode($unsou_code) . '&unsou_name=' . urlencode($unsou_name) . '&day=' . urlencode($select_day) . '&souko=' . urlencode($souko_code) . '&get_souko_name=' . urlencode($souko_name) . '&shouhin_code=' . urlencode($Shouhin_code) . '&shouhin_name=' . urlencode($Shouhin_name) . '&denpyou_num=' . $IN_Dennpyou_num . '&denpyou_Gyou_num=' . $IN_Dennpyou_Gyou_num . '&five_back=111' ?>
            <li><a href="" id="five_back_btn">戻る</a></li>

            <li>


                <form action="./four.php" method="GET" name="kakutei_btn_post" id="kakutei_btn_post">

                    <!--
                <form method="GET" name="kakutei_btn_post" id="kakutei_btn_post">
            -->
                    <input type="hidden" name="select_day" value="<?php print $select_day; ?>">
                    <input type="hidden" name="souko_code" value="<?php print $souko_code; ?>">
                    <input type="hidden" name="unsou_code" value="<?php print $unsou_code; ?>">
                    <input type="hidden" name="unsou_name" value="<?php print $unsou_name; ?>">
                    <input type="hidden" name="souko_name" value="<?php print $_SESSION['soko_name']; ?>">
                    <input type="hidden" name="shouhin_jan" value="<?php print $Shouhin_Detail_DATA[4]; ?>">
                    <input type="hidden" name="shouhin_code" value="<?php print $Shouhin_Detail_DATA[5]; ?>">
                    <input type="hidden" name="shouhin_name" value="<?php print $Shouhin_name; ?>">

                    <!-- 伝票番号 -->
                    <input type="hidden" name="Dennpyou_num" value="<?php print $IN_Dennpyou_num; ?>">
                    <!-- 伝票行番号 -->
                    <input type="hidden" name="Dennpyou_Gyou_num" value="<?php print $IN_Dennpyou_Gyou_num; ?>">

                    <!-- カウントの値 -->
                    <input type="hidden" name="count_num_val" id="count_num_val" value="">

                    <input type="hidden" name="Kakutei_Btn_Flg" id="Kakutei_Btn_Flg" value="">

                    <button type="submit" name="kakutei_btn" id="kakutei_btn">確定</button>

                </form>

                <!-- 
                <a href="./four.php?test=1">確定</a>
                -->

            </li>

            <li><a href="#">全数完了</a></li>
        </ul>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            // ========= モーダル処理 表示　「確定」
            function showModal() {
                $('#myModal').css('display', 'block');
            }

            // ========= モーダル処理 表示　「確定」
            function hideModal() {
                $('#myModal').css('display', 'none');
            }

            // ========= モーダル処理 表示　「確定」
            function showModal_back() {
                $('#myModal_02').css('display', 'block');
            }

            // ========= モーダル処理 表示　「確定」
            function hideModal_back() {
                $('#myModal_02').css('display', 'none');
            }

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

            //====================== start ============================
            // ******** [戻る ボタン] **********
            $('#five_back_btn').on('click', function() {

                var suuryou_num = $('#suuryou_num').text();
                var count_num = $('#count_num').val();

                if (parseInt(count_num) === 0) {
                    event.preventDefault();
                    showModal_back();
                } else {
                    var back_url = "./four.php?" +
                        "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                        "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                        "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                        "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                        "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                        "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                        "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                        "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                        "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                        "&five_back=111";

                    window.location.href = back_url;
                }

            });

            // モーダル　「戻る」
            $('#send_back').on('click', function() {

                var back_url = "./four.php?" +
                    "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                    "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                    "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                    "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                    "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                    "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                    "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                    "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                    "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                    "&five_back=111";

                window.location.href = back_url;

            });

            $('#cancel_back').on('click', function() {
                hideModal_back();
            });


            // ******** [戻る ボタン] ********** 
            //====================== END ============================


            // ********* 「確定ボタン」処理 *********

            $('#kakutei_btn').on('click', function() {

                // event.preventDefault();

                var suuryou_num = $('#suuryou_num').text();
                var count_num = $('#count_num').val();

                console.log("数量:" + suuryou_num);
                console.log("カウント:" + count_num);

                // === 「数量」「カウント」比較　100 => 残 ,200 => 通常処理
                if (suuryou_num > count_num) {
                    console.log("残");
                    $('#Kakutei_Btn_Flg').val(100);
                    $('#count_num_val').val(count_num);

                    //   showModal();
                    //   return false;
                    //   $('#kakutei_btn').submit();
                    //    $('#kakutei_btn').submit();

                    $('#kakutei_btn').submit();

                } else {
                    console.log("NO-残");
                    $('#Kakutei_Btn_Flg').val(200);
                    $('#count_num_val').val(count_num);

                    $('#kakutei_btn').submit();
                }

            });

            // === モーダル「確定」
            $('#send_kakutei').on('click', function() {

                hideModal();

                var count_num = $('#count_num').val();
                $('#count_num_val').val(count_num);

                $('#Kakutei_Btn_Flg').val(100);
                $('#count_num_val').val(count_num);


                $("#kakutei_btn").click(function() {
                    $("#kakutei_btn_post").submit();
                });


                $('#kakutei_btn').submit();

                $("#kakutei_btn_post").submit(function() {
                    console.log("submit");
                });


                $('#kakutei_btn_post').submit();
            });


            // === モーダル「確定」キャンセル
            /*
            $('#cancel_kakutei').on('click', function() {
                hideModal();
            });
            */

            // === モーダル閉じる
            /*
            $('.close').on('click', function() {
                hideModal();
            });
            */


        });
    </script>


</body>

</html>