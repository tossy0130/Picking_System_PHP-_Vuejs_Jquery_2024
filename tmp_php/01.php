<?php

// =================================== HTPK インサート部分　旧

// ========================== HTPK テーブル重複処理


$conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);
if (!$conn) {
    $e = oci_error();
}


// ========================== 処理ＳＥＱ プラス 1 処理


$select_max = " SELECT MAX(処理ＳＥＱ) AS max_seq FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num 
        AND 伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code 
        AND 商品名 = :IN_Syouhin_Name AND 伝票ＳＥＱ = :Denpyou_SEQ";

$select_seq_stid = oci_parse($conn, $select_max);
if (!$select_seq_stid) {
    $e = oci_error($conn);
}

oci_bind_by_name($select_seq_stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
oci_bind_by_name($select_seq_stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
oci_bind_by_name($select_seq_stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
oci_bind_by_name($select_seq_stid, ':IN_Syouhin_Name', $Shouhin_name);
oci_bind_by_name($select_seq_stid, ':Denpyou_SEQ', $IN_Denpyou_SEQ);


$result_select_seq = oci_execute($select_seq_stid);
if (!$result_select_seq) {
    $e = oci_error($select_seq_stid);
}

$row = oci_fetch_assoc($select_seq_stid);
$max_seq = $row['MAX_SEQ'];

$new_seq = $max_seq + 1;

oci_free_statement($select_seq_stid);



// ========================================
// =========== five.php データ重複　削除
// ========================================

// データ重複　確認

$check_sql = "SELECT COUNT(*) AS num_duplicates FROM HTPK WHERE 伝票番号 = :IN_Dennpyou_num AND 
            伝票行番号 = :IN_Dennpyou_Gyou_num AND 商品Ｃ = :IN_Syouhin_Code AND 商品名 = :Syouhin_name
            AND 伝票ＳＥＱ = :Denpyou_SEQ";

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
oci_bind_by_name($check_stid, ':Denpyou_SEQ', $IN_Denpyou_SEQ);


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
                AND 商品Ｃ = :IN_Syouhin_Code AND  商品名 = :Syouhin_name  AND 伝票ＳＥＱ = :Denpyou_SEQ";

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
    oci_bind_by_name($check_stid, ':Denpyou_SEQ', $IN_Denpyou_SEQ);


    $result_delete = oci_execute($delete_stid);
    if (!$result_delete) {
        $e = oci_error($delete_stid);
        // エラーハンドリングを行う
    }

    oci_free_statement($delete_stid);
}

oci_free_statement($check_stid);

// ========================================
// =========== five.php データ重複　削除  END
// ========================================



// ========================================
// =========== five.php 処理 F 「作業中処理」
// ========================================

$sql = "INSERT INTO HTPK (処理ＳＥＱ, 伝票番号, 伝票行番号, 伝票行枝番,
                                            入力担当, 商品Ｃ, 倉庫Ｃ, 運送Ｃ, 出荷元,
                                            特記事項,出荷予定数量, ピッキング数量,
                                            処理開始日時,登録日,登録端末,処理Ｆ,商品名,伝票ＳＥＱ)
                            VALUES(:NEW_SEQ, :IN_Dennpyou_num, :IN_Dennpyou_Gyou_num, :IN_Dennpyou_Eda_num, 
                            1111, :IN_Syouhin_Code ,:IN_Souko_Code, :unsoucode,
                                    :shippingmoto, :tokki_zikou,:IN_Syuka_Yotei_num,:shouhinnum, 
                                    TO_DATE(:start_time, 'YYYY-MM-DD HH24:MI:SS'),
                                    TO_DATE(:touroku_date, 'YYYY-MM-DD HH24:MI:SS'),
                                    :touroku_sikibetu,2, :Shouhin_name,:Denpyou_SEQ)";


$stid = oci_parse($conn, $sql);
if (!$stid) {
    $e = oci_error($conn);
}


// 処理開始日時
$syori_start_datetime = date('Y-m-d H:i:s');

// 処理開始日時
$start_time = date('Y-m-d');

$start_time_day = date('Y-m-d');
// 登録端末　代わり 
$touroku_sikibetu = '1111_' . $start_time_day;

// パラメータをバインド
oci_bind_by_name($stid, ':NEW_SEQ', $syori_SEQ_value); // 処理 SEQ
oci_bind_by_name($stid, ':IN_Dennpyou_num', $IN_Dennpyou_num);
oci_bind_by_name($stid, ':IN_Dennpyou_Gyou_num', $IN_Dennpyou_Gyou_num);
oci_bind_by_name($stid, ':IN_Dennpyou_Eda_num', $IN_Dennpyou_Eda_num);

oci_bind_by_name($stid, ':IN_Syouhin_Code', $IN_Syouhin_Code);
oci_bind_by_name($stid, ':IN_Souko_Code', $IN_Souko_Code);
oci_bind_by_name($stid, ':unsoucode', $unsou_code);
oci_bind_by_name($stid, ':shippingmoto', $shipping_moto);
oci_bind_by_name($stid, ':tokki_zikou', $IN_Tokki_zikou);

oci_bind_by_name($stid, ':IN_Syuka_Yotei_num', $IN_Syuka_Yotei_num);
oci_bind_by_name($stid, ':shouhinnum', $Shouhin_num);
oci_bind_by_name($stid, ':start_time', $syori_start_datetime); // 処理開始日時
oci_bind_by_name($stid, ':touroku_date', $start_time_day);  // 登録日
oci_bind_by_name($stid, ':touroku_sikibetu', $touroku_sikibetu); // 登録端末
oci_bind_by_name($stid, ':Shouhin_name', $Shouhin_name); // 商品名
oci_bind_by_name($stid, ':Denpyou_SEQ', $IN_Dennpyou_SEQ);   //伝票SEQ

$result_insert = oci_execute($stid);
if (!$result_insert) {
    $e = oci_error($stid);
    echo "[HTPKテーブル] ::: insertエラー:" . $e["message"];
}

oci_free_statement($stid);
oci_close($conn);

// ========================================
// =========== five.php 処理 F 「作業中処理」   END
// ========================================
