<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";
require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

$five_back = "";

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

    // 倉庫名　取得
    if (isset($_SESSION['soko_name'])) {
        $get_souko_name = $_SESSION['soko_name'];
        $_SESSION['soko_name'] = $get_souko_name;
    } else {
        $_SESSION['soko_name'] = $souko_name;
    }

    if (isset($_GET['day'])) {
        $select_day = $_GET['day'];
    }

    if (isset($_GET['unsou_name'])) {
        $get_unsou_name = $_GET['unsou_name'];
    }

    if (isset($_GET['unsou_code'])) {
        $select_unsou_code = $_GET['unsou_name'];
    }


    // =================== 「確定」five.php からの戻り ===========================
    // ========= five.php「確定ボタン」を押した時の処理 =========
    // =======================================================
    if (isset($_GET['kakutei_btn']) && !isset($_GET['back_one_condition'])) {

        // 「残」フラグ  
        $Kakutei_Btn_Flg = $_GET['Kakutei_Btn_Flg'];

        // ========= HTPK へ 「残」の識別をインサートする。=========

        // === 100 => 残
        if ($Kakutei_Btn_Flg == 100) {

            $select_day = $_GET['select_day'];
            $select_souko_code  = $_GET['souko_code'];
            $select_unsou_code  = $_GET['unsou_code'];
            $unsou_name = $_GET['unsou_name'];
            $souko_name = $_GET['souko_name'];
            $shouhin_jan = $_GET['shouhin_jan'];
            $shouhin_code = $_GET['shouhin_code'];
            $shouhin_name = $_GET['shouhin_name'];
            // === 伝票番号
            $Dennpyou_num = $_GET['Dennpyou_num'];
            // === 伝票行番号
            $Dennpyou_Gyou_num = $_GET['Dennpyou_Gyou_num'];
            // === カウントの値
            $count_num_val = $_GET['count_num_val'];
            print("count_num_val:::" . $count_num_val);

            // 倉庫名
            $get_souko_name = $souko_name;
            // 運送名
            $get_unsou_name = $unsou_name;

            // ********* 伝票 SEQ（値を 処理SEQへ変更 ） five.php から戻ってきた値（確定） *********
            $Syori_SEQ = $_GET['IN_Denpyou_SEQ'];
            print("処理SEQ:::" . $Syori_SEQ);

            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
            }

            //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
            $sql = "UPDATE HTPK SET 処理Ｆ = 8, ピッキング数量 = :Pikking_Num WHERE 処理ＳＥＱ = :Syori_SEQ AND 伝票番号 = :Denpyou_Num AND 商品Ｃ = :SHOHIN_CODE";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":Pikking_Num",  $count_num_val);
            oci_bind_by_name($stid, ":Syori_SEQ",  $Syori_SEQ); // 処理 SEQ
            oci_bind_by_name($stid, ":Denpyou_Num", $Dennpyou_num);
            oci_bind_by_name($stid, ":SHOHIN_CODE",  $shouhin_code);

            $result_update = oci_execute($stid);
            if (!$result_update) {
                $e = oci_error($stid);
                echo "[HTPKテーブル] ::: updatetエラー:" . $e["message"];
            }

            oci_free_statement($stid);

            // === 運送便 単数 , 備考・特記　の　場合 ===
            if (isset($_GET['one_now_sql_kakutei'])) {

                $sql = $_GET['one_now_sql_kakutei'];

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

                oci_execute($stid);
            } else {
                $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名	
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SK.出荷数量) AS 数量	
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SJ.得意先名
      ,SH.ＪＡＮ
      ,SK.特記事項
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
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
                oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

                oci_execute($stid);
            }

            $arr_Picking_DATA = array();
            while ($row = oci_fetch_assoc($stid)) {
                // カラム名を指定して値を取得
                $syuka_day = $row['出荷日'];
                $souko_code = $row['倉庫Ｃ'];
                $souko_name = $row['倉庫名'];
                $Unsou_code = $row['運送Ｃ'];
                $Unsou_name = $row['運送略称'];
                $shipping_moto = $row['出荷元'];
                $shipping_moto_name = $row['出荷元名'];
                $Shouhin_code = $row['商品Ｃ'];
                $Shouhin_name = $row['品名'];
                $Tana_num = $row['棚番'];
                $Konpou_num = $row['梱包入数'];
                $Shouhin_num = $row['数量'];
                $Picking_num = $row['ピッキング数量'];
                $Shori_Flg = $row['処理Ｆ'];
                $Tokuisaki_name = $row['得意先名'];
                $shouhin_JAN    = $row['ＪＡＮ'];
                $Toki_Zikou    = $row['特記事項'];


                if (isset($_GET['one_now_sql_kakutei'])) {

                    $sql_one_tokki = $_GET['one_now_sql_kakutei'];

                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN,               // JANコード
                        'Toki_Zikou' => $Toki_Zikou,                // 特記
                        'Denpyou_SEQ' => $Denpyou_SEQ,              // 伝票番号
                        'sql_one_tokki' => $sql_one_tokki
                    );
                } else {
                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN               // JANコード
                        // 'Toki_Zikou' => $Toki_Zikou
                    );
                }
            }

            oci_free_statement($stid);
            oci_close($conn);

            // ================================== 200 正常処理
        } else {
            // === 200 残じゃない

            $select_day = $_GET['select_day'];
            $select_souko_code  = $_GET['souko_code'];
            $select_unsou_code  = $_GET['unsou_code'];
            $unsou_name = $_GET['unsou_name'];
            $souko_name = $_GET['souko_name'];
            $shouhin_jan = $_GET['shouhin_jan'];
            $shouhin_code = $_GET['shouhin_code'];
            $shouhin_name = $_GET['shouhin_name'];
            // === 伝票番号
            $Dennpyou_num = $_GET['Dennpyou_num'];
            // === 伝票行番号
            $Dennpyou_Gyou_num = $_GET['Dennpyou_Gyou_num'];
            // === カウントの値
            $count_num_val = $_GET['count_num_val'];
            print("count_num_val:::" . $count_num_val);
            // 倉庫名
            $get_souko_name = $souko_name;
            // 運送名
            $get_unsou_name = $unsou_name;

            // ********* 伝票 SEQ（処理 SEQ の値に変更） five.php から戻ってきた値（確定） *********
            $Syori_SEQ = $_GET['IN_Denpyou_SEQ'];
            print("処理SEQ:::" . $Syori_SEQ);


            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
            }

            //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
            $sql = "UPDATE HTPK SET 処理Ｆ = 9, ピッキング数量 = :Pikking_Num WHERE 処理ＳＥＱ = :Syori_SEQ AND 伝票番号 = :Denpyou_Num AND 商品Ｃ = :SHOHIN_CODE";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":Pikking_Num",  $count_num_val);
            oci_bind_by_name($stid, ":Syori_SEQ",  $Syori_SEQ); // 処理 SEQ
            oci_bind_by_name($stid, ":Denpyou_Num", $Dennpyou_num);
            oci_bind_by_name($stid, ":SHOHIN_CODE",  $shouhin_code);

            $result_update = oci_execute($stid);
            if (!$result_update) {
                $e = oci_error($stid);
                echo "[HTPKテーブル] ::: updatetエラー:" . $e["message"];
            }

            oci_free_statement($stid);


            // === 運送便 単数 , 備考・特記　の　場合 ===
            if (isset($_GET['one_now_sql_kakutei'])) {

                $sql = $_GET['one_now_sql_kakutei'];

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

                oci_execute($stid);
            } else {
                $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名	
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SK.出荷数量) AS 数量	
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SJ.得意先名
      ,SH.ＪＡＮ
      ,SK.特記事項
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
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";


                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
                oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

                oci_execute($stid);
            }

            $arr_Picking_DATA = array();
            while ($row = oci_fetch_assoc($stid)) {
                // カラム名を指定して値を取得
                $syuka_day = $row['出荷日'];
                $souko_code = $row['倉庫Ｃ'];
                $souko_name = $row['倉庫名'];
                $Unsou_code = $row['運送Ｃ'];
                $Unsou_name = $row['運送略称'];
                $shipping_moto = $row['出荷元'];
                $shipping_moto_name = $row['出荷元名'];
                $Shouhin_code = $row['商品Ｃ'];
                $Shouhin_name = $row['品名'];
                $Tana_num = $row['棚番'];
                $Konpou_num = $row['梱包入数'];
                $Shouhin_num = $row['数量'];
                $Picking_num = $row['ピッキング数量'];
                $Shori_Flg = $row['処理Ｆ'];
                $Tokuisaki_name = $row['得意先名'];
                $shouhin_JAN    = $row['ＪＡＮ'];
                $Toki_Zikou    = $row['特記事項'];

                // === 運送便 単数 , 備考・特記　の　場合 ===
                if (isset($_GET['one_now_sql_kakutei'])) {

                    $sql_one_tokki = $_GET['one_now_sql_kakutei'];

                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN,               // JANコード
                        'Toki_Zikou' => $Toki_Zikou,                // 特記
                        'Denpyou_SEQ' => $Denpyou_SEQ,              // 伝票番号
                        'sql_one_tokki' => $sql_one_tokki
                    );
                } else {
                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN,               // JANコード
                        'Toki_Zikou' => $Toki_Zikou
                    );
                }
            }

            oci_free_statement($stid);
            oci_close($conn);
        }


        // ==============================================================
        // ==========================   戻るボタン =======================   
        // ============================================================== 
    } else if (isset($_GET['five_back'])) {

        $shouhin_code = $_GET['shouhin_code'];
        $shouhin_name = $_GET['shouhin_name'];

        print("戻ってきた");

        // === five.php から、 運送コード 単体 , 備考・特記
        if (isset($_GET['back_one_condition'])) {
            $back_one_condition = $_GET['back_one_condition'];
            $one_condition = $_GET['back_one_condition'];
            $one_condition = " " . $one_condition;

            // === five.php から、複数選択
        } else if (isset($_GET['back_now_sql_multiple'])) {

            $back_now_sql_multiple = $_GET['back_now_sql_multiple'];
            $back_multiple_condition = $_GET['back_now_sql_multiple'];
            $back_multiple_condition = " " . $back_multiple_condition;
        } else {
            $back_now_sql_multiple = "";
        }

        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
        $sql = "UPDATE HTPK SET 処理Ｆ = NULL WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SHOHIN_CODE",  $shouhin_code);
        oci_bind_by_name($stid, ":SHOUHIN_NAME", $shouhin_name);

        $result_update = oci_execute($stid);
        if (!$result_update) {
            $e = oci_error($stid);
            echo "[HTPKテーブル] ::: updatetエラー:" . $e["message"];
        }

        oci_free_statement($stid);

        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];


        // ============================= DB 処理 =============================
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
      ,SK.特記事項
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";

        if (isset($one_condition) && $one_condition == "" && isset($back_multiple_condition) && $back_multiple_condition == "") {
            $sql .= " AND SK.運送Ｃ = :SELECT_UNSOU";
        } else if (isset($one_condition) && $one_condition != "") {
            $sql .= $one_condition;
        } else if (isset($back_multiple_condition) && $back_multiple_condition != "") {
            print("戻る 複数");
            $sql .= $back_multiple_condition;
        }


        $sql .= " AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

        if (isset($one_condition) && $one_condition == "" && isset($back_multiple_condition) && $back_multiple_condition == "") {
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
        } else if (isset($one_condition) && $one_condition != "" && isset($back_multiple_condition) && $back_multiple_condition == "") {
            $sql_one_tokki = $sql;
        } else if (isset($one_condition) && $one_condition == "" && isset($back_multiple_condition) && $back_multiple_condition != "") {
            $sql_multiple_tokki = $sql;
        }

        oci_execute($stid);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];

            // === 運送便 単数 , 備考・特記　の　場合 ===
            if (isset($_GET['back_one_condition'])) {

                $sql_one_tokki = $_GET['back_one_condition'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'souko_code' => $souko_code,                // SK.倉庫Ｃ
                    'souko_name' => $souko_name,                // SO.倉庫名
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // SL.出荷元
                    'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // SH.品名
                    'Tana_num' => $Tana_num,                    // RZ.棚番
                    'Konpou_num' => $Konpou_num,                // SH.梱包入数
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                    'shouhin_JAN' => $shouhin_JAN,               // JANコード
                    'Toki_Zikou' => $Toki_Zikou,
                    'sql_one_tokki' => $sql_one_tokki
                );
            } else if (isset($_GET['back_now_sql_multiple'])) {

                $sql_multiple_tokki = $_GET['back_now_sql_multiple'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'souko_code' => $souko_code,                // SK.倉庫Ｃ
                    'souko_name' => $souko_name,                // SO.倉庫名
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // SL.出荷元
                    'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // SH.品名
                    'Tana_num' => $Tana_num,                    // RZ.棚番
                    'Konpou_num' => $Konpou_num,                // SH.梱包入数
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                    'shouhin_JAN' => $shouhin_JAN,               // JANコード
                    'Toki_Zikou' => $Toki_Zikou,                // 特記
                    'sql_multiple_tokki' => $sql_multiple_tokki // 複数選択 SQL
                );

                // five_back=333
            } else {
                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'souko_code' => $souko_code,                // SK.倉庫Ｃ
                    'souko_name' => $souko_name,                // SO.倉庫名
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // SL.出荷元
                    'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // SH.品名
                    'Tana_num' => $Tana_num,                    // RZ.棚番
                    'Konpou_num' => $Konpou_num,                // SH.梱包入数
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                    'shouhin_JAN' => $shouhin_JAN,               // JANコード
                    'Toki_Zikou' => $Toki_Zikou
                );
            }
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }


        // ===================== END  isset($_GET['five_back']


        // =====================================================================================================
        // =========================================== 「全数完了」場合  ===========================================
        // =====================================================================================================
    } else if (isset($_GET['all_completed_button']) && !isset($_GET['back_one_condition'])) {

        print("全数");

        $select_unsou_code = $_GET['Unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $Shouhin_code = $_GET['shouhin_code'];
        $Shouhin_name = $_GET['shouhin_name'];
        $Shouhin_num = $_GET['shouhin_num'];
        $shipping_moto_name = $_GET['shipping_moto_name'];
        $get_souko_name = $_GET['get_souko_name'];

        $ZEN_SUU_VAL = $_GET['ZEN_SUU_VAL']; // === ピッキング数量

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // HTPKテーブル内の「処理Ｆ」を9に更新
        $updateFSQL = "UPDATE HTPK SET  ピッキング数量 = :PICKINGNUM, 処理Ｆ = 9 WHERE 商品Ｃ = :SHOUHINCODE AND 商品名 = :SHOUHINNAME";

        $stid_updateFSQL = oci_parse($conn, $updateFSQL);
        if (!$stid_updateFSQL) {
            $e = oci_error($conn);
            echo "SQLの準備でエラーが発生しました:" . $e["message"];
            exit;
        }

        // パラメータをバインド
        oci_bind_by_name($stid_updateFSQL, ':PICKINGNUM', $ZEN_SUU_VAL);
        oci_bind_by_name($stid_updateFSQL, ':SHOUHINCODE', $Shouhin_code);
        oci_bind_by_name($stid_updateFSQL, ':SHOUHINNAME', $Shouhin_name);

        oci_execute($stid_updateFSQL);

        oci_free_statement($stid_updateFSQL);

        // === 全数完了　SQL 分岐
        if ($_GET['one_now_sql_zensuu']) {

            print("kokoko");
            $sql = $_GET['one_now_sql_zensuu'];

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE",  $select_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

            oci_execute($stid);
        } else {

            $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名	
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SK.出荷数量) AS 数量	
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SJ.得意先名
      ,SH.ＪＡＮ
      ,SK.特記事項
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
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);


            oci_execute($stid);
        }

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];

            // === 運送便 単数 , 備考・特記　の　場合 ===
            if (isset($_GET['one_now_sql_zensuu'])) {

                $sql_one_tokki = $_GET['one_now_sql_zensuu'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'souko_code' => $souko_code,                // SK.倉庫Ｃ
                    'souko_name' => $souko_name,                // SO.倉庫名
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // SL.出荷元
                    'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // SH.品名
                    'Tana_num' => $Tana_num,                    // RZ.棚番
                    'Konpou_num' => $Konpou_num,                // SH.梱包入数
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                    'shouhin_JAN' => $shouhin_JAN,               // JANコード
                    'Toki_Zikou' => $Toki_Zikou,
                    'sql_one_tokki' => $sql_one_tokki
                );
            } else {
                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'souko_code' => $souko_code,                // SK.倉庫Ｃ
                    'souko_name' => $souko_name,                // SO.倉庫名
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // SL.出荷元
                    'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // SH.品名
                    'Tana_num' => $Tana_num,                    // RZ.棚番
                    'Konpou_num' => $Konpou_num,                // SH.梱包入数
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                    'shouhin_JAN' => $shouhin_JAN,               // JANコード
                    'Toki_Zikou' => $Toki_Zikou
                );
            }
        }

        oci_free_statement($stid);
        oci_close($conn);


        // =====================================================================================================
        // =========================================== 複数選択の場合  ===========================================
        // =====================================================================================================
    } else if (isset($_GET['fukusuu_select']) && !isset($_GET['back_one_condition'])) {

        print("複数" . "<br>");

        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];

        // 複数　運送コード
        $fukusuu_unsouo_num = $_GET['fukusuu_unsouo_num'];
        // 複数  運送コード + 特記・備考
        $fukusuu_select_val = $_GET['fukusuu_select_val'];

        if (empty($fukusuu_select_val)) {
            //      print("特記あり 空:::" . $fukusuu_select_val);
        }

        if (empty($fukusuu_unsouo_num)) {
            //   print("複数 空:::" . $fukusuu_select_val);
        }

        // 複数　運送コード 分割
        $arr_fukusuu_unsouo_num = explode(',', $fukusuu_unsouo_num);
        // 複数  運送コード + 特記・備考 分割
        $arr_fukusuu_select_val = explode(',', $fukusuu_select_val);

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // ========= 運送コード + 特記・備考　処理
        // コロン & 空白要素　削除
        $arr_fukusuu_select_val[0] = str_replace('：', '', $arr_fukusuu_select_val[0]);
        $arr_fukusuu_select_val = array_filter($arr_fukusuu_select_val);

        // 運送コードの 先頭の - 削除
        foreach ($arr_fukusuu_select_val as &$arr_fukusuu_VAL) {
            $arr_fukusuu_VAL = preg_replace('/^-/', '', $arr_fukusuu_VAL);
        }
        unset($arr_fukusuu_VAL);

        // ========= 運送コード　複数
        $arr_fukusuu_unsouo_num = explode(',', $fukusuu_unsouo_num);
        $arr_fukusuu_unsouo_num[0] = str_replace('：', '', $arr_fukusuu_unsouo_num[0]);
        $arr_fukusuu_unsouo_num = array_filter($arr_fukusuu_unsouo_num);

        //   print_r($arr_fukusuu_unsouo_num);

        $arr_Fku_Val = [];

        /*
        if (empty($arr_Fku_Val)) {
            print("arr_Fku_Val:::空");
        }
        */

        // 運送コード 運送コード + 特記・備考 の２次元配列作成
        foreach ($arr_fukusuu_select_val as $arr_val) {
            $arr_Fku_Val[] = explode(":", $arr_val);
        }

        // ********* 可変部分の条件を生成 *********
        if (!empty($arr_Fku_Val)) {

            $conditions = [];
            foreach ($arr_Fku_Val as $arr_SQL) {
                // 可変部分の条件を生成
                $conditionSet = [];
                $conditionSet[0] = "(SK.運送Ｃ = '{$arr_SQL[1]}')";

                if ($arr_SQL[2] !== '-') {
                    $conditionSet[1] = "(SL.出荷元 = '{$arr_SQL[2]}')";
                } else {
                    $conditionSet[1] = "(SL.出荷元 IS NULL)";
                }

                if ($arr_SQL[3] !== '---') {
                    $conditionSet[2] = "(SK.特記事項 = '{$arr_SQL[3]}')";
                } else {
                    $conditionSet[2] = "(SK.特記事項 IS NULL)";
                }

                // $conditions[] = implode(' AND ', $conditionSet);

                // 条件を結合して配列に追加
                if (empty($fukusuu_unsouo_num)) {
                    // OK 
                    $conditions[] = implode(' AND ', $conditionSet);
                } else {

                    //   $conditions[] = implode(' AND ', $conditionSet);
                    $conditions[] = '(' . implode(' AND ', $conditionSet) . ')';
                }
            }
        }

        // === 運送コード（備考・特記）なし抽出　重複削除
        if (!empty($arr_Fku_Val)) {
            $idx = 0;
            for ($i = 0; $i < count($arr_fukusuu_unsouo_num); $i++) {

                if ($arr_fukusuu_unsouo_num[$idx] == $arr_Fku_Val[$idx][1]) {
                    unset($arr_fukusuu_unsouo_num[$idx]);
                    break;
                }

                $idx = $idx + 1;
            }
        }

        if (empty($arr_fukusuu_unsouo_num)) {
            print("複数は空");
        }


        $conditions_UNSOU = [];
        // === 運送コードだけのものがあった場合
        if (!empty($arr_fukusuu_unsouo_num)) {

            $idx = 0;
            foreach ($arr_fukusuu_unsouo_num as $F_Unsou_VAL) {

                // print($F_Unsou_VAL . "<br>");

                $conditionSet_Unsou = [];
                $conditionSet_Unsou[0] = "(SK.運送Ｃ = '{$F_Unsou_VAL}')";
                //      $idx = $idx + 1;

                if (empty($arr_Fku_Val)) {
                    //
                    $conditions_UNSOU[] = '(' . implode(' OR ', $conditionSet_Unsou) . ')';
                } else {
                    // $conditions_UNSOU[] = '(' . implode(' OR ', $conditionSet_Unsou) . ')';
                    $conditions_UNSOU[] = implode(' OR ', $conditionSet_Unsou);
                }
            }
        }


        $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名	
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SK.出荷数量) AS 数量	
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SJ.得意先名
      ,SH.ＪＡＮ
      ,SK.特記事項
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";

        /*
   --------------------------------- ＊＊＊ここから下が可変する＊＊＊
   AND (((SK.運送Ｃ = '60') AND (SL.出荷元 IS NULL) AND (SK.特記事項 = '関東')) OR 
                            ((SK.運送Ｃ = '60') AND (SL.出荷元 IS NULL) AND (SK.特記事項 IS NULL)) OR
                            (SK.運送Ｃ = '59')
                           )    
  ---- ＊＊＊ここまで＊＊＊
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";
 */

        // 可変部分の条件を追加 、運送コード + 備考・特記
        if (!empty($conditions) && !empty($conditions_UNSOU)) {
            //    $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            $sql .= ' AND (' . implode(' OR ', $conditions);
        } else if (!empty($conditions) && empty($conditions_UNSOU)) {
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        if (!empty($conditions_UNSOU) && !empty($conditions)) {
            //  $sql .= ' OR (' . implode(' OR ', $conditions_UNSOU) . ')';
            $sql .= ' OR ' . implode(' OR ', $conditions_UNSOU) . ')';
        } else if (!empty($conditions_UNSOU) && empty($conditions)) {
            $sql .= ' AND (' . implode(' OR ', $conditions_UNSOU) . ')';
        }

        // 可変部分の条件を追加 、運送コード

        // GROUP BY句とORDER BY句を追加
        $sql .= " GROUP BY SK.出荷日, SK.倉庫Ｃ, SO.倉庫名, SK.運送Ｃ, US.運送略称, SL.出荷元, SM.出荷元名, SK.商品Ｃ, SH.品名, PK.処理Ｆ, RZ.棚番, SH.梱包入数, SJ.得意先名, SH.ＪＡＮ, SK.特記事項
              ORDER BY SK.倉庫Ｃ, SK.運送Ｃ, SM.出荷元名, SK.商品Ｃ, SL.出荷元, SK.特記事項";

        // =================
        // === 複数SQL　取得
        // =================

        $Multiple_Sql = $sql;
        //  print("Multiple_Sql:::" . $Multiple_Sql);

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

        oci_execute($stid);



        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'Toki_Zikou' => $Toki_Zikou,
                'Multiple_Sql' => $Multiple_Sql
            );
        }

        oci_free_statement($stid);
        oci_close($conn);

        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }
    }


    // ==========================================================
    // ================= 通常処理　（運送単数） =================
    // ==========================================================

    // ========= 備考・特記　なし
    if (
        isset($_GET['day']) && !isset($_GET['selectedToki_Code']) && !isset($_GET['fukusuu_select_val'])
        && !isset($_GET['back_one_condition']) && !isset($_GET['show_all'])
        && !isset($_GET['one_now_sql_zensuu']) && !isset($_GET['back_now_sql_multiple'])
    ) {

        if (isset($_GET['day'])) {
            $select_day = $_GET['day'];
        }

        if (isset($_GET['souko'])) {
            $select_souko_code = $_GET['souko'];
        }

        if (isset($_GET['get_souko_name'])) {
            $get_souko_name = $_GET['get_souko_name'];
        }

        if (isset($_GET['unsou_code'])) {
            $select_unsou_code = $_GET['unsou_code'];
        }

        // === five.php から戻ってきた用
        if (isset($_GET['Unsou_code'])) {
            $select_unsou_code = $_GET['Unsou_code'];
        }

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        /*
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SL.数量) AS 数量     
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SH.ＪＡＮ
      ,SK.特記事項
 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
      ,RZMF RZ
      ,HTPK PK
 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
   AND SL.伝票番号   = PK.伝票番号(+)
   AND SL.伝票行番号 = PK.伝票行番号(+)
   AND SL.伝票行枝番 = PK.伝票行枝番(+)
   AND SL.倉庫Ｃ = SO.倉庫Ｃ
   AND SL.出荷元 = SM.出荷元Ｃ(+)
   AND SJ.運送Ｃ = US.運送Ｃ
   AND SL.商品Ｃ = SH.商品Ｃ
   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
   AND SL.商品Ｃ = RZ.商品Ｃ
   AND SJ.出荷日 = :SELECT_DATE  
   AND SL.倉庫Ｃ = :SELECT_SOUKO
   AND SJ.運送Ｃ = :SELECT_UNSOU
   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項";
*/

        // SQL 修正 24_0522 最新
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SL.数量) AS 数量    
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SH.ＪＡＮ
      ,SK.特記事項
 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
      ,RZMF RZ
      ,HTPK PK
 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
   AND SK.伝票行番号 = SL.伝票行番号
   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
   AND SL.伝票番号   = PK.伝票番号(+)
   AND SL.伝票行番号 = PK.伝票行番号(+)
   AND SL.伝票行枝番 = PK.伝票行枝番(+)
   AND SL.倉庫Ｃ = SO.倉庫Ｃ
   AND SL.出荷元 = SM.出荷元Ｃ(+)
   AND SJ.運送Ｃ = US.運送Ｃ
   AND SL.商品Ｃ = SH.商品Ｃ
   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
   AND SL.商品Ｃ = RZ.商品Ｃ
   AND SJ.出荷日 = :SELECT_DATE  
   AND SL.倉庫Ｃ = :SELECT_SOUKO
   AND SJ.運送Ｃ = :SELECT_UNSOU
   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項";


        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);


        oci_execute($stid);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            //     $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];


            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                //    'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,              // JANコード
                'Toki_Zikou' => $Toki_Zikou                 // 特記事項
            );
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // ================================================================================
        // ====================  単数 運送コード 特記・備考あり  ============================
        // ================================================================================
    } else if (
        !isset($_GET['fukusuu_select_val']) && isset($_GET['selectedToki_Code'])
        && !isset($_GET['back_now_sql_multiple'])
    ) {

        print("単数 運送コード 特記・備考あり");
        // ============= 運送便（単数） & 備考・特記あり ================
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];

        // 運送便　& 備考, 特記事項　文字列   :コロン区切り
        $selectedToki_Code = $_GET['selectedToki_Code'];

        // index , 0 => 運送名 , 1 => 運送コード , 2 => 出荷元 , 3 => 特記事項
        $arr_SQL = explode(":", $selectedToki_Code);

        // 可変部分の条件を生成
        $conditions = [];

        $conditionSet[0] = "SK.運送Ｃ = '{$arr_SQL[1]}'";

        if ($arr_SQL[2] !== '-') {
            $conditionSet[1] = "SL.出荷元 = '{$arr_SQL[2]}'";
        } else {
            $conditionSet[1] = "SL.出荷元 IS NULL";
        }

        if ($arr_SQL[3] !== '---') {
            $conditionSet[2] = "SK.特記事項 = '{$arr_SQL[3]}'";
        } else {
            $conditionSet[2] = "SK.特記事項 IS NULL";
        }

        // $conditions[] = '(' . implode(' AND ', $conditionSet) . ')';

        $conditions[] = '(' . $conditionSet[0] . ')' . ' AND ' . '(' . $conditionSet[1] . ')' . ' AND ' . '(' . $conditionSet[2] . ')';

        // デフォルトの並べ替え

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // SQL 修正 24_0522 最新
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
      ,RZ.棚番
      ,SH.梱包入数
      ,SUM(SL.数量) AS 数量    
      ,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ
      ,SH.ＪＡＮ
      ,SK.特記事項
 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
      ,RZMF RZ
      ,HTPK PK
 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
   AND SK.伝票行番号 = SL.伝票行番号
   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
   AND SL.伝票番号   = PK.伝票番号(+)
   AND SL.伝票行番号 = PK.伝票行番号(+)
   AND SL.伝票行枝番 = PK.伝票行枝番(+)
   AND SL.倉庫Ｃ = SO.倉庫Ｃ
   AND SL.出荷元 = SM.出荷元Ｃ(+)
   AND SJ.運送Ｃ = US.運送Ｃ
   AND SL.商品Ｃ = SH.商品Ｃ
   AND SL.倉庫Ｃ = RZ.倉庫Ｃ
   AND SL.商品Ｃ = RZ.商品Ｃ
   AND SJ.出荷日 = :SELECT_DATE     
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";

        // 可変部分の条件を追加
        if (!empty($conditions)) {
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        // GROUP BY句とORDER BY句を追加

        $sql .= " AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項";

        /*
        $sql .= " AND PK.処理Ｆ <> 9
 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項";
*/

        // === テスト
        $sql_one_tokki = $sql;
        // print($sql_one_tokki);

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }


        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);

        // print($sql);

        oci_execute($stid);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            //       $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                //       'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'Toki_Zikou' => $Toki_Zikou,
                'sql_one_tokki' => $sql_one_tokki
            );
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }
    }


    // ==================================================================================================
    // ========================================== 全数表示　処理　==========================================    
    // ==================================================================================================
    // 全表示ボタンが押された場合の処理

    if (isset($_GET["show_all"])) {

        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $select_unsou_code = $_GET['unsou_code'];


        // 運送, 備考・特記
        if (isset($_GET['one_condition'])) {
            $one_condition = " " . $_GET['one_condition'];
        } else if (isset($_GET['back_one_condition'])) {
            $back_one_condition = " " . $_GET['back_one_condition'];
        } else {
            $one_condition = "";
        }

        // ============================= DB 処理 =============================
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
      ,SK.特記事項
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";


        if ($one_condition == "") {
            $sql .= " AND SK.運送Ｃ = :SELECT_UNSOU";
        } else {
            $sql .= $one_condition;
        }


        $sql .= " AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元 ,SK.特記事項";


        /*
        $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ
        ,SH.品名,SUM(SK.出荷数量) AS 数量,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
                FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                AND SK.倉庫Ｃ = SO.倉庫Ｃ
                AND SL.出荷元 = SM.出荷元Ｃ(+)
                AND SK.運送Ｃ = US.運送Ｃ
                AND SL.商品Ｃ = SH.商品Ｃ
                AND SJ.出荷日 = :SELECT_DATE
                AND SK.倉庫Ｃ = :SELECT_SOUKO
                AND SK.運送Ｃ = :SELECT_UNSOU
                GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,
                    SK.商品Ｃ,SH.品名 ,SJ.得意先名,SH.ＪＡＮ,SK.特記事項	
                ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";
*/

        $stid = oci_parse(
            $conn,
            $sql
        );
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

        if ($one_condition == "") {
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
        } else {
        }

        oci_execute($stid);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $Toki_Zikou    = $row['特記事項'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,              // JANコード
                'Toki_Zikou' => $Toki_Zikou
            );
        }


        /*
        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Shouhin_num = $row['数量'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,
                'souko_code' => $souko_code,
                'souko_name' => $souko_name,
                'Unsou_code' => $Unsou_code,
                'Unsou_name' => $Unsou_name,
                'shipping_moto' => $shipping_moto,
                'shipping_moto_name' => $shipping_moto_name,
                'Shouhin_code' => $Shouhin_code,
                'Shouhin_name' => $Shouhin_name,
                'Shouhin_num' => $Shouhin_num
            );
        }
        */

        oci_free_statement($stid);
        oci_close($conn);

        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            // print($_SESSION['soko_name'] . ":::01");
            // print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $souko_name;
            //  print($souko_name . ":::02");
            // print($_SESSION['soko_name'] . ":::02");
        }
    }


    // ==================================================================================================
    // ========================================== ソート用処理　==========================================    
    // ==================================================================================================
    // ソート用キー取得
    $sortKey = "";
    if (isset($_GET['sort_key'])) {
        $sortKey = $_GET['sort_key'];

        // ============================= DB 処理（テーブル存在チェック）=============================
        // === 接続準備
        switch ($sortKey) {
                // ロケ順に並べ替え
            case 'location_note':

                // 運送, 備考・特記
                if (isset($_GET['one_condition'])) {
                    $one_condition = " " . $_GET['one_condition'];
                } else {
                    $one_condition = "";
                }

                // ============================= DB 処理 =============================
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
      ,SK.特記事項
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";

                if ($one_condition == "") {
                    $sql .= " AND SK.運送Ｃ = :SELECT_UNSOU";
                } else {
                    $sql .= $one_condition;
                }

                $sql .= " AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

                if ($one_condition == "") {
                    oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
                } else {
                }

                oci_execute($stid);

                $arr_Picking_DATA = array();
                while ($row = oci_fetch_assoc($stid)) {
                    // カラム名を指定して値を取得
                    $syuka_day = $row['出荷日'];
                    $souko_code = $row['倉庫Ｃ'];
                    $souko_name = $row['倉庫名'];
                    $Unsou_code = $row['運送Ｃ'];
                    $Unsou_name = $row['運送略称'];
                    $shipping_moto = $row['出荷元'];
                    $shipping_moto_name = $row['出荷元名'];
                    $Shouhin_code = $row['商品Ｃ'];
                    $Shouhin_name = $row['品名'];
                    $Tana_num = $row['棚番'];
                    $Konpou_num = $row['梱包入数'];
                    $Shouhin_num = $row['数量'];
                    $Picking_num = $row['ピッキング数量'];
                    $Shori_Flg = $row['処理Ｆ'];
                    $Tokuisaki_name = $row['得意先名'];
                    $shouhin_JAN    = $row['ＪＡＮ'];
                    $Toki_Zikou    = $row['特記事項'];

                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN,               // JANコード
                        'Toki_Zikou' => $Toki_Zikou
                    );
                }

                oci_free_statement($stid);
                oci_close($conn);

                break;

                // 数量順に並べ変え
            case 'num_note':

                // 運送, 備考・特記
                if (isset($_GET['one_condition'])) {
                    $one_condition = " " . $_GET['one_condition'];
                } else {
                    $one_condition = "";
                }

                // ============================= DB 処理 =============================
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
      ,SK.特記事項
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";

                if ($one_condition == "") {
                    $sql .= " AND SK.運送Ｃ = :SELECT_UNSOU";
                } else {
                    $sql .= $one_condition;
                }

                $sql .= " AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY 数量 desc, SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
                oci_bind_by_name(
                    $stid,
                    ":SELECT_SOUKO",
                    $select_souko_code
                );

                // === bind 分岐部分
                if ($one_condition == "") {
                    oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
                } else {
                }

                oci_execute($stid);

                $arr_Picking_DATA = array();
                while ($row = oci_fetch_assoc($stid)) {
                    // カラム名を指定して値を取得
                    $syuka_day = $row['出荷日'];
                    $souko_code = $row['倉庫Ｃ'];
                    $souko_name = $row['倉庫名'];
                    $Unsou_code = $row['運送Ｃ'];
                    $Unsou_name = $row['運送略称'];
                    $shipping_moto = $row['出荷元'];
                    $shipping_moto_name = $row['出荷元名'];
                    $Shouhin_code = $row['商品Ｃ'];
                    $Shouhin_name = $row['品名'];
                    $Tana_num = $row['棚番'];
                    $Konpou_num = $row['梱包入数'];
                    $Shouhin_num = $row['数量'];
                    $Picking_num = $row['ピッキング数量'];
                    $Shori_Flg = $row['処理Ｆ'];
                    $Tokuisaki_name = $row['得意先名'];
                    $shouhin_JAN    = $row['ＪＡＮ'];
                    $Toki_Zikou    = $row['特記事項'];


                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN,               // JANコード
                        'Toki_Zikou' => $Toki_Zikou
                    );

                    //   var_dump($arr_Picking_DATA);
                }

                oci_free_statement($stid);
                oci_close($conn);

                break;
            case 'tokki_note':
                $sql = " ORDER BY SM.出荷元名";
                break;
            case 'bikou_note':
                $sql = " ORDER BY SK.商品Ｃ";
                break;
            default:
                // ソートデフォルトの並べ替え

                // 運送, 備考・特記
                if (isset($_GET['one_condition'])) {
                    $one_condition = " " . $_GET['one_condition'];
                } else {
                    $one_condition = "";
                }


                // ============================= DB 処理 =============================
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO";

                if ($one_condition == "") {
                    $sql .= " AND SK.運送Ｃ = :SELECT_UNSOU";
                } else {
                    $sql .= $one_condition;
                }

                " AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);

                // === bind 分岐部分
                if ($one_condition == "") {
                    oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
                } else {
                }


                oci_execute($stid);

                $arr_Picking_DATA = array();
                while ($row = oci_fetch_assoc($stid)) {
                    // カラム名を指定して値を取得
                    $syuka_day = $row['出荷日'];
                    $souko_code = $row['倉庫Ｃ'];
                    $souko_name = $row['倉庫名'];
                    $Unsou_code = $row['運送Ｃ'];
                    $Unsou_name = $row['運送略称'];
                    $shipping_moto = $row['出荷元'];
                    $shipping_moto_name = $row['出荷元名'];
                    $Shouhin_code = $row['商品Ｃ'];
                    $Shouhin_name = $row['品名'];
                    $Tana_num = $row['棚番'];
                    $Konpou_num = $row['梱包入数'];
                    $Shouhin_num = $row['数量'];
                    $Picking_num = $row['ピッキング数量'];
                    $Shori_Flg = $row['処理Ｆ'];
                    $Tokuisaki_name = $row['得意先名'];
                    $shouhin_JAN    = $row['ＪＡＮ'];

                    // 取得した値を配列に追加
                    $arr_Picking_DATA[] = array(
                        'syuka_day' => $syuka_day,                  // SK.出荷日
                        'souko_code' => $souko_code,                // SK.倉庫Ｃ
                        'souko_name' => $souko_name,                // SO.倉庫名
                        'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                        'Unsou_name' => $Unsou_name,                // US.運送略称
                        'shipping_moto' => $shipping_moto,          // SL.出荷元
                        'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                        'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                        'Shouhin_name' => $Shouhin_name,            // SH.品名
                        'Tana_num' => $Tana_num,                    // RZ.棚番
                        'Konpou_num' => $Konpou_num,                // SH.梱包入数
                        'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                        'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                        'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                        'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                        'shouhin_JAN' => $shouhin_JAN               // JANコード
                    );
                }

                oci_free_statement($stid);
                oci_close($conn);

                //  break;
        }
    }



    if (isset($_GET['back_flg'])) {
        $back_flg = $_GET['back_flg'];
        // print($back_flg);
    }

    // =========
    // janテスト　
    // =========
    if (isset($_GET['scan_b'])) {
        $sortKey = 'location_note';
        //    print($_GET['scan_b'] . "ここ");
    }


    // ============================= HTPK テーブル 処理 =============================
    // === 接続準備
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
    }

    //   $sql = "SELECT 商品Ｃ,処理Ｆ,商品名 FROM HTPK WHERE 処理Ｆ = 2 and 登録日時 = :syuka_day";
    $sql = "SELECT 商品Ｃ,処理Ｆ,商品名 FROM HTPK WHERE 処理Ｆ = 2 OR 処理Ｆ = 8 OR 処理Ｆ = 9";
    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($stid);
    }

    // oci_bind_by_name($select_seq_stid, ':syuka_day', $syuka_day);

    oci_execute($stid);

    $arr_Zumi_DATA = array();
    while ($row = oci_fetch_assoc($stid)) {
        // カラム名を指定して値を取得
        $HTPK_Souhin_Code = $row['商品Ｃ'];
        $HTPK_Sori_Flg = $row['処理Ｆ'];
        $HTPK_Souhin_Name = $row['商品名'];

        // 取得した値を配列に追加
        $arr_Zumi_DATA[] = array(
            'HTPK_Souhin_Code' => $HTPK_Souhin_Code,
            'HTPK_Sori_Flg' => $HTPK_Sori_Flg,
            'HTPK_Souhin_Name' => $HTPK_Souhin_Name,
        );
    }

    // var_dump($arr_Zumi_DATA);

    oci_free_statement($stid);
    oci_close($conn);
}

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">
    <link rel="stylesheet" href="./css/common.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <!-- jQuery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <title>ピッキング 04</title>


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

    <div id="app">
        <div class="head_box_02">
            <div class="head_content_02">
                <span class="home_sub_icon_span">
                    <i class="fa-solid fa-thumbtack"></i>
                </span>

                <span class="page_title">
                    ピッキング対象選択
                </span>
            </div>
        </div>

        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <i class="fa-solid fa-warehouse"></i>
                        <span class="souko_icon_box">
                            <?php echo h($get_souko_name); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_icon_box">
                            <i class="fa-solid fa-truck"></i>
                        </span>

                        <span class="unsou_text_box">
                            <?php echo h($get_unsou_name); ?>
                        </span>
                    </div>

                </div>

                <div class="" id="menu_btn_box">

                    <div class="dropdown_02" @click="toggleDropdown(1)">
                        <button class="dropbtn" id="order_btn" value="並替">並替</button>
                        <div class="dropdown-content" :class="{show: isOpen[1]}">
                            <button type="button" @click="handleButtonClick('location_note')">ロケ順</button>
                            <button type="button" @click="handleButtonClick('num_note')">数量順</button>
                            <button type="button" @click="handleButtonClick('tokki_note')">特記順</button>
                            <button type="button" @click="handleButtonClick('bikou_note')">備考順</button>
                        </div>
                    </div>

                    <div>

                        <button type="button" id="all_select_btn">
                            全表示
                        </button>

                    </div>

                </div>


                <div class="cp_iptxt_03">
                    <label class="ef_03">
                        <input type="number" id="get_JAN" name="get_JAN" placeholder="Scan JAN">
                    </label>
                </div>
                <p id="err_JAN" style="color:red;"></p>


                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ********* テストsql 運送コード単数 , 備考・特記あり ********* -->
            <?php if (isset($arr_Picking_DATA[0]['sql_one_tokki'])) : ?>
                <?php
                $one_condition = getCondition($arr_Picking_DATA[0]['sql_one_tokki']);
                //  print($one_condition);
                ?>
                <input type="hidden" name="sql_one_tokki" id="sql_one_tokki" value="<?php print $one_condition; ?>">
            <?php endif; ?>

            <!-- ********* five.php 「戻る」 ********* -->
            <?php if (isset($_GET['back_one_condition'])) : ?>
                <input type="hidden" name="back_sql_one_tokki" id="back_sql_one_tokki" value="<?php print $back_one_condition; ?>">
            <?php else : ?>
                <?php $back_one_condition = ""; ?>
            <?php endif; ?>

            <!-- ********* 複数処理の場合 ********* -->
            <?php if (isset($arr_Picking_DATA[0]['Multiple_Sql'])) : ?>
                <?php
                $Multiple_condition = getCondition_Multiple($arr_Picking_DATA[0]['Multiple_Sql']);
                print($Multiple_condition);
                ?>
                <input type="hidden" name="Multiple_condition" id="Multiple_condition" value="<?php print $Multiple_condition; ?>">
            <?php endif; ?>

            <!-- ==================================================== -->
            <!-- ============== テーブルレイアウト 開始 =============== -->
            <!-- ==================================================== -->
            <div class="">

                <table class="" border="1">

                    <thead>
                        <tr>
                            <th>ロケ</th>
                            <th>数量</th>
                            <th>ケース</th>
                            <th>バラ</th>
                            <th>品名・品番</th>
                            <th>特記・備考</th>
                        </tr>

                        <?php

                        // Sagyou_NOW_Flg = 2 : 残
                        // Sagyou_NOW_Flg = 1 : 選択中
                        // Sagyou_NOW_Flg = 0 : 作業前 

                        foreach ($arr_Picking_DATA as $Picking_VAL) {

                            $Sagyou_NOW_Flg = 0;

                            $shouhin_name_part1 = mb_substr($Picking_VAL['Shouhin_name'], 0, 20);
                            $shouhin_name_part2 = mb_substr($Picking_VAL['Shouhin_name'], 20);

                            foreach ($arr_Zumi_DATA as $Zumi_DATA) {

                                if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 2) {
                                    $Sagyou_NOW_Flg = 1;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 8) {
                                    $Sagyou_NOW_Flg = 2;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 9 && isset($_GET["show_all"])) {
                                    $Sagyou_NOW_Flg = 3;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                } else if ($Zumi_DATA['HTPK_Souhin_Code'] == $Picking_VAL['Shouhin_code'] && $Zumi_DATA['HTPK_Souhin_Name'] == $Picking_VAL['Shouhin_name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 9) {
                                    $Sagyou_NOW_Flg = 4;
                                    //print("Sagyou_NOW_Flg" . $Sagyou_NOW_Flg);
                                    break;
                                }
                            }

                            // ケース薄 計算
                            if ($Picking_VAL['Shouhin_num'] != 0 && $Picking_VAL['Konpou_num']) {
                                // ケース数
                                $Case_num_View = floor($Picking_VAL['Shouhin_num'] / $Picking_VAL['Konpou_num']);
                            }

                            // バラ数 計算
                            if ($Picking_VAL['Konpou_num'] != 0) {
                                $Bara_num_View = $Picking_VAL['Shouhin_num'] % $Picking_VAL['Konpou_num'];
                            }


                            if ($Sagyou_NOW_Flg == 0) {

                                // === 運送便（単数）, 備考・特記あり
                                if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "") {
                                    $encoded_sql_one_tokki = urlencode($sql_one_tokki);
                                    echo '<tr data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) . '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . urldecode($Picking_VAL['Toki_Zikou']) . '&now_sql=' . $encoded_sql_one_tokki . '">';

                                    // === 運送便（複数） , 備考・特記あり,　※備考・特記ありも複数　=> five.php から戻ってきた
                                } else if (isset($Picking_VAL['sql_multiple_tokki']) && $Picking_VAL['sql_multiple_tokki'] != "") {

                                    $Multiple_Sql_Url = urlencode($Picking_VAL['sql_multiple_tokki']);
                                    echo '<tr data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) .  '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . urldecode($Picking_VAL['Toki_Zikou']) . '&now_sql_multiple=' . $Multiple_Sql_Url . '">';
                                } else if (isset($Picking_VAL['Multiple_Sql']) && $Picking_VAL['Multiple_Sql'] != "") {

                                    $Multiple_Sql_Url = urlencode($Picking_VAL['Multiple_Sql']);
                                    echo '<tr data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) . '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . urldecode($Picking_VAL['Toki_Zikou']) . '&now_sql_multiple=' . $Multiple_Sql_Url . '">';
                                } else {

                                    // === 通常処理　特記事項 あり
                                    if (isset($Picking_VAL['Toki_Zikou']) && $Picking_VAL['Toki_Zikou'] != "") {
                                        echo '<tr data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) . '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  urldecode($Picking_VAL['Toki_Zikou']) . '">';
                                    } else {

                                        // === 通常処理　特記事項 なし
                                        echo '<tr data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) . '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($Picking_VAL['shouhin_JAN']) . '">';
                                    }
                                }


                                echo '<td>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記がある
                                if (isset($Picking_VAL['Toki_Zikou']) && $Picking_VAL['Toki_Zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    // === 特記がない
                                    echo '<td><span class="toki_list">' . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }


                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 1) {
                                echo '<tr style="background: yellow;" id="sagyou_now" class="sagyou_now">';
                                echo '<td><span id="sagyou_now_text">作業中<i class="fa-regular fa-circle-stop"></i></span></td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if (isset($Picking_VAL['Toki_Zikou']) && $Picking_VAL['Toki_Zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }


                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 2) {

                                if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "") {

                                    if (isset($Picking_VAL['Denpyou_SEQ']) && $Picking_VAL['Denpyou_SEQ'] != "") {
                                        $encoded_sql_one_tokki = urlencode($sql_one_tokki);
                                        echo '<tr style="background: green;" data-href="./five.php?select_day=' . urlencode($select_day) .
                                            '&souko_code=' . urlencode($select_souko_code) .
                                            '&unsou_code=' . urlencode($select_unsou_code) .
                                            '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) .
                                            '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) .
                                            '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) .
                                            '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) .
                                            '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) .
                                            '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) .
                                            '&Tokuisaki=' . urlencode($Tokuisaki_name) .
                                            '&tana_num=' . urlencode($Picking_VAL['Tana_num']) .
                                            '&case_num=' . urlencode($Case_num_View) .
                                            '&bara_num=' . urlencode($Bara_num_View) .
                                            '&shouhin_jan=' . urlencode($shouhin_JAN) .
                                            '&tokki_zikou=' . $Picking_VAL['Toki_Zikou'] .
                                            '&Denpyou_SEQ=' . urldecode($Picking_VAL['Denpyou_SEQ']) . // 伝票 SEQ
                                            '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    } else {

                                        $encoded_sql_one_tokki = urlencode($sql_one_tokki);
                                        echo '<tr style="background: green;" data-href="./five.php?select_day=' . urlencode($select_day) .
                                            '&souko_code=' . urlencode($select_souko_code) .
                                            '&unsou_code=' . urlencode($select_unsou_code) .
                                            '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) .
                                            '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) .
                                            '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) .
                                            '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) .
                                            '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) .
                                            '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) .
                                            '&Tokuisaki=' . urlencode($Tokuisaki_name) .
                                            '&tana_num=' . urlencode($Picking_VAL['Tana_num']) .
                                            '&case_num=' . urlencode($Case_num_View) .
                                            '&bara_num=' . urlencode($Bara_num_View) .
                                            '&shouhin_jan=' . urlencode($shouhin_JAN) .
                                            '&now_sql=' . $encoded_sql_one_tokki . '">';
                                    }
                                } else {

                                    echo '<tr style="background: green"; data-href="./five.php?select_day=' . urlencode($select_day) . '&souko_code=' . urlencode($select_souko_code) . '&unsou_code=' . urlencode($select_unsou_code) . '&unsou_name=' . urlencode($Picking_VAL['Unsou_name']) . '&shipping_moto=' . urlencode($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . urlencode($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . urlencode($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . urlencode($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . urlencode($Picking_VAL['Shouhin_num']) . '&Tokuisaki=' . urlencode($Tokuisaki_name) . '&tana_num=' . urlencode($Picking_VAL['Tana_num']) . '&case_num=' . urlencode($Case_num_View) . '&bara_num=' . urlencode($Bara_num_View) . '&shouhin_jan=' . urlencode($shouhin_JAN) . '">';
                                }

                                echo '<td><span id="sagyou_now_text">残<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if (isset($Picking_VAL['Toki_Zikou']) && $Picking_VAL['Toki_Zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }

                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 3) {
                                // === 全数表示
                                $row_class = 'picking-row1';

                                if ($Picking_VAL['sql_one_tokki'] != "") {
                                    $encoded_sql_one_tokki = urlencode($sql_one_tokki);
                                    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                                } else {

                                    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                }

                                echo '<td><span id="sagyou_now_text_ok">作業完了<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';

                                echo '</tr>';
                            } else if ($Sagyou_NOW_Flg == 4) {
                                // 「確定」
                                $row_class = 'picking-row2';
                                echo '<tr class="' . $row_class . '" data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                echo '<td><span id="sagyou_now_text_ok">作業完了<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                // === 特記
                                if (isset($Picking_VAL['Toki_Zikou']) && $Picking_VAL['Toki_Zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                }

                                echo '</tr>';
                            } /* else if ($Sagyou_NOW_Flg == 55) {

                                $encoded_sql_one_tokki = urlencode($sql_one_tokki);
                                echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';

                                echo '<td>' . $Picking_VAL['Tana_num'] . '</td>';
                                echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                                echo '<td>' .  $Case_num_View . '</td>';
                                echo '<td>' . $Bara_num_View . '</td>';

                                echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";

                                echo '<td><span class="toki_list">' . $Picking_VAL['Toki_Zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }
                            */
                        }


                        ?>

                    </thead>

                </table>


            </div> <!-- head_02 -->

        </div> <!-- ======== END container ========= -->



        <!-- フッターメニュー -->
        <footer class="footer-menu">
            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./third.php?selectedSouko=" . urlencode($select_souko_code) . "&selected_day=" . urlencode($select_day) . "&souko_name=" . urlencode($get_souko_name) . "&back_flg=" . $back_flg; ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
                <li><a href="" id="Kousin_Btn">更新</a></li>
            </ul>
        </footer>


    </div> <!-- ======== END app ========= -->

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                isOpen: {
                    1: false,
                },
                selectValue: ''
            },
            methods: {
                // === トグル open, close 処理
                toggleDropdown(menuId) {
                    this.isOpen[menuId] = !this.isOpen[menuId];
                },
                // === プルダウンボタンの値取得処理
                handleButtonClick(value) {
                    this.selectValue = value;
                    console.log("プルダウン:::" + this.selectValue);

                    var selectedDay = '<?php echo $select_day; ?>';
                    var select_souko_code = '<?php echo $select_souko_code; ?>';
                    var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                    var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';

                    // 運送　単数, 備考・特記
                    var one_condition = '<?php echo htmlspecialchars(json_encode(trim($one_condition)), ENT_QUOTES, 'UTF-8'); ?>';

                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&sort_key=' + this.selectValue;

                    if (one_condition !== "") {
                        url += '&one_condition=' + encodeURIComponent(one_condition);
                    }

                    window.location.href = url;


                }
            },

        });
    </script>

    <script>
        $(document).ready(function() {

            // 全角を半角に変換
            function convertToHalfWidth(input) {
                return input.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                    return String.fromCharCode(s.charCodeAt(0) - 65248); // 全角文字のUnicode値から半角文字に変換
                });
            }

            // JAN のテキストにフォーカスを当てる
            $('#get_JAN').focus();
            $('#get_JAN').val("");

            // JAN エラーフラグ
            var Jan_Flg = 0;

            // JAN 判定
            //  $('#get_JAN').blur(function() {
            $('#get_JAN').change(function() {

                Jan_Flg = 0;

                var input_JAN = $('#get_JAN').val();
                var convertedValue_JAN = convertToHalfWidth(input_JAN);
                $(this).val(convertedValue_JAN);

                // JAN コード判定
                $(".shouhin_JAN").each(function() {
                    console.log("item:::" + $(this).val() + "\n");
                    var shouhin_JAN = $(this).val();

                    // *** 商品コード 取得ループ start
                    var Shouhin_code_val_values = [];
                    $(this).closest('tr').find('.Shouhin_code_val').each(function() {
                        // .Shouhin_code_valクラスの値を配列に追加
                        Shouhin_code_val_values.push($(this).val());
                    });
                    // *** 商品コード 取得ループ END


                    // JAN
                    if (shouhin_JAN === $('#get_JAN').val()) {
                        var parentElement = $(this).closest('tr');


                        if (!parentElement.hasClass('sagyou_now')) { // 'sagyou_now'クラスがない場合のみ遷移する
                            var dataHref = parentElement.data('href') + "&scan_b=bar_san";
                            console.log("値一致:::" + dataHref);

                            // 
                            for (var i = 0; i < Shouhin_code_val_values.length; i++) {
                                console.log("商品コード ループ HIT:::" + Shouhin_code_val_values[i]);
                            }

                            window.location.href = dataHref;

                            Jan_Flg = 1;
                            // return false;
                        } else {
                            console.log("sagyou_nowクラスが付いているため、画面遷移しません。");
                            Jan_Flg = 11;
                            // return false;
                        }

                    }

                });

                // JAN エラーメッセージ
                if (Jan_Flg === 0) {
                    $('#err_JAN').html("JAN コードに一致する商品がありません。<br>値：(" + $('#get_JAN').val() + ")");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                } else if (Jan_Flg === 1) {
                    $('#err_JAN').html("対象のJANコード商品へ遷移します。");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                } else {
                    $('#err_JAN').html("対象のJANコード商品は作業中です。");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                }

            });


            $('table tbody').on('click', 'tr', function() {
                var row = $(this).closest('tr');
                var Shouhin_num = row.find('td:eq(1)').text().trim();
                var Shouhin_name = row.find('td:eq(4)').text().trim();
                var shipping_moto_name = row.find('td:eq(5)').text().trim();

                console.log("Shouhin_num");
                console.log("Shouhin_name");
                console.log("shipping_moto_name");

                // 取得した値を詳細画面へ渡して遷移
                window.location.href = 'detail.php?Shouhin_num=' + Shouhin_num + '&Shouhin_name=' + Shouhin_name + '&shipping_moto_name=' + shipping_moto_name;
            });

            // 「更新」ボタンを押した時の処理
            $('#Kousin_Btn').on('click', function() {
                location.reload();
            });


            // 「全表示ボタン」押したら
            $('#all_select_btn').on('click', function() {

                var selectedDay = '<?php echo $select_day; ?>';
                var select_souko_code = '<?php echo $select_souko_code; ?>';
                var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                var get_souko_name = '<?php echo $get_souko_name; ?>';

                // === 運送便 単発 , 備考・特記
                var back_one_condition = '<?php echo json_encode(" " . $back_one_condition); ?>';
                var one_condition = '<?php echo htmlspecialchars(json_encode(trim($one_condition)), ENT_QUOTES, 'UTF-8'); ?>';

                var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&show_all=' + '200';

                if (back_one_condition != "") {
                    url += '&one_condition=' + encodeURIComponent(back_one_condition);
                } else if (one_condition != "") {
                    url += '&one_condition=' + encodeURIComponent(one_condition);
                }

                window.location.href = url;

            });


        });
    </script>

    <script>
        $('tr[data-href]').click(function() {
            var href = $(this).data('href');

            console.log("リンク値:::" + href);
            window.location.href = href;
        });
    </script>


</body>

</html>