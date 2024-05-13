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

    // =================== 「確定」 ===========================
    // ========= five.php「確定ボタン」を押した時の処理 =========
    // =======================================================
    if (isset($_GET['kakutei_btn'])) {

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

        // 倉庫名
        $get_souko_name = $souko_name;
        // 運送名
        $get_unsou_name = $unsou_name;

        // 「残」フラグ  
        $Kakutei_Btn_Flg = $_GET['Kakutei_Btn_Flg'];

        /*
        print("残 フラグ:::" . $Kakutei_Btn_Flg . "<br>");
        print("select_day :::" . $select_day . "<br>");
        print("select_souko_code :::" . $select_souko_code . "<br>");
        print("select_unsou_code :::" . $select_unsou_code . "<br>");
        print("shouhin_jan :::" . $shouhin_jan . "<br>");
        print("shouhin_code :::" . $shouhin_code . "<br>");
        print("shouhin_name :::" . $shouhin_name . "<br>");
        */
        print("ここ 01");
        print("shouhin_name :::" . $shouhin_name . "<br>");
        print("count_num_val :::" . $count_num_val . "<br>");
        print("Kakutei_Btn_Flg :::" . $Kakutei_Btn_Flg . "<br>");
        print("伝票番号 :::" . $Dennpyou_num . "<br>");
        print("伝票行番号 :::" . $Dennpyou_Gyou_num . "<br>");


        // ========= HTPK へ 「残」の識別をインサートする。=========

        // === 100 => 残
        if ($Kakutei_Btn_Flg == 100) {

            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
            }

            //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
            $sql = "UPDATE HTPK SET 処理Ｆ = 8, ピッキング数量 = :Pikking_Num WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":Pikking_Num",  $count_num_val);
            oci_bind_by_name($stid, ":SHOHIN_CODE",  $shouhin_code);
            oci_bind_by_name($stid, ":SHOUHIN_NAME", $shouhin_name);

            $result_update = oci_execute($stid);
            if (!$result_update) {
                $e = oci_error($stid);
                echo "[HTPKテーブル] ::: updatetエラー:" . $e["message"];
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
---  AND PK.処理Ｆ<> 9 -- 完了は除く
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
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

            // ================================== 200 正常処理
        } else {
            // === 200 残じゃない

            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
            }

            //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
            $sql = "UPDATE HTPK SET 処理Ｆ = 9, ピッキング数量 = :Pikking_Num WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":Pikking_Num",  $count_num_val);
            oci_bind_by_name($stid, ":SHOHIN_CODE",  $shouhin_code);
            oci_bind_by_name($stid, ":SHOUHIN_NAME", $shouhin_name);

            $result_update = oci_execute($stid);
            if (!$result_update) {
                $e = oci_error($stid);
                echo "[HTPKテーブル] ::: updatetエラー:" . $e["message"];
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
---  AND PK.処理Ｆ<> 9 -- 完了は除く
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
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
        }

        // ==============================================================
        // ==========================   戻るボタン =======================   
        // ============================================================== 
    } else if (isset($_GET['five_back'])) {

        $shouhin_code = $_GET['shouhin_code'];
        $shouhin_name = $_GET['shouhin_name'];

        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        //            $sql = "UPDATE HTPK SET 処理Ｆ = 8 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME ";
        $sql = "UPDATE HTPK SET 処理Ｆ = 9 WHERE 商品Ｃ = :SHOHIN_CODE AND 商品名 = :SHOUHIN_NAME";

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
        oci_close($conn);
    } // ===================== END  isset($_GET['five_back']


    // ==========================================================
    // ================= 通常処理 =================
    // ==========================================================
    if (isset($_GET['day'])) {
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];

        // デフォルトの並べ替え

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


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // ****** 戻ってきた処理  ******
        // === five.php から戻ってきた場合　111 の値が入る

        /*
        if (isset($_GET['five_back'])) {    
            $five_back = $_GET['five_back'];
        }
        */
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
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ,SK.特記事項
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元,SK.特記事項";

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
                oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

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

                // 数量順に並べ変え
            case 'num_note':

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
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ
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
                oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

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
                // デフォルトの並べ替え

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
   AND SK.倉庫Ｃ = :SELECT_SOUKO
   AND SK.運送Ｃ = :SELECT_UNSOU
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9 -- 完了は除く
 GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
         ,SK.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SJ.得意先名,SH.ＪＡＮ
 ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

                $stid = oci_parse($conn, $sql);
                if (!$stid) {
                    $e = oci_error($stid);
                }

                oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
                oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
                oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

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

                break;
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
        print($_GET['scan_b'] . "ここ");
    }



    // ========= five.php から戻ってきた処理 =========
    /*
    if ($five_back === "111") {

        $denpyou_num = $_GET['denpyou_num'];
        $denpyou_Gyou_num = $_GET['denpyou_Gyou_num'];
        $shouhin_code = $_GET['shouhin_code'];
        $shouhin_name = $_GET['shouhin_name'];

        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }
    } else {
        // === third.php からきた場合
        print("back じゃない（新規）");
    }
    */

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

    <style>
        /* ドロップダウンリスト */
        .dropdown_02 {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            overflow: auto;
            border: 1px solid #ddd;
            z-index: 1;
            left: 0%;
        }

        .dropdown-content button {
            color: black;
            padding: 10px 12px;
            text-decoration: none;
            display: block;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        /* テーブル要素　ホバー */
        tr:hover {
            background: gray;
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



                <div>
                    <input type="number" id="get_JAN" name="get_JAN">
                    <p id="err_JAN" style="color:red;"></p>
                </div>


                <hr class="hr_01">

            </div> <!-- head_01 END -->

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
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 8) {
                                    $Sagyou_NOW_Flg = 2;
                                    break;
                                } else if ($Picking_VAL['Shouhin_code'] == $Zumi_DATA['HTPK_Souhin_Code'] && $Picking_VAL['Shouhin_name'] == $Zumi_DATA['HTPK_Souhin_Name'] && $Zumi_DATA['HTPK_Sori_Flg'] == 9) {
                                    $Sagyou_NOW_Flg = 3;
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
                                echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                echo '<td>' . $Picking_VAL['Tana_num'] . '</td>';
                            } else if ($Sagyou_NOW_Flg == 1) {
                                echo '<tr style="background: yellow;" id="sagyou_now" class="sagyou_now">';
                                echo '<td><span id="sagyou_now_text">作業中<i class="fa-regular fa-circle-stop"></i></span></td>';
                            } else if ($Sagyou_NOW_Flg == 2) {
                                echo '<tr style="background: green;" data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                echo '<td><span id="sagyou_now_text">残<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';
                            } else if ($Sagyou_NOW_Flg == 3) {
                                echo '<tr style="display: none;" data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '">';
                                echo '<td><span id="sagyou_now_text">残<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';
                            }

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

                    window.location.href = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&sort_key=' + this.selectValue;

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

        });
    </script>

    <script>
        $('tr[data-href]').click(function() {
            var href = $(this).data('href');

            console.log("リンク値:::" + href);
            window.location.href = href;
        });

        /*
        $(function() {
            setTimeout(function() {
                location.reload();
            }, 10000);

        });
        */
    </script>


</body>

</html>