<?php


// ================================================================================
// ================================ five.php 全数完了 処理  ========================
// ================================================================================

if (isset($_GET['all_completed_button'])) {

    $one_option_Syori_SEQ = $_GET['one_option_Syori_SEQ'];
    $one_option_Denpyou_SEQ = $_GET['one_option_Denpyou_SEQ'];
    $one_option_Syukka_Yotei_Num = $_GET['one_option_Syukka_Yotei_Num'];

    $arr_one_option_Denpyou_SEQ = explode(',', $one_option_Denpyou_SEQ);
    $arr_one_option_Syukka_Yotei_Num = explode(',', $one_option_Syukka_Yotei_Num);

    print_r($arr_one_option_Denpyou_SEQ);
    print_r($arr_one_option_Syukka_Yotei_Num);


    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
        echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
        exit;
    }

    $sql = "UPDATE HTPK set ピッキング数量 = :Piking_NUM , 処理Ｆ = 9 WHERE 処理ＳＥＱ = :Syori_SEQ AND 出荷予定数量 = :Syuka_Yotei_NUM";

    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($conn);
        echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
        exit;
    }

    $idx = 0;
    foreach ($arr_one_option_Denpyou_SEQ as $Denpyou_SEQ_VAL) {

        $Syuka_Yotei_Num = $arr_one_option_Syukka_Yotei_Num[$idx];
        $denpyou_num = $Denpyou_SEQ_VAL;

        print($Syuka_Yotei_Num . "<br>");
        print($denpyou_num . "<br>");

        oci_bind_by_name($stid, ":Piking_NUM", $Syuka_Yotei_Num);
        oci_bind_by_name($stid, ":syori_seq", $one_option_Syori_SEQ);
        oci_bind_by_name($stid, ":Syuka_Yotei_NUM", $denpyou_num);

        // ステートメントを実行
        $result = oci_execute($stid);
        if (!$result) {
            $e = oci_error($stid);
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
        }

        $idx = $idx + 1;
    }

    // ステートメントを解放
    oci_free_statement($stid);

    // 接続を閉じる
    oci_close($conn);
}
    // =================== 全数完了 を押した場合 END ========================