<?php

error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);
ini_set('display_errors', 1);

require __DIR__ . "/conf.php";
require_once(dirname(__FILE__) . "/class/init_val.php");
require(dirname(__FILE__) . "/class/function.php");

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
        dprint("01:::" . $_SESSION['soko_name']);
    } else {
        $souko_name = $_SESSION['soko_name'];
        dprint("02:::" . $_SESSION['soko_name']);
    }


    if (isset($_GET['day'])) {
        $select_day = $_GET['day'];
    }

    if (isset($_GET['unsou_name']) && (isset($_GET['forth_pattern']) || isset($_GET['fukusuu_select']))) {
        $get_unsou_name = $_GET['unsou_name'];
        $_SESSION['unsou_name'] = $get_unsou_name;
        $get_unsou_name = $_SESSION['unsou_name'];
    }


    if ($_SESSION['unsou_name']) {
        $get_unsou_name = $_SESSION['unsou_name'];
    }

    if (isset($_GET['unsou_code'])) {
        $select_unsou_code = $_GET['unsou_code'];
    }

    // 2024/06/05
    if (isset($_GET['selectedToki_Code'])) {
        $selectedToki_Code = $_GET['selectedToki_Code'];
    }

    if (isset($_GET['fukusuu_unsouo_num'])) {
        $fukusuu_unsouo_num = $_GET['fukusuu_unsouo_num'];
    }

    if (isset($_GET['fukusuu_select'])) {
        $fukusuu_select = $_GET['fukusuu_select'];
    }

    if (isset($_GET['fukusuu_select_val'])) {
        $fukusuu_select_val = $_GET['fukusuu_select_val'];
    }

    if (isset($_SESSION['selected_index']) && isset($_SESSION['selected_jan'])) {
        $selected_index = $_SESSION['selected_index'];
        $selected_jan = $_SESSION['selected_jan'];
    }


    // ===================================================
    // ==== test　作業中 入れる処理　24_0723  作業解除処理
    // ===================================================

    $arr_tmp = [];

    $arr_response_sagyou_now = [];

    $test_flg = 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['arr_Sagyou_Tyuu_data'])) {
        header('Content-Type: application/json; charset=UTF-8');

        if (isset($_POST['arr_Sagyou_Tyuu_data'])) {
            $data = $_POST['arr_Sagyou_Tyuu_data'];
            $arr_response_sagyou_now = [];
            $arr_response_sagyou_now['items'] = []; // 

            foreach ($data as $item) {

                $arr_response_sagyou_now['items'][] = [
                    'shouhin_name_sagyou_now' => trim($item['shouhin_name_aj']),
                    'shouhin_code_val_sagyou_now' => trim($item['shouhin_code_val_aj'])
                ];
            }

            // 正常なレスポンス
            $arr_response_sagyou_now['status'] = 'success';
        } else {
            // エラー時のレスポンス
            $arr_response_sagyou_now['status'] = 'error';
            $arr_response_sagyou_now['message'] = 'データが送信されていません';
        }

        // エンコード
        $tmp = json_encode($arr_response_sagyou_now);
        // デコード
        $arr_response_sagyou_now_val = json_decode($tmp, true);

        // === 配列の個数を取得
        $loop_num = count($arr_response_sagyou_now_val['items']);

        $Sagyou_now_Shouhin_Code = "";

        $test_flg = 1;

        // === 商品コード取得
        for ($i = 0; $i < $loop_num; $i++) {
            if (
                $i == $loop_num - 1
            ) {
                $Sagyou_now_Shouhin_Code .= $arr_response_sagyou_now_val['items'][$i]['shouhin_code_val_sagyou_now'];
            } else {
                $Sagyou_now_Shouhin_Code .= $arr_response_sagyou_now_val['items'][$i]['shouhin_code_val_sagyou_now'] . ",";
            }

            // print($arr_response_sagyou_now_val['items'][$i]['shouhin_code_val_sagyou_now'] . ",");
        }

        $_SESSION['Sagyou_now_Shouhin_Code'] = $Sagyou_now_Shouhin_Code;

        // echo json_encode($arr_response_sagyou_now); // JSONレスポンスを返す
        echo json_encode(['status' => 'success', 'message' => 'first ajax OK']);

        exit;
    }

    // =========================================
    // === 追加　作業中 解除  24_0801
    // =========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['syori_seq_val_post'])) {
        header('Content-Type: application/json; charset=UTF-8');

        if (isset($_POST['syori_seq_val_post'])) {
            $data = $_POST['syori_seq_val_post'];
        }

        // エンコード
        $syori_seq_val_post = json_encode($data);
        // デコード
        $syori_seq_val_post_val = json_decode($syori_seq_val_post, true);

        $_SESSION['syori_seq_val_post_val'] = $syori_seq_val_post_val;

        echo json_encode(['status' => 'success', 'message' => 'Session updated']);

        exit;
    }


    // ====== ログイン ID ======
    if (isset($_SESSION['input_login_id'])) {
        $session_login_id = $_SESSION['input_login_id'];
        dprintBR("ログインID:::" . $session_login_id);
    }

    $sql_multiple_cut = "";

    dprintBR("現在のパターン数:::");
    dprintBR($_SESSION['forth_pattern']);
    dprintBR($_SESSION['fukusuu_select']);


    // *********************************************** 連続処理 対応 **********************************
    // =====================================
    // === 追加 「連続処理」処理 （確定、通常　用）　削除
    // =====================================

    // 単数
    if (isset($_SESSION['four_five_default_SQL'])) {
        unset($_SESSION['four_five_default_SQL']);
        dprintBR("session four_five_default_SQL 削除 OK");
    }

    // 単数、備考・特記
    if (isset($_SESSION['sql_one_option'])) {
        unset($_SESSION['sql_one_option']);
        dprintBR("session sql_one_option 削除 OK");
    }

    // 複数
    if (isset($_SESSION['multiple_sql'])) {
        unset($_SESSION['multiple_sql']);
        unset($_SESSION['multiple_sql_cut']);

        dprintBR("session multiple_sql 削除 OK");
        dprintBR("session multiple_sql_cut 削除 OK");
    }


    // === five.php に値が残るエラー対策
    // ************************
    // === 追加 24_0716
    // ************************
    unset($_SESSION['kakutei_Syukka_Yotei_Num']);
    unset($_SESSION['kakutei_Denpyou_SEQ']);
    unset($_SESSION['kakutei_shouhin_code']);
    unset($_SESSION['Syuka_Yotei_SUM']);


    // **********************************************
    // === 追加 24_0703  ブラウザバック削除処理
    // **********************************************
    dprintBR("ブラウザバックphp:::");
    dprintBR($_SESSION['five_back_Syori_SEQ']);

    if (isset($_SESSION['five_back_Syori_SEQ'])) {

        dprintBR("ブラウザバック（有り） 処理中");

        // === five.php の　ブラウザバックしてきた処理SEQを取得 
        $five_back_Syori_SEQ = $_SESSION['five_back_Syori_SEQ'];

        // =============== Delete 処理 =================
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        $sql = "DELETE FROM HTPK WHERE 処理ＳＥＱ = :Syori_SEQ";
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        oci_bind_by_name($stid, ":Syori_SEQ", $five_back_Syori_SEQ);
        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
        } else {
            oci_commit($conn);
            // 削除が成功した場合、セッション変数をリセット
            unset($_SESSION['five_back_Syori_SEQ']);

            // ************************
            // === 追加 24_0702
            // ************************
            unset($_SESSION['kakutei_Syukka_Yotei_Num']);
            unset($_SESSION['kakutei_Denpyou_SEQ']);
            unset($_SESSION['kakutei_shouhin_code']);
            unset($_SESSION['Syuka_Yotei_SUM']);
        }

        oci_free_statement($stid);
        oci_close($conn);

        /*
        unset($_SESSION['kakutei_Denpyou_SEQ']);
        unset($_SESSION['kakutei_shouhin_code']);
        unset($_SESSION['Syuka_Yotei_SUM']);
      
        */
    }


    // 2024/06/07 修正
    // ==========================================================
    // ================= 通常処理　（運送単数） =================
    // ==========================================================


    // ********* four.php 通常 => five.php 「戻る」 ルート OK *********

    // isset($_GET['third_default_sql']) && $_GET['third_default_sql'] == "third_default_sql_VAL" 「third.php からの 判別」
    // isset($_SESSION['third_default_sql']) && $_SESSION['third_default_sql'] == "third_default_sql_VAL"  「five.php からの戻り 判別」
    // ========= 備考・特記　なし
    if (
        isset($_GET['forth_pattern']) && $_GET['forth_pattern'] === 'one' ||
        isset($_SESSION['forth_pattern']) && $_SESSION['forth_pattern'] === 'one'/* &&
        !isset($_GET['sort_areab']) && !isset($_GET['sort_areac']) ||
        isset($_GET['sort_key']) && isset($_GET['sort_areaa']) ||
        isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 0 ||
        isset($_GET["all_flg"]) && $_GET["all_flg"] == 0
        */
    ) {

        dprint("通常処理 （運送単数）" . "<br>");


        // === ********* third.php から、　four.php へ持ってきた 状態判別パラメータ *********
        if (isset($_GET['forth_pattern']) && !empty($_GET['forth_pattern'])) {
            $forth_pattern = $_GET['forth_pattern'];
            $_SESSION['forth_pattern'] = $forth_pattern;

            dprint("通常パラメータ" . $forth_pattern . "<br>");
        }

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

        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }
        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // SQL 修正 24_0522 最新

        // === SQL 受け渡し
        if (isset($_GET['default_root_sql_back'])) {
            $sql = $_GET['default_root_sql_back'];
            $_SESSION['one_now_sql_back_kanryou'] = $sql;
        } else {

            /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
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
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
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
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02 ";
*/
            $sql = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量    
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                      ,SH.品番
                 FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
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
                   AND SK.運送Ｃ = :SELECT_UNSOU
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02 ";
        }

        //2024/07/04 全表示 処理追加
        if (!isset($_GET["show_all_flg"])) {    // show_all_flgがセットされていない場合
            $sql .= "AND NVL(PK.処理Ｆ,0) <> 8 AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {                                // show_all_flgがセットされている場合
            $all_flg = 0;
        }
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
        $sql .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項 ";
*/
        $sql .= "GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項,SH.品番 ";

        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'num_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY SK.特記事項,SM.出荷元名,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
*/
                $sql .= "ORDER BY SK.特記事項,SM.出荷元名,SL.倉庫Ｃ,SK.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
                break;

            case 'bikou_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY SM.出荷元名,SK.特記事項,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY SM.出荷元名,SK.特記事項,SL.倉庫Ｃ,SK.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            default:
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;
        }

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);


        // === SQL をセッションへ格納
        $_SESSION['four_five_default_SQL'] = $sql;
        //echo $sql;
        oci_execute($stid);

        $item_index = 1;
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
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou    = $row['特記事項'];
            $SH_Hinban    = $row['品番']; // === 24_0807 追加  SH.品番


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
                'shouhin_JAN' => $shouhin_JAN,              // JANコード
                'tokki_zikou' => $tokki_zikou,              // 特記事項
                'four_status' => 'default_root',             // five.php への遷移状態
                'item_index' => $item_index,                   // インデックス番号
                'SH_Hinban' => $SH_Hinban  // === 24_0807 追加 SH.品番
            );
            $item_index++;
        }

        oci_free_statement($stid);
        oci_close($conn);

        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // 2024/06/07 修正
        // ================================================================================
        // ====================  単数 運送コード 特記・備考あり  ============================
        // ================================================================================

        // === three.php からの 状態判別 用　パラメータ selectedToki_Code
    } else if ((isset($_GET['forth_pattern']) && $_GET['forth_pattern'] === "two")
        || isset($_SESSION['forth_pattern']) && $_SESSION['forth_pattern'] === "two"
        //        || isset($_GET['five_back_button']) && !isset($_GET['sort_areaa']) && !isset($_GET['sort_areac'])
        //        || isset($_GET['sort_key']) && isset($_GET['sort_areab']) || isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 1
        //        || isset($_GET["all_flg"]) && $_GET["all_flg"] == 1
    ) {
        dprint("単数 運送コード 特記・備考あり" . "<br>");

        //      dprint("単数 運送コード 特記・備考あり");
        // ============= 運送便（単数） & 備考・特記あり ================


        // === third.php の 判別用パラメータをセッションへ格納

        // ==========================================
        // === four.php から　five.php へ戻ってきた場合
        // ==========================================

        // === GET パラメータ取得
        $select_unsou_code = $_GET['unsou_code'];
        if (isset($_GET['unsou_name'])) {
            $get_unsou_name = $_GET['unsou_name'];
        }
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];

        $sql = $_SESSION['sql_one_option'];
        $_SESSION['sql_one_option'] = $sql;


        if (isset($_GET['get_souko_name'])) {
            $get_souko_name = $_GET['get_souko_name'];
        }

        // ========================================
        // === third.php => から　four.php 　判別の値
        // ========================================
        if (isset($_GET['forth_pattern']) && $_GET['forth_pattern'] == 'two') {
            $_SESSION['forth_pattern'] = $_GET['forth_pattern'];
        }


        // 運送便　& 備考, 特記事項　文字列   :コロン区切り
        $selectedToki_Code = $_GET['selectedToki_Code'];

        //dprint($selectedToki_Code . "<br><br>");

        // === 特記、備考 部分を保持
        if (isset($_SESSION['selectedToki_Code']) && !empty($_SESSION['selectedToki_Code'])) {
            $selectedToki_Code = $_SESSION['selectedToki_Code'];
        } else {
            $_SESSION['selectedToki_Code'] = $selectedToki_Code;
        }


        // index , 0 => 運送名 , 1 => 運送コード , 2 => 出荷元 , 3 => 特記事項
        // $arr_SQL = explode(":", $selectedToki_Code);
        $arr_SQL = explode("\t", $selectedToki_Code);

        /* print_r($arr_SQL);
        print($arr_SQL[0] . "<br>");
        print($arr_SQL[1] . "<br>");
        print($arr_SQL[2] . "<br>");
        print($arr_SQL[3] . "<br>");
 */
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        if (isset($_GET['unsou_name'])) {
            $get_unsou_name = $_GET['unsou_name'];
        }

        // 可変部分の条件を生成
        $conditions = [];
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
        $conditionSet[0] = "SJ.運送Ｃ = '{$arr_SQL[1]}'";
*/
        $conditionSet[0] = "SK.運送Ｃ = '{$arr_SQL[1]}'";
        /* 24/07/26
        if ($arr_SQL[2] !== '-') {
            $conditionSet[1] = "SL.出荷元 = '{$arr_SQL[2]}'";
        } else {
            $conditionSet[1] = "SL.出荷元 IS NULL";
        }
*/
        if ($arr_SQL[2] == "'") {
            $conditionSet[1] = "SL.出荷元 = ''''";
        } else if ($arr_SQL[2] !== '-') {
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
        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }

        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // SQL 修正 24_0522 最新
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
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
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
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
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";
*/
        $sql = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量    
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                      ,SH.品番
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
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
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";

        // 可変部分の条件を追加
        if (!empty($conditions)) {
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        //2024/07/08修正 全表示処理追加
        if (!isset($_GET["show_all_flg"])) {
            $sql .= "AND NVL(PK.処理Ｆ,0) <> 8 AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {
            $all_flg = 1;
        }

        // GROUP BY句とORDER BY句を追加
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
        $sql .= " GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項 ";
*/
        $sql .= " GROUP BY SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項,SH.品番 ";

        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'num_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY 数量,SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY SK.特記事項,数量,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
*/
                $sql .= "ORDER BY SK.特記事項,数量,SL.倉庫Ｃ,SK.運送Ｃ,SL.商品Ｃ,SL.出荷元 ";
                break;

            case 'bikou_note':
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY SM.出荷元名,数量,SL.倉庫Ｃ,SJ.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY SM.出荷元名,数量,SL.倉庫Ｃ,SK.運送Ｃ,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            default:
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
*/
                $sql .= "ORDER BY RZ.棚番, 数量, SL.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;
        }

        // === SQL 格納
        $sql_one_tokki = $sql;
        $_SESSION['sql_one_option'] = $sql_one_tokki;

        dprintBR($sql);
        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }


        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);

        // dprint($sql);

        oci_execute($stid);

        $item_index = 1;
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
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou     = $row['特記事項'];
            $SH_Hinban    = $row['品番']; // === 24_0807 追加  SH.品番

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
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'tokki_zikou' => $tokki_zikou,
                'sql_one_tokki' => $sql_one_tokki,
                'four_status' => 'one_bikou_tokki',
                'item_index' => $item_index,                   // インデックス番号
                'SH_Hinban' => $SH_Hinban  // === 24_0807 追加 SH.品番
            );
            $item_index++;
        }

        oci_free_statement($stid);
        oci_close($conn);


        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }

        // =================================================
        // ==================== 複数選択   third.php => four.php  ===================
        // =================================================
    } else if ((isset($_GET['fukusuu_select']) && $_GET['fukusuu_select'] === '200')
        || isset($_SESSION['fukusuu_select']) && $_SESSION['fukusuu_select'] === '200'
        || isset($_GET['four_status']) && $_GET['four_status'] == 'multiple_sql_four'
        //        || !isset($_GET['sort_areaa']) && !isset($_GET['sort_areab'])
        //        || isset($_GET['sort_key']) && isset($_GET['sort_areac'])
        //        || isset($_GET["show_all_flg"]) && $_GET["show_all_flg"] == 2
        //        || isset($_GET["all_flg"]) && $_GET["all_flg"] == 2
    ) {


        dprint("複数選択 , デフォルト:::" . "<br>");
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];


        // 複数  運送コード + 特記・備考
        $fukusuu_unsouo_num = $_GET['fukusuu_unsouo_num'];
        $fukusuu_select_val = $_GET['fukusuu_select_val'];

        // === セッションへ格納
        if (isset($_SESSION['fukusuu_unsouo_num']) && !empty($_SESSION['fukusuu_unsouo_num'])) {
            $fukusuu_unsouo_num = $_SESSION['fukusuu_unsouo_num'];
        } else {
            $_SESSION['fukusuu_unsouo_num'] = $fukusuu_unsouo_num;
        }

        // === セッションへ格納
        if (isset($_SESSION['fukusuu_select_val']) && !empty($_SESSION['fukusuu_select_val'])) {
            $fukusuu_select_val = $_SESSION['fukusuu_select_val'];
        } else {
            $_SESSION['fukusuu_select_val'] = $fukusuu_select_val;
        }

        if (isset($_SESSION['fukusuu_select']) && !empty($_SESSION['fukusuu_select'])) {
            $fukusuu_select = $_SESSION['fukusuu_select'];
        } else {
            $_SESSION['fukusuu_select'] = $fukusuu_select;
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
        //    $arr_fukusuu_select_val[0] = str_replace('：', '', $arr_fukusuu_select_val[0]);
        $arr_fukusuu_select_val[0] = str_replace('\t', '', $arr_fukusuu_select_val[0]);
        $arr_fukusuu_select_val = array_filter($arr_fukusuu_select_val);

        // 運送コードの 先頭の - 削除
        foreach ($arr_fukusuu_select_val as &$arr_fukusuu_VAL) {
            $arr_fukusuu_VAL = preg_replace('/^-/', '', $arr_fukusuu_VAL);
        }
        unset($arr_fukusuu_VAL);

        // ========= 運送コード　複数
        $arr_fukusuu_unsouo_num = explode(',', $fukusuu_unsouo_num);
        //     $arr_fukusuu_unsouo_num[0] = str_replace('：', '', $arr_fukusuu_unsouo_num[0]);
        $arr_fukusuu_unsouo_num[0] = str_replace('\t', '', $arr_fukusuu_unsouo_num[0]);
        // 24/07/08 0:KA小矢部対応
        function valuecomp($var)
        {
            // null以外の値を返す
            return ($var <> null);
        }
        //      $arr_fukusuu_unsouo_num = array_filter($arr_fukusuu_unsouo_num);
        $arr_fukusuu_unsouo_num = array_filter($arr_fukusuu_unsouo_num, "valuecomp");

        $arr_Fku_Val = [];

        // 運送コード 運送コード + 特記・備考 の２次元配列作成
        foreach ($arr_fukusuu_select_val as $arr_val) {
            //     $arr_Fku_Val[] = explode(":", $arr_val);
            $arr_Fku_Val[] = explode("\t", $arr_val);
        }

        // ********* 可変部分の条件を生成 *********
        if (!empty($arr_Fku_Val)) {

            $conditions = [];
            foreach ($arr_Fku_Val as $arr_SQL) {
                // 可変部分の条件を生成
                $conditionSet = [];
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $conditionSet[0] = "(SJ.運送Ｃ = '{$arr_SQL[1]}')";
*/
                $conditionSet[0] = "(SK.運送Ｃ = '{$arr_SQL[1]}')";
                /* 24/07/26
                if ($arr_SQL[2] !== '-') {
                    $conditionSet[1] = "(SL.出荷元 = '{$arr_SQL[2]}')";
                } else {
                    $conditionSet[1] = "(SL.出荷元 IS NULL)";
                }
*/
                if ($arr_SQL[2] == "'") {
                    $conditionSet[1] = "SL.出荷元 = ''''";
                } else if ($arr_SQL[2] !== '-') {
                    $conditionSet[1] = "(SL.出荷元 = '{$arr_SQL[2]}')";
                } else {
                    $conditionSet[1] = "(SL.出荷元 IS NULL)";
                }

                if ($arr_SQL[3] !== '---') {
                    $conditionSet[2] = "(SK.特記事項 = '{$arr_SQL[3]}')";
                } else {
                    $conditionSet[2] = "(SK.特記事項 IS NULL)";
                }

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

        // 並替ボタン選択時 2024/06/05
        $sortKey = "";
        if (isset($_GET['sort_key'])) {
            $sortKey = $_GET['sort_key'];
        }

        // 全表示フラグ確認 2024/06/07
        if (isset($_GET['show_all_flg'])) {
            $show_all_flg = $_GET['show_all_flg'];
        }

        // === 運送コード（備考・特記）なし抽出　重複削除 *php8 修正
        if (!empty($arr_Fku_Val)) {
            $idx = 0;
            for ($i = 0; $i < count($arr_fukusuu_unsouo_num); $i++) {
                // キーが存在するかを確認
                if (isset($arr_Fku_Val[$idx][1]) && $arr_fukusuu_unsouo_num[$idx] == $arr_Fku_Val[$idx][1]) {
                    unset($arr_fukusuu_unsouo_num[$idx]);
                    break;
                }
                $idx = $idx + 1;
            }
        }


        if (empty($arr_fukusuu_unsouo_num)) {
            dprint("複数は空");
        }


        $conditions_UNSOU = [];
        // === 運送コードだけのものがあった場合
        if (!empty($arr_fukusuu_unsouo_num)) {

            $idx = 0;
            foreach ($arr_fukusuu_unsouo_num as $F_Unsou_VAL) {

                // print($F_Unsou_VAL . "<br>");

                $conditionSet_Unsou = [];
                /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
                $conditionSet_Unsou[0] = "(SJ.運送Ｃ = '{$F_Unsou_VAL}')";
*/
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
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名	
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
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
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
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";
*/
        $sql = "SELECT SK.出荷日,SL.倉庫Ｃ,SO.倉庫名,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名	
                      ,RZ.棚番
                      ,SH.梱包入数
                      ,SUM(SL.数量) AS 数量
                      ,SUM(PK.ピッキング数量) AS ピッキング数量
                      ,PK.処理Ｆ
                      ,SH.ＪＡＮ
                      ,SK.特記事項
                      ,SH.品番
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                      ,RZMF RZ
                      ,HTPK PK
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
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
                   AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";

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

        //2024/07/08 全表示 処理追加
        if (!isset($_GET["show_all_flg"])) {
            $sql .= "AND NVL(PK.処理Ｆ,0) <> 8 AND NVL(PK.処理Ｆ,0) <> 9 ";
        } else {
            $all_flg = 2;
        }

        // GROUP BY句とORDER BY句を追加
        /* ピッキングに合わせる SJ.出荷日とSJ.運送Ｃ 24/06/28
        $sql .= "GROUP BY SJ.出荷日, SL.倉庫Ｃ, SO.倉庫名, 
        SL.出荷元, SM.出荷元名, SL.商品Ｃ, SH.品名, PK.処理Ｆ, RZ.棚番, SH.梱包入数, SH.ＪＡＮ, SK.特記事項 ";
*/
        $sql .= "GROUP BY SK.出荷日, SL.倉庫Ｃ, SO.倉庫名, 
        SL.出荷元, SM.出荷元名, SL.商品Ｃ, SH.品名, PK.処理Ｆ, RZ.棚番, SH.梱包入数, SH.ＪＡＮ, SK.特記事項,SH.品番 ";
        // 2024/06/05
        switch ($sortKey) {
            case 'location_note':  //ロケ順
                $sql .= "ORDER BY RZ.棚番,SL.商品Ｃ,SL.出荷元,SK.特記事項,数量";
                break;

            case 'num_note':       //数量順
                $sql .= "ORDER BY 数量,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項 ";
                break;

            case 'tokki_note':     //特記順
                $sql .= "ORDER BY SK.特記事項,SL.出荷元,RZ.棚番,SL.商品Ｃ";
                break;

            case 'bikou_note':    //備考順
                $sql .= "ORDER BY SL.出荷元,SK.特記事項,RZ.棚番,SL.商品Ｃ";
                break;

            default:             //デフォルトはロケ順
                $sql .= "ORDER BY RZ.棚番,SL.商品Ｃ,SL.出荷元,SK.特記事項,数量";
                break;
        }

        // =================
        // === 複数SQL　取得
        // =================

        $Multiple_Sql = $sql;

        // *** sqlをセッションへ格納 ***
        $_SESSION['multiple_sql'] = $Multiple_Sql;

        //$sql_multiple_cut = getCondition_Multiple($Multiple_Sql);
        //  dprint($sql_multiple_cut);
        dprintBR("********* Multiple_Sql デフォルト **********");
        dprintBR($Multiple_Sql);
        dprintBR($show_all_flg);
        if ($show_all_flg <> "") {
            // 全数表示時 条件抜き出し
            $sql_multiple_cut = getCondition_Multiple_zen($Multiple_Sql);
            dprintBR("********* getCondition_Multiple_zen デフォルト **********");
            dprintBR($sql_multiple_cut);
        } else {
            // 通常時表示時 条件抜き出し
            $sql_multiple_cut = getCondition_Multiple($Multiple_Sql);
            dprintBR("********* getCondition_Multiple デフォルト **********");
            dprintBR($sql_multiple_cut);
        }

        $_SESSION['multiple_sql_cut'] = $sql_multiple_cut;

        // *** sqlをセッションへ格納 END ***

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
        oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
        oci_bind_by_name($stid, ":SELECT_SOUKO_02", $select_souko_code);

        dprint($sql);

        oci_execute($stid);

        $item_index = 1;
        $arr_Picking_DATA = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得
            $syuka_day = $row['出荷日'];
            $souko_code = $row['倉庫Ｃ'];
            $souko_name = $row['倉庫名'];
            //    $Unsou_code = $row['運送Ｃ'];
            //    $Unsou_name = $row['運送略称'];
            $shipping_moto = $row['出荷元'];
            $shipping_moto_name = $row['出荷元名'];
            $Shouhin_code = $row['商品Ｃ'];
            $Shouhin_name = $row['品名'];
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
            $Shouhin_num = $row['数量'];
            $Picking_num = $row['ピッキング数量'];
            $Shori_Flg = $row['処理Ｆ'];
            //24/05/24
            //          $Tokuisaki_name = $row['得意先名'];
            $shouhin_JAN    = $row['ＪＡＮ'];
            $tokki_zikou    = $row['特記事項'];
            $SH_Hinban     = $row['品番']; // === 追加 24_0807 SH.品番

            // 取得した値を配列に追加
            $arr_Picking_DATA[] = array(
                'syuka_day' => $syuka_day,                  // SK.出荷日
                'souko_code' => $souko_code,                // SK.倉庫Ｃ
                'souko_name' => $souko_name,                // SO.倉庫名
                //    'Unsou_code' => $Unsou_code,                // SJ.運送Ｃ
                //    'Unsou_name' => $Unsou_name,                // US.運送略称
                'shipping_moto' => $shipping_moto,          // SL.出荷元
                'shipping_moto_name' => $shipping_moto_name, // SM.出荷元名
                'Shouhin_code' => $Shouhin_code,            // SK.商品Ｃ
                'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'Shori_Flg' => $Shori_Flg,                  // PK.処理Ｆ
                //24/05/24
                //              'Tokuisaki_name' => $Tokuisaki_name,        // SJ.得意先名
                'shouhin_JAN' => $shouhin_JAN,               // JANコード
                'tokki_zikou' => $tokki_zikou,
                'four_status' => 'multiple_sql_four',
                //          'Multiple_Sql' => $Multiple_Sql
                'item_index' => $item_index,                   // インデックス番号
                'SH_Hinban' => $SH_Hinban                   // === 追加 24_0807 SH.品番
            );
            $item_index++;
        }

        oci_free_statement($stid);
        oci_close($conn);

        // === 倉庫名
        if (isset($_SESSION['soko_name'])) {
            $get_souko_name = $_SESSION['soko_name'];
            //    print($_SESSION['soko_name'] . "01");
        } else {
            $_SESSION['soko_name'] = $get_souko_name;
            //    print($_SESSION['soko_name'] . "02");
        }
    } else {
        //状態異常 24/06/15
        //警告表示(未設定)
        header("Location: index.php");
        exit;
    } // ================================================== END 

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


    // ======================================================================
    // ==============================作業中解除　判別処理   24_0729
    // =======================================================================

    // print("出力:::" . $_SESSION['Sagyou_now_Shouhin_Code'] . "\n");

    // ================ バインドへ入れる変数 
    $souko_code = $_SESSION['selectedSouko_sagyou']; // 倉庫コード
    $syukka_day = $_SESSION['selected_day_sagyou']; // 出荷日
    $nyuuryoku_tantou = $_SESSION['input_login_id']; // 担当者コード

    // print($Sagyou_now_Shouhin_Code);

    if (isset($_SESSION['Sagyou_now_Shouhin_Code'])) {
        $arr_tmp = explode(",", $_SESSION['Sagyou_now_Shouhin_Code']);

        //    var_dump($arr_tmp);

        // =========  作業コード分の ループを行う
        foreach ($arr_tmp as $arr_val) {
            //  print($arr_val);

            $syouhin_code = $arr_val; // 商品コード


            //  select 処理
            $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

            if (!$conn) {
                $e = oci_error();
                echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
                exit;
            }


            /*
               倉庫Ｃ => パラメーターから取得 , 出荷日 => パラメーターから取得
               処理Ｆ => 固定 , 商品Ｃ => ループの値から取得 , 入力担当 => パラメーターから取得
            */

            $sql = "SELECT * FROM HTPK WHERE 倉庫Ｃ = :souko_code AND 出荷日 = :syukka_day
            AND 処理Ｆ = 2 
            AND 商品Ｃ = :syouhin_code AND 入力担当 = :nyuuryoku_tantou";

            $stid = oci_parse($conn, $sql);

            if (!$stid) {
                $e = oci_error($conn);
                echo htmlentities(
                    $e['message'],
                    ENT_QUOTES,
                    'UTF-8'
                );
            }

            // バインド変数
            oci_bind_by_name($stid, ':souko_code', $souko_code);
            oci_bind_by_name($stid, ':syukka_day', $syukka_day);
            oci_bind_by_name($stid, ':syouhin_code', $syouhin_code);
            oci_bind_by_name($stid, ':nyuuryoku_tantou', $nyuuryoku_tantou);

            // クエリの実行
            oci_execute($stid);

            while ($row = oci_fetch_assoc($stid)) {

                $arr_Sagyou_Now_Data[] = array(
                    's_now_syori_seq' => $row['処理ＳＥＱ'],
                    's_now_denpyou_seq' => $row['伝票ＳＥＱ'],
                    's_now_denpyou_num' => $row['伝票番号'],
                    's_now_denpyou_gyou_num' => $row['伝票行番号'],
                    's_now_denpyou_eda_num' => $row['伝票行枝番'],
                    's_now_nyuuryou_tantou' => $row['入力担当'],
                    's_now_shouhinn_code' => $row['商品Ｃ'],
                    's_now_souko_code' => $row['倉庫Ｃ'],
                    's_now_unsou_code' => $row['運送Ｃ'],
                    's_now_syukka_moto' => $row['出荷元'],
                    's_now_tokki_zikou' => $row['特記事項'],
                    's_now_syukka_yotei_num' => $row['出荷予定数量'],
                    's_now_picking_num' => $row['ピッキング数量'],
                    's_now_shori_Flg' => $row['処理Ｆ'],
                );
            }

            // print_r($arr_Sagyou_Now_Data);
            $_SESSION['arr_Sagyou_Now_Data'] = $arr_Sagyou_Now_Data;

            oci_free_statement($stid);
            oci_close($conn);
        }
    } // ===================== END if

    //   var_dump($arr_Sagyou_Now_Data);
    $psition_flg = 0;
    // ========================================= 作業中　解除　削除処理 24_0801 追加
    if (isset($_SESSION['syori_seq_val_post_val'])) {
        $session_syori_seq_val_post_val = $_SESSION['syori_seq_val_post_val'];

        // print("セッション取得:::" . $session_syori_seq_val_post_val);

        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        //    $sql = "DELETE FROM HTPK WHERE 処理ＳＥＱ = :Syori_SEQ AND 入力担当 = :NyuuRyoku_Tantou AND 商品Ｃ = :Shouhin_Code";
        $sql = "DELETE FROM HTPK WHERE 処理ＳＥＱ = :Syori_SEQ AND 処理Ｆ = 2";


        $stid = oci_parse($conn, $sql);

        if (!$stid) {
            $e = oci_error($conn);
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        oci_bind_by_name($stid, ":Syori_SEQ", $session_syori_seq_val_post_val);               // 処理 SEQ
        //    oci_bind_by_name($stid, ":NyuuRyoku_Tantou", $Parame_nyuuryou_tantou);  // 入力担当
        //    oci_bind_by_name($stid, ":Shouhin_Code", $Parame_shouhinn_code);        // 商品コード

        $result = oci_execute($stid);

        if (!$result) {
            $e = oci_error($stid);
            echo json_encode(['status' => 'error', 'message' => htmlentities($e['message'], ENT_QUOTES, 'UTF-8')], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            unset($_SESSION['Sagyou_now_Shouhin_Code']);
            unset($_SESSION['syori_seq_val_post_val']);

            // リロード
            echo '<script type="text/javascript">location.reload();</script>';

            exit;
        }

        oci_free_statement($stid);
        oci_close($conn);
    } // ========================== END if

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

    <link href="./css/all.css" rel="stylesheet">

    <link rel="stylesheet" href="./css/sweetalert2.css">

    <!-- jQuery cdn -->
    <script src="./js/jquery.min.js"></script>

    <title>ピッキング対象選択</title>


</head>

<body>

    <div id="app">
        <div class="container">
            <div class="content_04">
                <div class="head_01">
                    <div class="">
                        <span class="unsou_text_box">
                            <?php echo h($get_unsou_name); ?>
                        </span>
                    </div>

                    <div class="" @click="toggleDropdown(1)">
                        <button class="dropbtn" id="order_btn" value="並替">並替</button>
                        <div class="dropdown-content" :class="{show: isOpen[1]}">
                            <button type="button" @click="handleButtonClick('location_note')">ロケ順</button>
                            <button type="button" @click="handleButtonClick('num_note')">数量順</button>
                            <button type="button" @click="handleButtonClick('tokki_note')">特記順</button>
                            <button type="button" @click="handleButtonClick('bikou_note')">備考順</button>
                        </div>
                    </div>

                    <div class="">

                        <button type="button" id="all_select_btn">
                            全表示
                        </button>

                    </div>

                </div>


                <div>
                    <input type="hidden" name="position" value="0">
                    <input type="hidden" id="selected_index" name="selected_index" value="<?php echo $selected_index; ?>">
                    <input type="hidden" id="selected_jan" name="selected_jan" value="<?php echo $selected_jan; ?>">
                </div>
            </div>

            <p id="err_JAN" style="color:black; margin:-10px 0px"></p>


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
        <?php endif; ?>

        <!-- ********* 複数処理の場合 ********* -->
        <?php if (isset($arr_Picking_DATA[0]['Multiple_Sql'])) : ?>
            <?php
            $Multiple_condition = getCondition_Multiple($arr_Picking_DATA[0]['Multiple_Sql']);
            //     dprint($Multiple_condition);
            ?>
            <input type="hidden" name="Multiple_condition" id="Multiple_condition" value="<?php print $Multiple_condition; ?>">
        <?php endif; ?>

        <!-- ==================================================== -->
        <!-- ============== テーブルレイアウト 開始 =============== -->
        <!-- ==================================================== -->
        <div id="select_view_box">
            <table border="1">
                <thead>
                    <tr>
                        <th class="location_title">ロケ</th>
                        <th class="btn_suuryou">数量</th>
                        <th class="btn_case">ケース</th>
                        <th class="btn_bara">バラ</th>
                        <!--
                        <th>品名・品番</th>
        -->
                        <th>品名・品番 <span style="display:block"><button id="toggle_all_button">JAN</button></span></th>
                        <th class="btn_tokki_bikou">特記・備考</th>
                    </tr>

                    <?php


                    // Sagyou_NOW_Flg = 2 : 残
                    // Sagyou_NOW_Flg = 1 : 選択中
                    // Sagyou_NOW_Flg = 0 : 作業前

                    foreach ($arr_Picking_DATA as $Picking_VAL) {
                        $Sagyou_NOW_Flg = 0;

                        // === 追加 24_0723 作業中 解除フラグ
                        $Sagyou_Kaizyo_Flg = 0;

                        $shouhin_name_part1 = mb_substr($Picking_VAL['Shouhin_name'], 0, 20);
                        $shouhin_name_part2 = mb_substr($Picking_VAL['Shouhin_name'], 20, null);


                        // ケース薄 計算
                        if ($Picking_VAL['Shouhin_num'] != 0 && $Picking_VAL['Konpou_num']) {
                            // ケース数
                            $Case_num_View = floor($Picking_VAL['Shouhin_num'] / $Picking_VAL['Konpou_num']);
                        } else {
                            $Case_num_View = 0;
                        }

                        // バラ数 計算
                        if ($Picking_VAL['Konpou_num'] != 0) {
                            $Bara_num_View = $Picking_VAL['Shouhin_num'] % $Picking_VAL['Konpou_num'];
                        } else {
                            $Bara_num_View = 0;
                        }


                        // =============================================
                        // === 24_0729 追加　作業中解除
                        foreach ($arr_Sagyou_Now_Data as $arr_Sagyou_Now_Val) {
                            //    print($_SESSION['input_login_id'] . "\n");

                            $login_id = $_SESSION['input_login_id'];

                            $sagyou_login_id = Zero_Padding($arr_Sagyou_Now_Val['s_now_nyuuryou_tantou']);
                            //   dprintBR($sagyou_login_id . "\n");

                            if (
                                $login_id == $sagyou_login_id
                                && $Picking_VAL['Shouhin_code'] == $arr_Sagyou_Now_Val['s_now_shouhinn_code']
                            ) {

                                // === 追加 24_0723 作業中 解除フラグ  0:解除 NG , 1:解除 OK
                                $Sagyou_Kaizyo_Flg = 1;
                                //  dprintBR("一致OK:::" . "\n");

                                // 処理ＳＥＱ
                                $Parame_syori_seq = $arr_Sagyou_Now_Val['s_now_syori_seq'];
                                // 伝票ＳＥＱ
                                $Parame_denpyou_seq = $arr_Sagyou_Now_Val['s_now_denpyou_seq'];
                                // 伝票番号
                                $Parame_denpyou_num = $arr_Sagyou_Now_Val['s_now_denpyou_num'];
                                // 商品コード
                                $Parame_shouhinn_code = $arr_Sagyou_Now_Val['s_now_shouhinn_code'];
                                // 入力担当
                                $Parame_nyuuryou_tantou = $arr_Sagyou_Now_Val['s_now_nyuuryou_tantou'];
                            }
                        }


                        // === 処理フラグ
                        $Shori_Flg = $Picking_VAL['Shori_Flg'];


                        // 完了
                        if ($Shori_Flg == 9) {
                            $Sagyou_NOW_Flg = 3;
                        }
                        // キャンセル 24/07/04
                        if ($Shori_Flg == 8) {
                            $Sagyou_NOW_Flg = 5;
                        }

                        // 作業中にする
                        if ($Shori_Flg == 2) {
                            $Sagyou_NOW_Flg = 1;
                        }

                        // ======= 24_0704 追加 JAN 切替用
                        $shouhin_JAN_TMP = $Picking_VAL['shouhin_JAN'];
                        $last_four = substr($shouhin_JAN_TMP, -4);


                        if ($Sagyou_NOW_Flg == 0) {

                            // === 運送便（単数）, 備考・特記あり
                            if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "" && $Picking_VAL['four_status'] == 'one_bikou_tokki') {
                                $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);

                                if (isset($Picking_VAL['tokki_zikou'])) {
                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code)
                                        . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                        '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                        '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&sort_key=' . $sortKey . '&now_sql=' . $encoded_sql_one_tokki . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                } else {
                                    $Picking_VAL['tokki_zikou'] = "";
                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code)
                                        . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                        '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                        '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&sort_key=' . $sortKey .  '&now_sql=' . $encoded_sql_one_tokki . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                }


                                // === 運送便（複数） , 備考・特記あり,　※備考・特記ありも複数　=> five.php から戻ってきた
                            } else if (isset($Picking_VAL['sql_multiple_tokki']) && $Picking_VAL['sql_multiple_tokki'] != "") {

                                dprint("ここ:複数");
                                $Multiple_Sql_Url = UrlEncode_Val_Check($Picking_VAL['sql_multiple_tokki']);
                                echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                    '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' .
                                    UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                    '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                    '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .  '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                    '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                    '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                    '&sort_key=' . $sortKey . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';

                                // *** 複数が最初に通る ルート ***
                                // four.php => five.php へ　運送便（複数） , 備考・特記あり,　※備考・特記ありも複数
                            } else if (isset($Picking_VAL['four_status']) && $Picking_VAL['four_status'] == "multiple_sql_four") {

                                dprint("ここ:複数 最初のルート third.php => four.php => five.php");

                                echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' .
                                    //UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&shipping_moto='
                                    UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name)
                                    . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name'])
                                    . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name'])
                                    . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num'])
                                    . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' .
                                    UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&sort_key=' . $sortKey .
                                    '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&four_five_multiple_sql=' . UrlEncode_Val_Check($sql_multiple_cut) .
                                    '&four_status=multiple_sql_four' . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                            } else {

                                // === 通常処理　特記事項 あり
                                if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] != "" || $Picking_VAL['shipping_moto'] != "") {
                                    //    dprint("koko,// === 通常処理　特記事項 あり");
                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                        '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                                        '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                        '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                        '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                        '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&four_status=default_root' .  '&sort_key=' . $sortKey . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                } else if ($Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] == "" && $Picking_VAL['shipping_moto'] == "") {
                                    // dprint("koko,// === 通常処理　特記事項 あり else");
                                    // === 通常処理　特記事項 あり
                                    echo '<tr data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                        '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                                        '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                        '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                        '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                        '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&four_status=default_root' . '&status_sub=default' . '&sort_key=' . $sortKey . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                }
                            }


                            echo '<td class="location_val"><span class=tana_num>' . $Picking_VAL['Tana_num'] . '</span></td>';
                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                //echo $Picking_VAL['Shouhin_name'] . "<br>";
                                /*
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                                */

                                // === 24_0808 品番検索　追加  ===
                                // class="sh_hinban_val"  $Picking_VAL['SH_Hinban']
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    '<input type="hidden" class="sh_hinban_val" value="' . $Picking_VAL['SH_Hinban'] . '">' .
                                    "</td>";
                            } else {
                                /*
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                                */

                                // === 24_0808 品番検索　追加  ===
                                // class="sh_hinban_val"  $Picking_VAL['SH_Hinban']
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    '<input type="hidden" class="sh_hinban_val" value="' . $Picking_VAL['SH_Hinban'] . '">' .
                                    "</td>";
                            }

                            // === 特記がある
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {

                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {

                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }

                            /* if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>'.
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {    //2024/06/25 修正
                                    // === 特記がない
                                    echo '<td>'. $Picking_VAL['shipping_moto_name'] . '</td>';
                                } */
                            /* echo '<td><span class="toki_list">' . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>'; */


                            echo '</tr>';

                            // ===================================
                            //============== 作業中 ==============
                            // ===================================
                        } else if ($Sagyou_NOW_Flg == 1 && $Sagyou_Kaizyo_Flg == 0) {
                            echo '<tr style="background: yellow;" id="sagyou_now" class="sagyou_now">';
                            echo '<td class="location_val"><span id="sagyou_now_text">作業中</span>' .
                                //'<span class="sagyou_img_box" style="display: block;margin: 10px 0 0 0;">' . $Picking_VAL['Tana_num'] . '</span></td>';
                                '<span class="sagyou_img_box" style="margin: 10px 0 0 0; font-weight: 600">' . $Picking_VAL['Tana_num'] . '</span></td>';

                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            }
                            /* echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>"; */

                            // === 特記
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }

                            /* } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } */

                            echo '</tr>';


                            // ==========================================================
                            // =======================  24_0729 追加 作業中 解除
                            // ==========================================================
                        } else if ($Sagyou_NOW_Flg == 1 && $Sagyou_Kaizyo_Flg == 1) {

                            // === 運送便（単数）, 備考・特記あり
                            if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "" && $Picking_VAL['four_status'] == 'one_bikou_tokki') {
                                $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);

                                if (isset($Picking_VAL['tokki_zikou'])) {
                                    echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&sort_key=' . $sortKey . '&now_sql=' . $encoded_sql_one_tokki .
                                        '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&four_five_multiple_sql=' . UrlEncode_Val_Check($sql_multiple_cut) . '&four_status=multiple_sql_four'
                                        . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                        . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                        . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                } else {
                                    $Picking_VAL['tokki_zikou'] = "";
                                    echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&sort_key=' . $sortKey .  '&now_sql=' . $encoded_sql_one_tokki .
                                        '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&four_five_multiple_sql=' . UrlEncode_Val_Check($sql_multiple_cut) . '&four_status=multiple_sql_four'
                                        . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                        . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                        . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                }


                                // === 運送便（複数） , 備考・特記あり,　※備考・特記ありも複数　=> five.php から戻ってきた
                            } else if (isset($Picking_VAL['sql_multiple_tokki']) && $Picking_VAL['sql_multiple_tokki'] != "") {

                                dprint("ここ:複数");
                                $Multiple_Sql_Url = UrlEncode_Val_Check($Picking_VAL['sql_multiple_tokki']);
                                echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                    '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .  '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou'])
                                    . '&sort_key=' . $sortKey .
                                    UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&sort_key=' . $sortKey .
                                    '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&four_five_multiple_sql=' . UrlEncode_Val_Check($sql_multiple_cut) . '&four_status=multiple_sql_four'
                                    . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                    . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                    . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';

                                // *** 複数が最初に通る ルート ***
                                // four.php => five.php へ　運送便（複数） , 備考・特記あり,　※備考・特記ありも複数
                            } else if (isset($Picking_VAL['four_status']) && $Picking_VAL['four_status'] == "multiple_sql_four") {

                                dprint("ここ:複数 最初のルート third.php => four.php => five.php");

                                echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' .
                                    //UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&shipping_moto='
                                    UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name)
                                    . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name'])
                                    . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name'])
                                    . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num'])
                                    . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' .
                                    UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&sort_key=' . $sortKey .
                                    '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&four_five_multiple_sql=' . UrlEncode_Val_Check($sql_multiple_cut) . '&four_status=multiple_sql_four'
                                    . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                    . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                    . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                            } else {

                                // === 通常処理　特記事項 あり
                                if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] != "" || $Picking_VAL['shipping_moto'] != "") {
                                    //    dprint("koko,// === 通常処理　特記事項 あり");
                                    echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name'])
                                        . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&four_status=default_root'
                                        .  '&sort_key=' . $sortKey . '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                        . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                        . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                } else if ($Picking_VAL['four_status'] == 'default_root' && $Picking_VAL['tokki_zikou'] == "" && $Picking_VAL['shipping_moto'] == "") {
                                    // dprint("koko,// === 通常処理　特記事項 あり else");
                                    // === 通常処理　特記事項 あり
                                    echo '<tr style="background: yellow;" class="sagyou_now_text_no_c" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($Picking_VAL['Unsou_code']) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name'])
                                        . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($Picking_VAL['shouhin_JAN']) . '&tokki_zikou=' .  UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) . '&four_status=default_root'
                                        . '&status_sub=default' . '&sort_key=' . $sortKey . '&index=' . UrlEncode_Val_Check($Picking_VAL['item_index']) . '&s_now_syori_seq=' . UrlEncode_Val_Check($Parame_syori_seq) . '&s_now_denpyou_seq=' . UrlEncode_Val_Check($Parame_denpyou_seq) . '&s_now_denpyou_num=' . UrlEncode_Val_Check($Parame_denpyou_num)
                                        . '&s_now_shouhinn_code=' . UrlEncode_Val_Check($Parame_shouhinn_code) . '&s_now_nyuuryou_tantou=' . UrlEncode_Val_Check($Parame_nyuuryou_tantou)
                                        . '&scan_shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['SH_Hinban']) . '">';
                                }
                            }


                            echo '<td class="location_val"><span id="sagyou_now_text_no">解除可</span>' .
                                //'<span class="sagyou_img_box" style="display: block;margin: 10px 0 0 0;">' . $Picking_VAL['Tana_num'] . '</span></td>';
                                '<span class="sagyou_img_box" style="margin: 10px 0 0 0; font-weight: 600">' . $Picking_VAL['Tana_num'] . '</span></td>';
                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                //echo $Picking_VAL['Shouhin_name'] . "<br>";
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    '<input type="hidden" class="syori_seq_val" value="' . $Parame_syori_seq . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    '<input type="hidden" class="syori_seq_val" value="' . $Parame_syori_seq . '">' .
                                    "</td>";
                            }

                            // === 特記がある
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {

                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';

                                // dprintBR("**ワン**");
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';

                                // dprintBR("**ツー**");
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {

                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                                // dprintBR("**スリー**");
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';

                                // dprintBR("**フォー**");
                            }


                            echo '</tr>';
                        } else if ($Sagyou_NOW_Flg == 2) {

                            if (isset($Picking_VAL['sql_one_tokki']) && $Picking_VAL['sql_one_tokki'] != "") {

                                if (isset($Picking_VAL['Denpyou_SEQ']) && $Picking_VAL['Denpyou_SEQ'] != "") {
                                    $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);
                                    echo '<tr style="background: green;" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) .
                                        '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                        '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) .
                                        '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                                        '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                        '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                        '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                        '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                        '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) .
                                        '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                        '&case_num=' . UrlEncode_Val_Check($Case_num_View) .
                                        '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) .
                                        '&tokki_zikou=' . UrlEncode_Val_Check($Picking_VAL['tokki_zikou']) .
                                        '&sort_key=' . UrlEncode_Val_Check($sortKey) .
                                        '&Denpyou_SEQ=' . UrlEncode_Val_Check($Picking_VAL['Denpyou_SEQ']) . // 伝票 SEQ
                                        '&now_sql=' . $encoded_sql_one_tokki . '">';
                                } else {

                                    $encoded_sql_one_tokki = UrlEncode_Val_Check($sql_one_tokki);
                                    echo '<tr style="background: green;" data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) .
                                        '&souko_code=' . UrlEncode_Val_Check($select_souko_code) .
                                        '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) .
                                        '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                                        '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) .
                                        '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) .
                                        '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) .
                                        '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) .
                                        '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) .
                                        '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) .
                                        '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) .
                                        '&case_num=' . UrlEncode_Val_Check($Case_num_View) .
                                        '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) .
                                        '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) .
                                        '&sort_key=' . UrlEncode_Val_Check($sortKey) .
                                        '&now_sql=' . $encoded_sql_one_tokki . '">';
                                }
                            } else {

                                echo '<tr style="background: green"; data-href="./five.php?select_day=' . UrlEncode_Val_Check($select_day) . '&souko_code=' . UrlEncode_Val_Check($select_souko_code) . '&unsou_code=' . UrlEncode_Val_Check($select_unsou_code) . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&shipping_moto=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto']) . '&shipping_moto_name=' . UrlEncode_Val_Check($Picking_VAL['shipping_moto_name']) . '&Shouhin_code=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_code']) . '&Shouhin_name=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_name']) . '&Shouhin_num=' . UrlEncode_Val_Check($Picking_VAL['Shouhin_num']) . '&Tokuisaki=' . UrlEncode_Val_Check($Tokuisaki_name) . '&tana_num=' . UrlEncode_Val_Check($Picking_VAL['Tana_num']) . '&case_num=' . UrlEncode_Val_Check($Case_num_View) . '&bara_num=' . UrlEncode_Val_Check($Bara_num_View) . '&shouhin_jan=' . UrlEncode_Val_Check($shouhin_JAN) . '&sort_key=' . UrlEncode_Val_Check($sortKey) . '">';
                            }

                            echo '<td class="location_val"><span id="sagyou_now_text">残<i class="fa-regular fa-circle-stop"></i></span>' . $Picking_VAL['Tana_num'] . '</td>';

                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (isset($shouhin_name_part2)) {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            }

                            // === 特記
                            // === 特記がある
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }
                            /* if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } */

                            echo '</tr>';
                        } else if ($Sagyou_NOW_Flg == 3 || $Shori_Flg == 9) {

                            // === 全数表示
                            $row_class = 'picking-row1';
                            // 2024/06/07 echoコメント
                            if ($Picking_VAL['now_sql'] != "") {
                                $encoded_sql_one_tokki = UrlEncode_Val_Check($Picking_VAL['now_sql']);
                                //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num']  . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                            } else {

                                //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                            }

                            //echo '<tr style="background: #99CCFF;">';
                            echo '<tr style="background: #99CCFF;" id="sagyou_ok" class="sagyou_ok">';
                            echo '<td class="location_val"><span id="sagyou_now_text_ok" style="font-weight:bold;">作業完了</span>' .
                                '<span class="tana_num">' . $Picking_VAL['Tana_num'] . '</span>' . '</td>';
                            //echo '<td><span id="sagyou_now_text_ok" style="display:block;font-weight:bold;">作業完了</span>' . $Picking_VAL['Tana_num'] . '</td>';

                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            }
                            /* echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>"; */

                            // === 特記がある
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }

                            /* echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>'; */

                            echo '</tr>';
                        } else if ($Sagyou_NOW_Flg == 4) {
                            // 「確定」
                            $row_class = 'picking-row2';
                            echo '<tr class="' . $row_class . '" data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $get_unsou_name . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&sort_key=' . $sortKey . '">';
                            echo '<td class="location_val"><span id="sagyou_now_text_ok">作業完了<i class="fa-regular fa-circle-stop"></i><br></span>' . $Picking_VAL['Tana_num'] . '</td>';
                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' . $Picking_VAL['Shouhin_num'] . "</td>";
                            echo '<td>' .  $Case_num_View . '</td>';
                            echo '<td>' . $Bara_num_View . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            }
                            /* echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>"; */

                            // === 特記
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                echo '<td class="tokkibikou_cell><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell>' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {
                                echo '<td class="tokkibikou_cell>' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }

                            /* if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                    echo '<td><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                        '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } else {
                                    echo '<td><span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                                } */

                            echo '</tr>';
                        } else if ($Sagyou_NOW_Flg == 5) {
                            // === 全数表示
                            $row_class = 'picking-row3';
                            // 2024/06/07 echoコメント
                            if ($Picking_VAL['now_sql'] != "") {
                                $encoded_sql_one_tokki = UrlEncode_Val_Check($Picking_VAL['now_sql']);
                                //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num']  . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                            } else {

                                //    echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '&tana_num=' . $Picking_VAL['Tana_num'] . '&case_num=' . $Case_num_View . '&bara_num=' . $Bara_num_View . '&shouhin_jan=' . $shouhin_JAN . '&now_sql=' . $encoded_sql_one_tokki . '">';
                            }

                            //echo '<tr style="background: #99CCFF;">';
                            echo '<tr style="background: #FE7F2D;" id="sagyou_cancel" class="sagyou_cancel">';
                            echo '<td class="location_val"><span id="sagyou_cancel_text" style="font-weight:bold;">キャンセル</span>' .
                                '<span class="tana_num">' . $Picking_VAL['Tana_num'] . '</span>' . '</td>';
                            //echo '<td><span id="sagyou_now_text_ok" style="display:block;font-weight:bold;">作業完了</span>' . $Picking_VAL['Tana_num'] . '</td>';

                            echo '<td id="shouhin_num_box" class="shouhin_num_box">' .
                                '<span class="Font_Bold_default_root">' . $Picking_VAL['Shouhin_num'] . '</span>' . "</td>";
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Case_num_View . '</span>' . '</td>';
                            echo '<td>' . '<span class="Font_Bold_default_root">' . $Bara_num_View . '</span>' . '</td>';

                            if (!$shouhin_name_part2 == null) {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            } else {
                                echo '<td>' . '<span class="shouhin_name">' . $shouhin_name_part1 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>";
                            }
                            /* echo '<td>' . $shouhin_name_part1 . '<br />' .
                                    '<span class="Shouhin_name_default_root">' . $shouhin_name_part2 . '</span>' .
                                    '<input type="hidden" class="shouhin_JAN" value="' . $Picking_VAL['shouhin_JAN'] . '">' .
                                    '<input type="hidden" class="Shouhin_code_val" value="' . $Picking_VAL['Shouhin_code'] . '">' .
                                    "</td>"; */

                            // === 特記がある
                            if ((isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") && (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "")) {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            } else if (isset($Picking_VAL['tokki_zikou']) && $Picking_VAL['tokki_zikou'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['tokki_zikou'] . '</td>';
                            } else if (isset($Picking_VAL['shipping_moto']) && $Picking_VAL['shipping_moto'] != "") {
                                echo '<td class="tokkibikou_cell">' . $Picking_VAL['shipping_moto_name'] . '</td>';
                            } else {
                                echo '<td class="tokkibikou_cell"><span class="toki_list">' . $Picking_VAL['tokki_zikou'] . '</span>' .
                                    '<span class="bikou_list">' . $Picking_VAL['shipping_moto_name'] . '</span></td>';
                            }
                            echo '</tr>';
                        }
                    }


                    ?>

                </thead>

            </table>


        </div> <!-- head_02 -->

    </div> <!-- ======== END container ========= -->

    <!-- 全数完了　「戻り」 five.php => four.php -->
    <?php

    /*
        if (isset($_SESSION['one_now_sql_zensuu'])) {
            echo '<input type="hidden" name="one_now_sql_zensuu" id="one_now_sql_zensuu" value="' . h($_SESSION['one_now_sql_zensuu']) . '">';
        }
        */

    /*
        if (isset($_GET['selectedToki_Code']) && $_GET['selectedToki_Code'] != "") {

        }
        */

    ?>



    <!-- フッターメニュー -->
    <footer class="footer-menu">

        <div class="cp_iptxt_03">
            <label class="ef_03">

                <!--
                <input type="number" id="get_JAN" name="get_JAN" placeholder="Scan JAN">
                -->

                <!--　========= 追加 ========= 商品コード　検索用に変更 -->
                <input type="text" id="get_JAN" name="get_JAN" placeholder="Scan JAN">
            </label>
        </div>

        <ul>
            <?php $back_flg = 1; ?>
            <?php $url = "./third.php?selectedSouko=" . UrlEncode_Val_Check($select_souko_code) . "&selected_day=" . UrlEncode_Val_Check($select_day) . "&souko_name=" . UrlEncode_Val_Check($get_souko_name) . "&back_flg=" . $back_flg; ?>
            <li><a href="<?php echo $url; ?>">戻る</a></li>
            <li><a href="" id="Kousin_Btn">更新</a></li>
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

                    var selectedDay = '<?php echo $select_day; ?>';
                    var select_souko_code = '<?php echo $select_souko_code; ?>';
                    var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                    var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';
                    // 2024/06/07 全表示後の並替用
                    var show_all_flg = "<?php echo isset($show_all_flg) ? $show_all_flg : ''; ?>";
                    var all_flg = "<?php echo isset($all_flg) ? $all_flg : ''; ?>";

                    // 複数選択 2024/06/07
                    var fukusuu_unsouo_num = "<?php echo isset($fukusuu_unsouo_num) ? $fukusuu_unsouo_num : ''; ?>";
                    // 2024/06/12
                    var fukusuu_select = "<?php echo isset($fukusuu_select) ? $fukusuu_select : ''; ?>";
                    var fukusuu_select_val = "<?php echo isset($fukusuu_select_val) ? $fukusuu_select_val : ''; ?>";
                    // 2024/06/12
                    var select_toki_code = "<?php echo isset($selectedToki_Code) ? $selectedToki_Code : ''; ?>";

                    if (show_all_flg != "") {
                        // 選択条件分岐 2024/06/07 修正
                        if (fukusuu_select != "") {
                            var sortarea = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&sort_key=' + this.selectValue + '&sort_areac=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        } else if (select_toki_code != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areab=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        } else {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areaa=' + sort_area + '&show_all_flg=' + show_all_flg + '&all_flg=' + all_flg;
                        }


                    } else {
                        // 選択条件分岐 2024/06/07 修正
                        if (fukusuu_select != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&sort_key=' + this.selectValue + '&sort_areac=' + sort_area;
                        } else if (select_toki_code != "") {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areab=' + sort_area;
                        } else {
                            var sort_area = 1;
                            var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + this.selectValue + '&sort_areaa=' + sort_area;
                        }
                    }

                    window.location.href = url;

                }
            },

        });
    </script>

    <script>
        $(document).ready(function() {

            // === 追加 24_0726 作業中　解除用 ajax処理用 リロード処理 
            if (!window.location.search.includes('reloaded=true')) {
                window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'reloaded=true';
            }
            //初期状態でロケを消す 24_0805
            $('.location_title').hide();
            $('.location_val').hide();

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

            // ========================================================
            // ============== 入力された　JAN のイベント
            // ========================================================
            $('#get_JAN').change(function() {

                Jan_Flg = 0;

                var input_JAN = $('#get_JAN').val();

                var convertedValue_JAN = convertToHalfWidth(input_JAN);
                $(this).val(convertedValue_JAN);

                var parentElement = ""; // 親要素取得用
                var Hit_shouhin_code = ""; // === Janがヒットした、商品コード格納
                var dataHref = "";

                var trHref = "";
                var dataHrefs = [];

                // ==================== JAN コード照合 ループ =======================
                $(".shouhin_JAN").each(function() {

                    console.log("item:::" + $(this).val() + "\n");
                    var shouhin_JAN = $(this).val();

                    // ========================== 24_0808 追加
                    // shouhin_JAN 数値 変換
                    var shouhin_JAN = parseInt($(this).val(), 10);
                    // #get_JANの値 数値 変換

                    var get_JAN_val = parseInt($('#get_JAN').val(), 10);
                    var get_JAN_val_shouhin = $('#get_JAN').val().trim().replace(/[\s\u3000]/g, '');

                    console.log("get_JAN_val 値:::" + get_JAN_val);

                    // === SH.品番 取得 24_-0808
                    var sh_hinban_val = $(this).closest('tr').find('.sh_hinban_val').val().trim();
                    console.log("sh_hinban_val 値:::" + sh_hinban_val);

                    // ===  ******** JAN 照合  ********* 
                    // if (shouhin_JAN === $('#get_JAN').val()) {

                    // 追加 24_0808
                    if (shouhin_JAN === get_JAN_val || sh_hinban_val === get_JAN_val_shouhin) {
                        parentElement = $(this).closest('tr');

                        console.log("JAM照合 OK:::");

                        // === 作業中だった場合
                        if ($(this).closest('tr').hasClass('sagyou_now')) {
                            Jan_Flg = 2;
                            console.log("作業中:::");
                            return false;
                        }

                        Jan_Flg = 1;
                        //  console.log("shouhin_JAN HIT:::値:::" + shouhin_JAN);

                        // === 商品コード取得
                        Hit_shouhin_code = parentElement.find('.Shouhin_code_val').val();
                        //  console.log("Hit_shouhin_code_Val:::値:::" + Hit_shouhin_code);

                        dataHref = parentElement.data('href');

                        // 0 番目に挿入
                        dataHrefs.push(dataHref);
                        console.log("data-href スキャン:::" + dataHref);

                        // === ループ終了 ===
                        return false;
                    } else {
                        Jan_Flg = 0;

                    }

                });
                // ==================== JAN コード照合 ループ END =======================

                // ================== 商品コード判定 =================
                $('.shouhin_name').each(function() {
                    var $span = $(this);

                    // 商品コードを回す
                    var loop_shouhin_code = $span.closest('td').find('.Shouhin_code_val').val();
                    console.log("loop_shouhin_code:::値:::" + loop_shouhin_code);

                    // ****** 商品コードを取得 ******
                    if (loop_shouhin_code == Hit_shouhin_code) {
                        console.log("loop_shouhin_code:::値GET:::" + loop_shouhin_code);
                        var parentElement_tr = $span.closest('tr');
                        var trHref_ff = parentElement_tr.data('href');

                        // 重複削除
                        if (trHref_ff && !dataHrefs.includes(trHref_ff)) {
                            // === データ挿入
                            // five.php で　「カウント」　+1 用に　&scan_b=bar_san　が必要
                            dataHrefs.push(trHref_ff);
                        }

                        console.log("配列 dataHrefs :::" + dataHrefs);
                        console.log("配列 dataHrefs 長さ :::" + dataHrefs.length);
                    }

                });

                // === 作業中　チェック
                if (Jan_Flg != 2) {

                    if (dataHref.length > 0) {
                        console.log("配列 dataHrefs ::: ループ抜け値 :::" + dataHrefs);

                        // === data-hrer をセッションストレージへ保存
                        sessionStorage.setItem('dataHrefs', dataHrefs.join(','));
                        console.log("保存されたdata-hrefs:***【scan-jan 用】*** " + sessionStorage.getItem('dataHrefs'));

                        // hrefの値をURLとしてページを遷移
                        // five.php で　「カウント」　+1 用に　&scan_b=bar_san　が必要
                        window.location.href = dataHrefs[0] + "&scan_b=bar_san";

                    } else {
                        Jan_Flg = 0;
                    }

                } else {
                    Jan_Flg = 2;
                }

                // デバッグ用
                return false;

                // ================================================
                // ===========================　フラグ判定チェック
                // ================================================
                // JAN エラーメッセージ
                if (Jan_Flg === 0) {
                    // ========== 一致する JANの商品がないエラー
                    Swal.fire({
                        position: "center",
                        title: "一致する商品がありません。",
                        text: "JAN：" + $('#get_JAN').val()
                    });
                    $('#get_JAN').val("");

                    return false;

                } else if (Jan_Flg === 1) {

                    $('#get_JAN').val("");
                    // 2024/07/04 追加
                    var position = $(window).scrollTop();
                    localStorage.setItem('scrollPosition', position);
                    //$('#get_JAN').focus();
                } else if (Jan_Flg === 2) {
                    // ========== 作業中の警告
                    Swal.fire({
                        position: "center",
                        title: "対象の商品は作業完了しています。",
                        text: "JAN：" + $('#get_JAN').val()
                    });
                    $('#get_JAN').val("");

                } else {
                    // ======== とりあえずの分岐
                    Swal.fire({
                        position: "center",
                        title: "対象の商品は作業中です。",
                        text: "JAN：" + $('#get_JAN').val()
                    });
                    $('#get_JAN').val("");
                }

            });

            // ========================================================
            // ============== 入力された　JAN のイベント  END
            // ========================================================


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
                // 2024/07/04
                var sort_key = "<?php echo isset($sortKey) ? $sortKey : ''; ?>";
                // 複数選択 2024/06/07
                var fukusuu_unsouo_num = "<?php echo isset($fukusuu_unsouo_num) ? $fukusuu_unsouo_num : ''; ?>";
                // 2024/06/12
                var fukusuu_select = "<?php echo isset($fukusuu_select) ? $fukusuu_select : ''; ?>";
                var fukusuu_select_val = "<?php echo isset($fukusuu_select_val) ? $fukusuu_select_val : ''; ?>";
                // 2024/06/12
                var select_toki_code = "<?php echo isset($selectedToki_Code) ? $selectedToki_Code : ''; ?>";


                // 選択条件分岐 2024/06/07
                if (fukusuu_select != "") {
                    var show_all_flg = 2;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_unsouo_num + '&fukusuu_select=' + fukusuu_select + '&fukusuu_select_val=' + fukusuu_select_val + '&sort_key=' + sort_key + '&show_all_flg=' + show_all_flg;
                } else if (select_toki_code != "") {
                    var show_all_flg = 1;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&selectedToki_Code=' + select_toki_code + '&sort_key=' + sort_key + '&show_all_flg=' + show_all_flg;
                } else {
                    var show_all_flg = 0;
                    var url = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&sort_key=' + sort_key + '&show_all_flg=' + show_all_flg;
                }

                window.location.href = url;

            });


        });
    </script>

    <script>
        // jQueryがドキュメントを読み込んだ後に以下の処理を実行
        $('tr[data-href]').click(function() {
            // クリックされた<tr>要素のdata-href属性の値を取得
            var href = $(this).data('href');

            console.log("リンク値:::" + href);
            var position = $(window).scrollTop();
            console.log("位置情報取得:" + position);

            localStorage.setItem('scrollPosition', position);

            // ==================================================
            // ************************** 連続処理　ロジック  追加 24_0705
            // ==================================================
            // クリックされた、JANコード
            var clickedJAN = $(this).find('.shouhin_JAN').val();

            // *** 商品コード or 商品名 ※ ***
            var clicked_Shouhin_Name = $(this).find('.shouhin_name').text().trim();
            //  console.log("clicked_Shouhin_Name:::値:::" + clicked_Shouhin_Name);

            // 商品コード　押されたもの
            var Shouhin_code_val = $(this).find('.Shouhin_code_val').val();
            console.log("Shouhin_code_val:::タップ値:::" + Shouhin_code_val);

            var dataHrefs = [];
            // クリックした値 0番目に追加
            dataHrefs.push(href);

            $('.shouhin_name').each(function() {
                var $span = $(this);

                // === 商品コード or 商品名取得
                var shouhin_Name = $span.text().trim();
                console.log("shouhin_Name:::値:::" + shouhin_Name);

                var shouhin_JAN = $span.closest('td').find('.shouhin_JAN').val();
                var shouhin_code = $span.closest('td').find('.Shouhin_code_val').val();
                console.log("shouhin_code:::値:::" + shouhin_code);

                // ========== 24_0806 連続読み取り用
                var $tr = $span.closest('tr');
                if ($tr.hasClass('sagyou_now_text_no_c')) {

                    console.log("解除クラスあり");
                    return;
                }

                // ＊＊＊　商品JAN と、商品コードでの比較 if 文　＊＊＊
                if (Shouhin_code_val === shouhin_code) {
                    // if (shouhin_JAN === clickedJAN && shouhin_Name === clicked_Shouhin_Name) {
                    // if (shouhin_JAN === clickedJAN) {
                    // if (shouhin_Name === clicked_Shouhin_Name) {

                    // === 連続読み取りの、値を格納
                    var trHref = $(this).closest('tr').data('href');

                    // 重複削除
                    if (trHref && !dataHrefs.includes(trHref)) {
                        dataHrefs.push(trHref);
                    }

                    console.log("解除クラスなし");
                }

            });

            // === data-hrer をセッションストレージへ保存
            sessionStorage.setItem('dataHrefs', dataHrefs.join(','));

            // console.log 確認用 下記の return false
            //  return false;

            // ==================================================
            // ************************** 追加  連続処理　ロジック 24_0705  END
            // ==================================================

            // ====================================================
            // === 作業中　解除　ロジック  24_0801 追加
            // ====================================================
            if ($(this).hasClass('sagyou_now_text_no_c')) {

                var syori_seq_val = $(this).find('.syori_seq_val').val();

                $.ajax({
                    url: '<?php echo $_SERVER["PHP_SELF"]; ?>',
                    type: 'POST',
                    data: {
                        syori_seq_val_post: syori_seq_val
                    },
                    dataType: 'json',
                    success: function(response) {

                        console.log('ajax OK 02:', response);

                        if (response.status === 'success') {
                            Swal.fire({
                                position: "center",
                                title: "作業中 解除完了",
                                text: "",
                                icon: "success",
                                confirmButtonText: "OK"
                            }).then((result) => {
                                if (result.isConfirmed) {

                                    // OKボタンが押されたときにページをリロードする
                                    location.reload();

                                }
                            });


                        } else {
                            // エラーメッセージを表示
                            alert('エラーが発生しました 02: ' + response.message);
                        }

                        handleAjaxResponse(response);
                    },
                    error: function(xhr, status, error) {
                        console.log('エラー ここ 02:', error);
                        console.log('レスポンス 02:', xhr.responseText);
                    }
                });

                return false;
            }
            //   return false;
            // hrefの値をURLとしてページを遷移
            window.location.href = href;
        });


        // === 変更前 24_0802 fiveから戻ってきた時の、カーソル位置 移動
        /*
        // HTMLドキュメントのすべてのコンテンツが読み込まれた後に発生するイベント	2024/07/01
        document.addEventListener('DOMContentLoaded', function() {
            var selectedIndexElement = document.getElementById('selected_index');
            var selectedIndexValue = selectedIndexElement.value;

            var scrollPosition = localStorage.getItem('scrollPosition');

            if (selectedIndexValue != null) {

                window.scrollTo(0, parseInt(scrollPosition, 10));
                localStorage.clear();

            }
        });
        */

        // ======================================== 変更後 24_0802 fiveから戻ってきた時の、カーソル位置 移動
        function restoreScrollPosition() {
            var selectedIndexElement = document.getElementById('selected_index');
            var selectedIndexValue = selectedIndexElement ? selectedIndexElement.value : null;
            var scrollPosition = localStorage.getItem('scrollPosition');

            /*
            if (selectedIndexValue != null && scrollPosition != null) {
                window.scrollTo(0, parseInt(scrollPosition, 10));
                localStorage.clear();
            }
            */

            console.log("function:::実行 OK restoreScrollPosition ****** 01");

            if (selectedIndexValue != null && scrollPosition != null) {
                console.log("function:::実行 OK restoreScrollPosition ****** 02");
                setTimeout(function() {
                    window.scrollTo(0, parseInt(scrollPosition, 10));
                    localStorage.clear();
                }, 500);
            } else {
                console.log("function:::実行 OK restoreScrollPosition ****** 9999");
            }

        }

        restoreScrollPosition();


        // ===================== 24_0724 追加 JAN表記で five.php へいって戻ってきたら、JAN表記にする ===================

        // === 追加 24_0704  JANコード　下四桁　切り替え
        // var isOriginal = true; // 初期状態を元の名前に設定
        var isOriginal = sessionStorage.getItem('isOriginal') === null ? true : (sessionStorage.getItem('isOriginal') === 'true');

        if (isOriginal == false) {
            $('.shouhin_name').each(function() {
                var $span = $(this);
                var shouhin_JAN = $span.closest('td').find('.shouhin_JAN').val();
                var last_four = shouhin_JAN.slice(-4);

                if ($span.data('original-name') === undefined) {
                    $span.data('original-name', $span.text());
                }

                if (isOriginal) {
                    // ****** JAN ****** 
                    // フラグをセッションストレージへ保存
                    sessionStorage.setItem('isOriginal', isOriginal);
                    // 元の名前に戻す
                    $span.text($span.data('original-name'));
                } else {
                    // ****** JAN ****** 
                    // フラグをセッションストレージへ保存
                    sessionStorage.setItem('isOriginal', isOriginal);
                    $span.text(last_four);
                }
            });
        }

        // ================= JAN 表示切替 処理 =====================
        $('#toggle_all_button').on('click', function() {

            isOriginal = !isOriginal; // 状態を反転

            $('.shouhin_name').each(function() {
                var $span = $(this);
                var shouhin_JAN = $span.closest('td').find('.shouhin_JAN').val();
                var last_four = shouhin_JAN.slice(-4);

                if ($span.data('original-name') === undefined) {
                    $span.data('original-name', $span.text());
                }

                if (isOriginal) {
                    // ****** JAN ****** 
                    // フラグをセッションストレージへ保存
                    sessionStorage.setItem('isOriginal', isOriginal);
                    // 元の名前に戻す
                    $span.text($span.data('original-name'));

                    // フォーカスを合わせる
                    $('#get_JAN').focus();

                    $("#get_JAN").attr('readonly', true);

                    setTimeout(function() {
                        $("#get_JAN").removeAttr('readonly'); // readonlyを削除
                    }, 100);

                } else {
                    // ****** JAN ****** 
                    // フラグをセッションストレージへ保存
                    sessionStorage.setItem('isOriginal', isOriginal);
                    $span.text(last_four);

                    // フォーカスを合わせる
                    $('#get_JAN').focus();

                    $("#get_JAN").attr('readonly', true);

                    setTimeout(function() {
                        $("#get_JAN").removeAttr('readonly'); // readonlyを削除
                    }, 100);
                }
            });
        });


        // ============================================================================
        // =============================================== 追加 24_0723 作業中 解除 処理
        // ============================================================================

        var arr_Sagyou_Tyuu = [];
        // ======================== 追加 テスト 24_0723
        $('tr').each(function() {
            //24_0806 更新時にラベルが変更しない問題対応
            //if ($(this).hasClass('sagyou_now')) {
            if (($(this).hasClass('sagyou_now')) || ($(this).hasClass('sagyou_now_text_no_c'))) {
                var shouhin_name_aj = $(this).find('.shouhin_name').text();
                var shouhin_code_val_aj = $(this).find('.Shouhin_code_val').val();

                shouhin_name_aj = shouhin_name_aj.trim();
                shouhin_code_val_aj = shouhin_code_val_aj.trim();

                console.log('作業中:::商品名: ' + shouhin_name_aj);
                console.log('作業中:::商品コード: ' + shouhin_code_val_aj);

                // 配列へ格納
                arr_Sagyou_Tyuu.push({
                    shouhin_name_aj: shouhin_name_aj,
                    shouhin_code_val_aj: shouhin_code_val_aj
                });
            }
        });

        $.ajax({
            url: '<?php echo $_SERVER["PHP_SELF"]; ?>',
            type: 'POST',
            data: {
                arr_Sagyou_Tyuu_data: arr_Sagyou_Tyuu
            },
            dataType: 'json',
            success: function(response) {
                console.log('ajax OK:', response);

                handleAjaxResponse(response);
            },
            error: function(xhr, status, error) {
                console.log('エラー ここ:', error);
                console.log('レスポンス:', xhr.responseText);
            }
        });


        function handleAjaxResponse(data) {
            console.log('Handling AJAX response:', data);
            // response を外で使う
        }

        // ==============================================
        // === 追加 24_0801 ロケーションを非表示にする
        // ==============================================
        $('.btn_suuryou,.btn_case,.btn_bara,.btn_tokki_bikou,.location_title').on('click', function() {

            $('.location_title').toggle();
            $('.location_val').toggle();

        });
    </script>



</body>

</html>