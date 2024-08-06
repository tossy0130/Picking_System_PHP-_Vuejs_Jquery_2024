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

    // ================= 通常処理 =================
    $selected_day = isset($_GET['selected_day']) ? $_GET['selected_day'] : '';
    $selected_shippingname = isset($_GET['selected_shippingname']) ? $_GET['selected_shippingname'] : '';
    $selected_shippingcode = isset($_GET['selected_shippingcode']) ? $_GET['selected_shippingcode'] : '';
    $selected_soukocode = isset($_GET['selected_soukocode']) ? $_GET['selected_soukocode'] : '';
    $selected_soukoname = isset($_GET['selected_soukoname']) ? $_GET['selected_soukoname'] : '';

    // ============================= DB 処理 =============================
    // === 接続準備
    $conn = oci_connect(
        DB_USER,
        DB_PASSWORD,
        DB_CONNECTION_STRING,
        DB_CHARSET
    );

    if (!$conn) {
        $e = oci_error();
        echo "接続エラー: " . $e['message'];
        exit;
    }
        $sql_select = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名 
                             ,SUM(SL.数量) AS 数量
                             ,SUM(PK.ピッキング数量) AS ピッキング数量
                             ,SH.ＪＡＮ
                         FROM SJTR SJ, SKTR SK, SOMF SO, USMF US,SHMF SH
                             ,RZMF RZ,HTPK PK
                             ,SLTR SL
                        WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                          AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                          AND SK.伝票行番号 = SL.伝票行番号
                          AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
                          AND SL.伝票番号   = PK.伝票番号(+)
                          AND SL.伝票行番号 = PK.伝票行番号(+)
                          AND SL.伝票行枝番 = PK.伝票行枝番(+)
                          AND SL.倉庫Ｃ = SO.倉庫Ｃ
                          AND SK.運送Ｃ = US.運送Ｃ
                          AND SL.商品Ｃ = SH.商品Ｃ
                          AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                          AND SL.商品Ｃ = RZ.商品Ｃ
                          AND SK.出荷日 = :SELECT_DATE
                          AND SL.倉庫Ｃ = :SELECT_SOUKO
                          AND SK.運送Ｃ = :SELECT_UNSOU
                          AND PK.処理Ｆ = 9
                        GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名,SH.ＪＡＮ
                        ORDER BY SL.倉庫Ｃ,SK.運送Ｃ,SL.商品Ｃ";

        $stid_select = oci_parse($conn, $sql_select);
        if (!$stid_select) {
            $e = oci_error($stid_select);
            echo "SQLエラー: " . $e['message'];
            exit;
        }

        oci_bind_by_name($stid_select, ":SELECT_DATE", $selected_day);
        oci_bind_by_name($stid_select, ":SELECT_SOUKO", $selected_soukocode);
        oci_bind_by_name($stid_select, ":SELECT_UNSOU", $selected_shippingcode);

        oci_execute($stid_select);

        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid_select)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $shouhin_JAN    = $row['ＪＡＮ'];

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                'Unsou_name' => $Unsou_name,                // US.運送略称
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // 品名
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'shouhin_JAN' => $shouhin_JAN               // JANコード
            );

        }
        // 特記あり
        $sql = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫略称 AS 倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,
                       SM.出荷元名,SK.特記事項
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US, HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.伝票行番号 = PK.伝票行番号
                   AND SL.倉庫Ｃ = SO.倉庫Ｃ
                   AND SL.倉庫Ｃ = PK.倉庫Ｃ
                   AND SL.出荷元 = SM.出荷元Ｃ(+)
                   AND SK.運送Ｃ = US.運送Ｃ
                   AND SK.出荷日 = :SELECT_DATE
                   AND US.運送略称 = :SELECT_UNSOUNAME
                   AND SL.倉庫Ｃ = :SELECT_SOUKO
                   AND PK.処理Ｆ = 9
                 GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫略称,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
                 ORDER BY SL.倉庫Ｃ,SK.運送Ｃ,SL.出荷元,SM.出荷元名 ,SK.特記事項";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $selected_day);
        oci_bind_by_name($stid, ":SELECT_UNSOUNAME", $selected_shippingname);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $selected_soukocode);

        oci_execute($stid);
        
        // 結果を取得して表示
        //$data = array();
        $arr_Unsou_data = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            $Unsou_code = $row['運送Ｃ'];
            $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $tokki_zikou = $row['特記事項'];

            // ユニークなキーを作成
            $key = $Unsou_code . '_' . $Unsou_name . '_' . $syuka_day . '_' . $souko_code . '_' . $souko_name;
            if (!isset($arr_Unsou_data[$key])) {
                // データが存在しない場合、新しい連想配列を作成
                $arr_Unsou_data[$key] = array(
                    'Unsou_code' => $Unsou_code,
                    'Unsou_name' => $Unsou_name,
                    'details' => array() // 詳細情報を格納する配列を初期化
                );
            }

            // 重複をチェックして詳細情報を追加
            $isDuplicate = false;
            foreach ($arr_Unsou_data[$key]['details'] as $detail) {

                if (
                    $detail['shipping_moto'] == $shipping_moto && $detail['shipping_moto_name'] == $shipping_moto_name
                    && $detail['tokki_zikou'] == $tokki_zikou
                ) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {

                $arr_Unsou_data[$key]['details'][] = array(
                    'shipping_moto' => $shipping_moto,
                    'shipping_moto_name' => $shipping_moto_name,
                    'tokki_zikou' => $tokki_zikou
                );
            }
        }

        // ==================================================================================================
        // ========================================== 備考・特記選択時処理 ==========================================    
        // ==================================================================================================
        // ソート用キー取得
        $detailBiko = "";
        $detailTokki = "";

        if (isset($_GET['detail_biko']) || isset($_GET['detail_tokki'])) {
            $detailBiko = $_GET['detail_biko'];
            $detailTokki = $_GET['detail_tokki'];

            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
            }
            $sql = "SELECT SK.出荷日,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
                    ,SUM(SL.数量) AS 数量    
                    ,SUM(PK.ピッキング数量) AS ピッキング数量
                    ,SH.ＪＡＮ
                    ,SK.特記事項
                    FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                    ,RZMF RZ
                    ,HTPK PK
                    WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                    AND SK.伝票行番号 = SL.伝票行番号
                    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                    AND SK.伝票行番号 = SL.伝票行番号
                    AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                    AND SL.伝票番号   = PK.伝票番号(+)
                    AND SL.伝票行番号 = PK.伝票行番号(+)
                    AND SL.伝票行枝番 = PK.伝票行枝番(+)
                    AND SL.倉庫Ｃ = SO.倉庫Ｃ
                    AND SL.出荷元 = SM.出荷元Ｃ(+)
                    AND SK.運送Ｃ = US.運送Ｃ
                    AND SL.商品Ｃ = SH.商品Ｃ
                    AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                    AND SL.商品Ｃ = RZ.商品Ｃ
                    AND SK.出荷日 = :SELECT_DATE
                    AND SL.倉庫Ｃ = :SELECT_SOUKO
                    AND SK.運送Ｃ = :SELECT_UNSOU ";
                    
            if ($detailBiko != "ー") {
                $sql .= "AND SM.出荷元名 = :SELECT_BIKO ";
            } else {
                $sql .= "AND SM.出荷元名 IS NULL ";
            }

            if ($detailTokki != "ーーー") {
                $sql .= "AND SK.特記事項 = :SELECT_TOKKI ";
            } else {
                $sql .= "AND SK.特記事項 IS NULL ";
            }
            $sql .= " AND PK.処理Ｆ = 9
                    GROUP BY SK.出荷日,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名,SH.ＪＡＮ,SK.特記事項
                    ORDER BY SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE", $selected_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $selected_soukocode);
            oci_bind_by_name($stid, ":SELECT_UNSOU", $selected_shippingcode);
            if ($detailBiko != "ー") {
                oci_bind_by_name($stid, ":SELECT_BIKO", $detailBiko);
            }

            if ($detailTokki != "ーーー") {
                oci_bind_by_name($stid, ":SELECT_TOKKI", $detailTokki);
            }

            oci_execute($stid);

            $arr_Picking_DATA = array();
            while ($row = oci_fetch_assoc($stid)) {
                // カラム名を指定して値を取得
                $syuka_day = $row['出荷日'];
                $Unsou_code = $row['運送Ｃ'];
                $Unsou_name = $row['運送略称'];
                $shipping_moto = $row['出荷元'];
                $shipping_moto_name = $row['出荷元名'];
                $Shouhin_code = $row['商品Ｃ'];
                $Shouhin_name = $row['品名'];
                $Shouhin_num = $row['数量'];
                $Picking_num = $row['ピッキング数量'];
                $shouhin_JAN    = $row['ＪＡＮ'];
                $Toki_Zikou = $row['特記事項'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,                  // SK.出荷日
                    'Unsou_code' => $Unsou_code,                // SK.運送Ｃ
                    'Unsou_name' => $Unsou_name,                // US.運送略称
                    'shipping_moto' => $shipping_moto,          // 出荷元
                    'shipping_moto_name' => $shipping_moto_name,// 出荷元名
                    'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                    'Shouhin_name' => $Shouhin_name,            // 品名
                    'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                    'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                    'shouhin_JAN' => $shouhin_JAN,              // JANコード
                    'Toki_Zikou' => $Toki_Zikou                 // 特記事項
                );
            }
        }

        $utiwakeTotal = "";
        // クリック時に内訳を取得するSQLを追加 2024/07/19
        if (isset($_GET['selected_shouhincode']) && isset($_GET['selected_shouhinjan'])) {
            $selected_shouhincode = $_GET['selected_shouhincode'];

            $sql_get = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名
                            ,SUM(SL.数量) AS 数量
                            ,SUM(PK.ピッキング数量) AS ピッキング数量
                            ,SH.ＪＡＮ
                            ,COUNT(CM2.得意先名) AS CNT
                            ,CM.集計得意先Ｃ
                            ,CM2.得意先名 AS 集計得意先名
                        FROM SJTR SJ, SKTR SK, SOMF SO, USMF US,SHMF SH
                            ,RZMF RZ, HTPK PK
                            ,SLTR SL, CMMF CM, CMMF CM2
                        WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                        AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                        AND SK.伝票行番号 = SL.伝票行番号
                        AND SJ.得意先Ｃ = CM.得意先Ｃ
                        AND CM.集計得意先Ｃ = CM2.得意先Ｃ
                        AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
                        AND SL.伝票番号   = PK.伝票番号(+)
                        AND SL.伝票行番号 = PK.伝票行番号(+)
                        AND SL.伝票行枝番 = PK.伝票行枝番(+)
                        AND SL.倉庫Ｃ = SO.倉庫Ｃ
                        AND SK.運送Ｃ = US.運送Ｃ
                        AND SL.商品Ｃ = SH.商品Ｃ
                        AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                        AND SL.商品Ｃ = RZ.商品Ｃ
                        AND SK.出荷日 = :SELECT_DATE
                        AND SL.倉庫Ｃ = :SELECT_SOUKO
                        AND SK.運送Ｃ = :SELECT_UNSOU
                        AND SL.商品Ｃ = :SELECT_SHOUHIN
                        AND PK.処理Ｆ = 9
                        GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名
                                ,SH.ＪＡＮ,CM.集計得意先Ｃ,CM2.得意先Ｃ,CM2.得意先名
                        ORDER BY CM2.得意先Ｃ,CM2.得意先名 ";
        
            $stid_get = oci_parse($conn, $sql_get);
            if (!$stid_get) {
                $e = oci_error($stid_get);
                echo "SQLエラー: " . $e['message'];
                exit;
            }

            oci_bind_by_name($stid_get, ":SELECT_DATE", $selected_day);
            oci_bind_by_name($stid_get, ":SELECT_SOUKO", $selected_soukocode);
            oci_bind_by_name($stid_get, ":SELECT_UNSOU", $selected_shippingcode);
            oci_bind_by_name($stid_get, ":SELECT_SHOUHIN", $selected_shouhincode);

            oci_execute($stid_get);

            $utiwakeTotal = '';
            $arr_GETTING_DATA = array();
            while ($row = oci_fetch_assoc($stid_get)) {
                // カラム名を指定して値を取得
                $tokuimei = $row['集計得意先名'];
                $Picking_num = $row['ピッキング数量'];
                
                $utiwakeTotal .= $tokuimei . ' × ' . $Picking_num . '<br>';
            }
            // 最後の '<br>' を削除
            $utiwakeTotal = rtrim($utiwakeTotal, '<br>');

        } else if (isset($_GET['selected_shouhincode']) && !isset($_GET['selected_shouhinjan'])) {
            $selected_shouhincode = $_GET['selected_shouhincode'];
            $detailBiko = $_GET['detail_biko'];
            $detailTokki = $_GET['detail_tokki'];
            

            $sql_get = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名
                            ,SUM(SL.数量) AS 数量
                            ,SUM(PK.ピッキング数量) AS ピッキング数量
                            ,SH.ＪＡＮ
                            ,COUNT(CM2.得意先名) AS CNT
                            ,CM.集計得意先Ｃ
                            ,CM2.得意先名 AS 集計得意先名
                        FROM SJTR SJ, SKTR SK, SOMF SO, USMF US,SHMF SH
                            ,RZMF RZ, HTPK PK
                            ,SLTR SL, CMMF CM, CMMF CM2,SMMF SM
                        WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                        AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                        AND SK.伝票行番号 = SL.伝票行番号
                        AND SJ.得意先Ｃ = CM.得意先Ｃ
                        AND CM.集計得意先Ｃ = CM2.得意先Ｃ
                        AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ
                        AND SL.伝票番号   = PK.伝票番号(+)
                        AND SL.伝票行番号 = PK.伝票行番号(+)
                        AND SL.伝票行枝番 = PK.伝票行枝番(+)
                        AND SL.倉庫Ｃ = SO.倉庫Ｃ
                        AND SK.運送Ｃ = US.運送Ｃ
                        AND SL.商品Ｃ = SH.商品Ｃ
                        AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                        AND SL.出荷元 = SM.出荷元Ｃ(+)
                        AND SL.商品Ｃ = RZ.商品Ｃ
                        AND SK.出荷日 = :SELECT_DATE
                        AND SL.倉庫Ｃ = :SELECT_SOUKO
                        AND SK.運送Ｃ = :SELECT_UNSOU
                        AND SL.商品Ｃ = :SELECT_SHOUHIN ";

            if ($detailBiko != "ー") {
                $sql_get .= "AND SM.出荷元名 = :SELECT_BIKO ";
            } else {
                $sql_get .= "AND SM.出荷元名 IS NULL ";
            }

            if ($detailTokki != "ーーー") {
                $sql_get .= "AND SK.特記事項 = :SELECT_TOKKI ";
            } else {
                $sql_get .= "AND SK.特記事項 IS NULL ";
            }

            $sql_get .= " AND PK.処理Ｆ = 9
                        GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.商品Ｃ,SH.品名
                                ,SH.ＪＡＮ,CM.集計得意先Ｃ,CM2.得意先Ｃ,CM2.得意先名
                        ORDER BY CM2.得意先Ｃ,CM2.得意先名 ";
        
            $stid_get = oci_parse($conn, $sql_get);
            if (!$stid_get) {
                $e = oci_error($stid_get);
                echo "SQLエラー: " . $e['message'];
                exit;
            }

            oci_bind_by_name($stid_get, ":SELECT_DATE", $selected_day);
            oci_bind_by_name($stid_get, ":SELECT_SOUKO", $selected_soukocode);
            oci_bind_by_name($stid_get, ":SELECT_UNSOU", $selected_shippingcode);
            oci_bind_by_name($stid_get, ":SELECT_SHOUHIN", $selected_shouhincode);
            
            if ($detailBiko != "ー") {
                oci_bind_by_name($stid_get, ":SELECT_BIKO", $detailBiko);
            }

            if ($detailTokki != "ーーー") {
                oci_bind_by_name($stid_get, ":SELECT_TOKKI", $detailTokki);
            }
            //echo $sql_get. '<br>';
            oci_execute($stid_get);

            $utiwakeTotal = '';
            $arr_GETTING_DATA = array();
            while ($row = oci_fetch_assoc($stid_get)) {
                // カラム名を指定して値を取得
                $tokuimei = $row['集計得意先名'];
                $Picking_num = $row['ピッキング数量'];
                
                $utiwakeTotal .= $tokuimei . ' × ' . $Picking_num . '<br>';
            }
            // 最後の '<br>' を削除
            $utiwakeTotal = rtrim($utiwakeTotal, '<br>');
        }
        
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/nine.css">

    <link rel="stylesheet" href="./css/common.css">

    <link href="./css/all.css" rel="stylesheet">

    <link rel="stylesheet" href="./css/sweetalert2.css">

    <!-- jQuery cdn -->
    <script src="./js/jquery.min.js"></script>

    <title>ピッキング実績照会照会画面</title>

    <style>
        .show {
            display: block;
        }
    </style>

</head>

<body>

    <div id="app">
        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <span class="souko_icon_box">
                            <?php echo h($selected_soukoname); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_text_box">
                            <?php echo h($selected_shippingname); ?>
                        </span>
                    </div>

                </div>

                <div class="bikotokki_box_v">
                    <?php
                    $idx = 1;
                    foreach ($arr_Unsou_data as $row) {
                        echo '<div class="dropdown_v" data-menuid="' . $idx . '">';
                        echo '<button class="dropbtn_v" value="' . $row["Unsou_code"] . '" data-unsou-code="' . $row["Unsou_code"] . '" data-unsou-name="' . $row["Unsou_name"] . '">' . '備考・特記' . '</button>';
                        echo '<div class="dropdown-contents_v" data-menuid="' . $idx . '">';
                        foreach ($row["details"] as $detail) {
                            // 備考が空の場合
                            if ($detail["shipping_moto_name"] == null) {
                                $detail["shipping_moto_name"] = "ー";
                                $detail["shipping_moto"] = "ー";
                            }

                            // 特記事項が空の場合
                            if ($detail["tokki_zikou"] == null) {
                                $detail["tokki_zikou"] = "ーーー";
                            }
                            echo '<button type="button" data-company="' . $detail["shipping_moto_name"] .
                                '" data-value="' . $detail["shipping_moto"] . '" data-tokki="' . $detail["tokki_zikou"] . '">' . $detail["shipping_moto_name"]  .  ' ' . $detail["tokki_zikou"] . '</button>';
                        }
                        echo '</div></div>';
                        $idx++;
                    }
                    ?>

                    <!-- 選択したアイテムを表示するためのdivを追加 -->
                    <div class="selectedItems">
                        <?php echo "選択した項目:" . $detailBiko . " " . $detailTokki;?>
                    </div>
                </div>

                <p id="err_JAN" style="color:red;"></p>
                
                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ==================================================== -->
            <!-- ============== テーブルレイアウト 開始 =============== -->
            <!-- ==================================================== -->
            <div class="">
                <table class="">
                    <thead>
                        <?php
                        // 初期表示
                        if (!isset($_GET['detail_biko']) || !isset($_GET['detail_tokki'])) {
                            
                                foreach ($arr_Picking_DATA as $Picking_VAL) {
                                    // 2024/07/13 追加
                                    echo '<tr class="clickable-row" data-total="' . $Picking_VAL['Picking_num'] . '" data-day="' . $selected_day . '" data-shippingname="' . $selected_shippingname . '" data-shippingcode="' . $selected_shippingcode . '" data-soukocode="' . $selected_soukocode . '" data-soukoname="' . $selected_soukoname . '" data-shouhincode="' . $Picking_VAL['Shouhin_code'] . '" data-shouhinjan="' . $Picking_VAL['shouhin_JAN'] . '">';
                                    echo '<td>品名:' . $Picking_VAL['Shouhin_name'] . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' . '</td>';
                                    echo '</tr>';
                                }

                        } else {
                            // 備考:あり、特記:ありの場合
                            if ($_GET['detail_biko'] != 'ー' && $_GET['detail_tokki'] != 'ーーー') {
                                foreach ($arr_Picking_DATA as $Picking_VAL) {
                                    // 2024/07/20 追加
                                    echo '<tr class="clickable-row" data-total="' . $Picking_VAL['Picking_num'] . '" data-day="' . $selected_day . '" data-shippingname="' . $selected_shippingname . '" data-shippingcode="' . $selected_shippingcode . '" data-soukocode="' . $selected_soukocode . '" data-soukoname="' . $selected_soukoname . '" data-shouhincode="' . $Picking_VAL['Shouhin_code'] . '" data-shouhinjan="' . $Picking_VAL['shouhin_JAN'] . '" data-biko="' . $Picking_VAL['shipping_moto_name'] . '" data-tokki="' . $Picking_VAL['Toki_Zikou'] . '">';
                                    echo '<td>品名:' . $Picking_VAL['Shouhin_name'] . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' . '<span><span style="display:inline-block;position:relative;left:32%"> 備考:</span>' . '<span style="display:inline-block;position:relative;left:32%">' . $Picking_VAL['shipping_moto_name'] . '</span></span>' . '<span style=float:right;>特記:' . $Picking_VAL['Toki_Zikou'] . '</span>' . '</td>';
                                    echo '</tr>';
                                }
                            // 備考:あり、特記:なしの場合
                            } else if ($_GET['detail_biko'] != 'ー' && $_GET['detail_tokki'] == 'ーーー') {
                                // 特記:なし状態判別変数 2024/07/20
                                $tokki_nasi = $_GET['detail_tokki'];
                                foreach ($arr_Picking_DATA as $Picking_VAL) {
                                    // 2024/07/20 追加
                                    echo '<tr class="clickable-row" data-total="' . $Picking_VAL['Picking_num'] . '" data-day="' . $selected_day . '" data-shippingname="' . $selected_shippingname . '" data-shippingcode="' . $selected_shippingcode . '" data-soukocode="' . $selected_soukocode . '" data-soukoname="' . $selected_soukoname . '" data-shouhincode="' . $Picking_VAL['Shouhin_code'] . '" data-shouhinjan="' . $Picking_VAL['shouhin_JAN'] . '" data-biko="' . $Picking_VAL['shipping_moto_name'] . '" data-tokki="' . $tokki_nasi . '">';
                                    echo '<td>品名:' . $Picking_VAL['Shouhin_name'] . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' . '<span style="float: right;"> 備考:' . $Picking_VAL['shipping_moto_name'] . '</span>' . '</td>';
                                    echo '</tr>';
                                }
                            // 備考:なし、特記:ありの場合
                            } else if ($_GET['detail_biko'] == 'ー' && $_GET['detail_tokki'] != 'ーーー') {
                                // 備考:なし状態判別変数 2024/07/20
                                $biko_nasi = $_GET['detail_biko'];
                                foreach ($arr_Picking_DATA as $Picking_VAL) {
                                    // 2024/07/20 追加
                                    echo '<tr class="clickable-row" data-total="' . $Picking_VAL['Picking_num'] . '" data-day="' . $selected_day . '" data-shippingname="' . $selected_shippingname . '" data-shippingcode="' . $selected_shippingcode . '" data-soukocode="' . $selected_soukocode . '" data-soukoname="' . $selected_soukoname . '" data-shouhincode="' . $Picking_VAL['Shouhin_code'] . '" data-shouhinjan="' . $Picking_VAL['shouhin_JAN'] . '" data-biko="' . $biko_nasi . '" data-tokki="' . $Picking_VAL['Toki_Zikou'] . '">';
                                    echo '<td>品名:' . $Picking_VAL['Shouhin_name']  . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' . '<span style="float: right;"> 特記:' . $Picking_VAL['Toki_Zikou'] . '</span>' . '</td>';
                                    echo '</tr>';
                                }
                            // 備考:なし、 特記:なしの場合
                            } else if ($_GET['detail_biko'] == 'ー' && $_GET['detail_tokki'] == 'ーーー') {
                                // 備考:なし、特記:なし状態判別変数 2024/07/20
                                $biko_nasi = $_GET['detail_biko'];
                                $tokki_nasi = $_GET['detail_tokki'];
                                foreach ($arr_Picking_DATA as $Picking_VAL) {
                                    // 2024/07/20 追加
                                    echo '<tr class="clickable-row" data-total="' . $Picking_VAL['Picking_num'] . '" data-day="' . $selected_day . '" data-shippingname="' . $selected_shippingname . '" data-shippingcode="' . $selected_shippingcode . '" data-soukocode="' . $selected_soukocode . '" data-soukoname="' . $selected_soukoname . '" data-shouhincode="' . $Picking_VAL['Shouhin_code'] . '" data-shouhinjan="' . $Picking_VAL['shouhin_JAN'] . '" data-biko="' . $biko_nasi . '" data-tokki="' . $tokki_nasi . '">';
                                    echo '<td>品名:' . $Picking_VAL['Shouhin_name']  . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' . '</td>';
                                    echo '</tr>';
                                }
                            }
                        }

                        ?>

                    </thead>

                </table>


            </div> <!-- head_02 -->

        </div> <!-- ======== END container ========= -->

        <script>
            // ページが完全に読み込まれた後に実行されるスクリプト   2024/07/13 script追加
            document.addEventListener('DOMContentLoaded', function() {
                // クラスが'clickable-row'のすべての要素を選択する
                const rows = document.querySelectorAll('.clickable-row');
                // 各row要素にクリックイベントリスナーを追加
                rows.forEach(row => {
                    // 行がクリックされたときに実行される関数を定義
                    row.addEventListener('click', function() {
                        // 現在のスクロール位置を取得して保存
                        var position = window.scrollY;
                        localStorage.setItem('scrollPosition', position);
                        // クリックされた行からデータ属性を取得
                        var total = this.dataset.total;
                        var selected_day = this.dataset.day;
                        var selected_shippingname = this.dataset.shippingname;
                        var selected_shippingcode = this.dataset.shippingcode;
                        var selected_soukocode = this.dataset.soukocode;
                        var selected_soukoname = this.dataset.soukoname;
                        var selected_shouhincode = this.dataset.shouhincode;
                        var selected_shouhinjan =this.dataset.shouhinjan;
                        console.log(selected_shouhinjan);
                        // 2024/07/20 備考・特記の有無判別
                        if (this.dataset.biko !== undefined) {
                            var selected_biko = this.dataset.biko;
                            console.log('備考は:' + selected_biko);
                        }
                        
                        if (this.dataset.tokki !== undefined) {
                            var selected_tokki = this.dataset.tokki;
                            console.log('特記は:' + selected_tokki);
                        }
                        //return false;

                        // 2024/07/19 クリックでリダイレクト
                        if (this.dataset.biko === undefined && this.dataset.tokki === undefined) {
                            // 新しいURLを作成し、選択されたデータをURLパラメーターとして追加する
                            console.log('通常ルート');
                            var url = window.location.pathname + "?selected_day=" + encodeURIComponent(selected_day) + "&selected_shippingname=" + encodeURIComponent(selected_shippingname) + "&selected_shippingcode=" + encodeURIComponent(selected_shippingcode)
                                 + "&selected_soukocode=" + encodeURIComponent(selected_soukocode) + "&selected_soukoname=" + encodeURIComponent(selected_soukoname) + "&selected_shouhincode=" + encodeURIComponent(selected_shouhincode) + "&selected_shouhinjan=" + encodeURIComponent(selected_shouhinjan) + "&total=" + encodeURIComponent(total);
                        } else {    // 備考・特記が選択されている場合 2024/07/20
                            var url = window.location.pathname + "?selected_day=" + encodeURIComponent(selected_day) + "&selected_shippingname=" + encodeURIComponent(selected_shippingname) + "&selected_shippingcode=" + encodeURIComponent(selected_shippingcode)
                                 + "&selected_soukocode=" + encodeURIComponent(selected_soukocode) + "&selected_soukoname=" + encodeURIComponent(selected_soukoname) + "&detail_biko=" + encodeURIComponent(selected_biko) + "&detail_tokki=" + encodeURIComponent(selected_tokki) + "&selected_shouhincode=" + encodeURIComponent(selected_shouhincode) + "&total=" + encodeURIComponent(total);
                        }
                        
                        // 作成したURLにリダイレクトする
                        window.location.href = url;
                        
                    });
                    
                });

            });


        </script>

        <script>
            // ページが完全に読み込まれた後に実行されるスクリプト   2024/07/20 script追加
            document.addEventListener('DOMContentLoaded', function() {
                // 現在のURLの検索パラメータを取得する
                const params = new URLSearchParams(window.location.search);
                console.log('現在のパラメーターは' + params);
                
                // URLパラメーターに'selected_day'が含まれているかをチェックする
                if (params.has('total')) {
                    // 'total'パラメーターの値を取得する
                    const total = params.get('total');
                    console.log('総数は：' + total);
                    
                    // PHP変数'utiwakeTotal'をJavaScriptに渡す
                    const utiwakeTotal = '<?php echo $utiwakeTotal; ?>'; // ここでPHPの変数を利用する場合

                    console.log(utiwakeTotal);
                    var scrollPosition = localStorage.getItem('scrollPosition');
                    
                    if (scrollPosition !== null) {
                        window.scrollTo(0, parseInt(scrollPosition, 10));
                        console.log('Scroll position set to: ' + scrollPosition);
                    }
                    
                    Swal.fire({
                        html: `
                            <div style="text-align: left;">
                                <div style="text-align: center; margin-bottom: 10px;">
                                    <span>総数:</span><span>${total}</span>
                                </div>
                                <div >
                                    <span>内訳:</span><br>
                                    <div style="display: flex; align-items: center; margin-top: 5px">
                                        <span>${utiwakeTotal}</span>
                                    </div>
                                </div>
                            </div>`
                    }).then((result) => {
                        // ダイアログが閉じられた後にパラメータを削除
                        if (result.isConfirmed || result.isDismissed) {
                            params.delete('total');
                            const newUrl = window.location.pathname + '?' + params.toString();
                            window.history.replaceState({}, '', newUrl);
                            console.log('新しいURLは' + newUrl);
                        }
                    });

                    // スクロール位置を保持する
                    localStorage.removeItem('scrollPosition');
                }
            });
        </script>

        <!-- フッターメニュー -->
        <footer class="footer-menu">

            <div class="cp_iptxt_02">
                <label class="ef_02">
                    <input type="number" id="get_JAN" name="get_JAN" placeholder="Scan JAN">
                </label>
            </div>  

            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./eight.php?selected_day=" . UrlEncode_Val_Check($selected_day) . "&selected_shippingname=" . urlencode($selected_shippingname) . "&selected_shippingcode="  . UrlEncode_Val_Check($selected_shippingcode); ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
                <?php $url = "./nine.php?selected_day=" . UrlEncode_Val_Check($selected_day) . "&selected_shippingname=" . urlencode($selected_shippingname) . "&selected_shippingcode="  . UrlEncode_Val_Check($selected_shippingcode) . "&selected_soukocode=" . UrlEncode_Val_Check($selected_soukocode) . "&selected_soukoname=" . UrlEncode_Val_Check($selected_soukoname); ?>
                <li><a href="<?php echo $url; ?>">更新</a></li>
            </ul>
        </footer>


    </div> <!-- ======== END app ========= -->

    <script src="./js/sweetalert2.min.js"></script>

    <script src="./js/vue@2.js"></script>

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
                }
            },

        });
    </script>

    <script>
        (function($) {
            $(document).ready(function() {

                // ドロップダウンメニューの表示を切り替える
                $('.dropdown_v').on('click', function() {
                    var menuId = $(this).data('menuid');
                    var dropdownContent = $('.dropdown-contents_v[data-menuid=' + menuId + ']');

                    dropdownContent.toggleClass('show');

                    // 運送コード, 運送便名, 取得
                    var unsouCode = $(this).find('button.dropbtn_v').data('unsou-code');
                    var unsouName = $(this).find('button.dropbtn_v').data('unsou-name');

                    $('#selectedUnsouCode').text(unsouCode);
                    $('#selectedUnsouName').text(unsouName);

                });

                // プルダウンメニューのボタンがクリックされたときの処理
                var selectedValues = [];
                
                $('.dropdown-contents_v button').on('click', function() {
                    var data_value = $(this).attr("data-value");
                    var data_tokki = $(this).attr("data-tokki");
                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSoukoCode = '<?php echo $selected_soukocode; ?>';
                    var selectedSoukoName = '<?php echo $selected_soukoname; ?>';

                    // 詳細データ取得
                    selected_Detail_Code = $(this).data('value');
                    selectedUnsou_Detail_Name = $(this).data('company');
                    console.log("備考：" + selectedUnsou_Detail_Name);
                    // 特記事項取得
                    selectedUnsou_Detail_tokki = $(this).data('tokki');
                    console.log("特記：" + selectedUnsou_Detail_tokki);

                    // 親要素の、運送コード, 運送名を取得
                    var unsouCode_m = $(this).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-code');
                    var unsouName_m = $(this).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-name');
                    
                    var url = window.location.pathname + '?selected_day=' + encodeURIComponent(selectedDay) + '&selected_shippingname=' + encodeURIComponent(unsouName_m)  + '&selected_shippingcode=' + encodeURIComponent(unsouCode_m)
                         + '&selected_soukocode=' + encodeURIComponent(selectedSoukoCode) + '&selected_soukoname=' + encodeURIComponent(selectedSoukoName) + '&detail_biko=' + encodeURIComponent(selectedUnsou_Detail_Name) + '&detail_tokki=' + encodeURIComponent(selectedUnsou_Detail_tokki);
                    window.location.href = url;


                    // 要素を追加
                    /* if (selectedValues.indexOf(data_value) === -1 && selectedValues.indexOf(unsouCode_m) === -1) {
                        selectedValues.push(unsouCode_m + data_value);
                        
                        $("#selectedValues_set_next_val").append('<div class="set_next_val">' + unsouName_m + ':' +
                            unsouCode_m + ':' + selected_Detail_Code + ':' + selectedUnsou_Detail_tokki + ',' + '</div>');
                    } else {
                    } */

                });



                // 全角を半角に変換
                function convertToHalfWidth(input) {
                    return input.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                        return String.fromCharCode(s.charCodeAt(0) - 65248); // 全角文字のUnicode値から半角文字に変換
                    });
                }

                // JAN のテキストにフォーカスを当てる
                $('#get_JAN').focus();
                $('#get_JAN').val("");

                // 現在のJANコードにフォーカスを当てたテーブル行のHTML要素を保持する変数
                var focusedTableRow = null;

                // JAN 判定
                $('#get_JAN').change(function() {
                    //  現在のフォーカスされているテーブル行のフォーカスを外す
                    // JAN エラーフラグ
                    var Jan_Flg = 0;
                    if (focusedTableRow !== null) {
                        focusedTableRow.css("background-color", "");
                    }

                    var input_JAN = $('#get_JAN').val();
                    var convertedValue_JAN = convertToHalfWidth(input_JAN);
                    $(this).val(convertedValue_JAN);

                    // JAN コード判定
                    $(".shouhin_JAN").each(function() {
                        var shouhin_JAN = $(this).text();

                        // JAN
                        if (shouhin_JAN === input_JAN) {
                            // 一致した行のJANコードの入力欄にフォーカスを当てる
                            $(this).focus();
                            // テーブル行の背景色を変更（任意のスタイルを適用）
                            $(this).closest('tr').css('background-color', 'yellow');
                            // 現在のテーブル行を保持
                            focusedTableRow = $(this).closest('tr');
                            // スクロールしてテーブル行が表示されるようにする（必要に応じて）
                            $(this).closest('tr')[0].scrollIntoView();
                            // 処理が完了したらループから抜ける
                            Jan_Flg = 1;
                        }

                    });

                    // JAN エラーメッセージ
                    if (Jan_Flg === 0) {
                        Swal.fire({
                            positon: "center",
                            title: "一致する商品がありません。",
                            text: "JAN:" + $('#get_JAN').val()
                        });
                        $('#get_JAN').val("");

                    } else if (Jan_Flg === 1) {
                        //$('#err_JAN').html("JAN コードと一致している商品があります。<br>値:(" + $('#get_JAN').val() + ")");
                        $('#get_JAN').val("");
                    }
                });

            });
        })(jQuery);
    </script>


</body>

</html>