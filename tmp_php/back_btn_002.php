<?php
// ================================================================
// =================== 「戻る」 を押した場合 ========================
// ================================================================
if (isset($_GET['five_back_button'])) {

    dprint("戻る ボタン");

    // 単数（特記・備考あり）
    if (isset($_GET['one_now_sql_zensuu']) && $_GET['one_now_sql_zensuu'] != "") {
        $get_now_sql = $_GET['one_now_sql_zensuu'];
        $_SESSION['five_back_one_bikou_tokki_sql'] = $get_now_sql;

        // 単数便
    } else if (isset($_GET['default_root_sql_zensuu']) && $_GET['default_root_sql_zensuu'] != "") {
        $get_now_sql = $_SESSION['four_five_default_SQL'];
        $_SESSION['back_four_five_default_SQL'] = $get_now_sql;
        dprint("koko:four_five_default_SQL");

        // 複数便
    } else if (isset($_GET['multiple_sql_four_sql_zensuu']) && $_GET['multiple_sql_four_sql_zensuu'] != "") {

        $get_now_sql = $_SESSION['multiple_sql'];
        $_SESSION['back_multiple_sql'] = $get_now_sql;
        dprint("ここ:back_multiple_sql");
    }

    // === GET で取得
    $five_back_Syori_SEQ = $_GET['five_back_Syori_SEQ']; // 処理 SEQ
    $five_back_Denpyou_SEQ = $_GET['five_back_Denpyou_SEQ'];
    $five_back_Syukka_Yotei_Num = $_GET['five_back_Syukka_Yotei_Num'];
    $five_back_shouhin_code = $_GET['five_back_shouhin_code'];

    dprint("<br><br>");
    dprint("処理SEQ:::" . $five_back_Syori_SEQ);
    dprint("session:::" . $_SESSION['s_syori_SEQ_value']);

    $get_day = $_GET['day'];
    $get_souko = $_GET['souko']; // 倉庫コード
    $get_unsou_code = $_GET['unsou_code'];
    $kakutei_tokki = $_GET['kakutei_tokki']; // 特記
    $kakutei_bikou = $_GET['kakutei_bikou']; // 備考

    if (isset($_GET['unsou_name'])) {
        $get_unsou_name = $_GET['unsou_name'];
    }

    if (isset($_SESSION['unsou_name'])) {
        $get_unsou_name = $_SESSION['unsou_name'];

        $_SESSION['unsou_name'] = $get_unsou_name;
    } else {
        $_SESSION['unsou_name'] = $get_unsou_name;
    }

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
    } else {
        // ============== 戻る処理

        // ********* 単数便 *********
        if (isset($_GET['default_root_sql_zensuu']) && $_GET['default_root_sql_zensuu'] != "") {

            dprint("戻る");

            echo '<script>
    document.addEventListener("DOMContentLoaded", function() {

        setTimeout(function() {
            window.location.href = "./four.php?five_back_btn=&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                .
                '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                '&day=' . UrlEncode_Val_Check($get_day) .
                '&souko=' . urldecode($get_souko) .
                '&default_root_sql_back=' . UrlEncode_Val_Check($get_now_sql) .
                '";
        }, 500);

    });
</script>';

            // ********* 単数便 , 備考・特記 *********
        } else if (isset($_GET['one_now_sql_zensuu']) && $_GET['one_now_sql_zensuu'] != "") {

            echo '<script>
    document.addEventListener("DOMContentLoaded", function() {

        setTimeout(function() {
            window.location.href = "./four.php?five_back_btn=' . '&unsou_code=' . urldecode($get_unsou_code)
                .
                '&unsou_name=' . urldecode($get_unsou_name) .
                '&day=' . UrlEncode_Val_Check($get_day) .
                '&souko=' . urldecode($get_souko) .
                '&souko_c=' .
                '&one_now_sql_back=' .
                UrlEncode_Val_Check($get_now_sql) .
                '";
        }, 500);

    });
</script>';

            // ********* 複数 選択 *********
        } else {


            echo '<script>
    document.addEventListener("DOMContentLoaded", function() {


        setTimeout(function() {
            window.location.href = "./four.php?kakutei_btn=' . '&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                .
                '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                '&day=' . UrlEncode_Val_Check($get_day) .
                '&souko=' . urldecode($get_souko) .
                '&back_multiple_sql_five_foure=' .
                UrlEncode_Val_Check($get_now_sql) .
                '";
        }, 500);
    });
</script>';
        }
    }
} // ==================== END five_back_button


// ================================================================
// =================== 「確定」 を押した場合 ========================
// ================================================================
if (isset($_GET['kakutei_btn'])) {

    // === GET で取得
    $kakutei_Syori_SEQ = $_GET['kakutei_Syori_SEQ']; // 処理 SEQ
    $kakutei_Denpyou_SEQ = $_GET['kakutei_Denpyou_SEQ'];
    $kakutei_Syukka_Yotei_Num = $_GET['kakutei_Syukka_Yotei_Num'];
    $kakutei_shouhin_code = $_GET['kakutei_shouhin_code'];

    $get_day = $_GET['day'];
    $get_souko = $_GET['souko_name'];
    $get_unsou_code = $_GET['unsou_code'];
    $kakutei_tokki = $_GET['kakutei_tokki']; // 特記
    $kakutei_bikou = $_GET['kakutei_bikou']; // 備考

    if (isset($_GET['unsou_name'])) {
        $get_unsou_name = $_GET['unsou_name'];
    }

    if (isset($_SESSION['unsou_name'])) {
        $get_unsou_name = $_SESSION['unsou_name'];

        $_SESSION['unsou_name'] = $get_unsou_name;
    } else {
        $_SESSION['unsou_name'] = $get_unsou_name;
    }

    // === カウント（入力された値を取得）
    if (isset($_GET['count_num_val']) && $_GET['count_num_val'] != "") {
        $Count_Num_Val = $_GET['count_num_val'];
        dprint("Count_Num_Val:::" . $Count_Num_Val);
    }

    $arr_kakutei_Denpyou_SEQ = explode(',', $kakutei_Denpyou_SEQ); // 伝票 SEQ
    $arr_kakutei_Syukka_Yotei_Num = explode(',', $kakutei_Syukka_Yotei_Num); // 出荷予定数量
    $arr_kakutei_shouhin_code = explode(',', $kakutei_shouhin_code); // 商品コード

    // === 出荷予定数量　の合計
    dprint("<br><br>");
    // print("合計:::" . array_sum($arr_akutei_Syukka_Yotei_Num));

    // === 出荷予定数量
    $Syuka_Yotei_SUM = array_sum($arr_kakutei_Syukka_Yotei_Num);
    dprint("合計:::" . $Syuka_Yotei_SUM);

    dprint("<br><br>");
    dprint("カウントの値:::" . $Count_Num_Val);

    // === 出荷予定数量　合計 （複数回　押した時対応）
    if (isset($_GET['session_Syuka_Yotei_SUM']) && !empty($_GET['session_Syuka_Yotei_SUM'])) {
        $Syuka_Yotei_SUM = $_GET['session_Syuka_Yotei_SUM'];
        dprint("セッション 出荷予定数量:::" . $Syuka_Yotei_SUM);
    }

    // *** 出荷予定数量 より , カウントの値が引く場合は処理を戻す ***
    if ($Count_Num_Val < $Syuka_Yotei_SUM) {
        $errorMessage = "エラーメッセージ: カウント値が出荷予定数量を下回っています。";
        $_SESSION['Count_Num_Val'] = $Count_Num_Val; // カウント $_SESSION['Syuka_Yotei_SUM']=$Syuka_Yotei_SUM; // 出荷予定数量 合計 } else if ($Count_Num_Val> $Syuka_Yotei_SUM) {
        $errorMessage = "エラーメッセージ: カウント値が出荷予定数量を上回っています。";

        $_SESSION['Count_Num_Val'] = $Count_Num_Val; // カウント
        $_SESSION['Syuka_Yotei_SUM'] = $Syuka_Yotei_SUM; // 出荷予定数量 合計
    } else if ($Count_Num_Val == $Syuka_Yotei_SUM) {
        // ================= 確定　アップデート処理 ==================

        // =============== Update 処理 =================
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        $sql = "UPDATE HTPK SET ピッキング数量 = :Piking_NUM, 処理Ｆ = 9,
    処理終了日時 = TO_DATE(:end_time, 'YYYY-MM-DD HH24:MI:SS')
    WHERE 処理ＳＥＱ = :Syori_SEQ AND 商品Ｃ = :Syouhin_Code AND 出荷予定数量 = :Syuka_Yotei_NUM
    AND 伝票ＳＥＱ = :Denpyou_SEQ";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($conn);
            echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        // コミット用　フラグ
        $commit_success = true;
        $idx = 0;
        foreach ($arr_kakutei_Denpyou_SEQ as $Denpyou_SEQ_VAL) {
            $Syuka_Yotei_Num = $arr_kakutei_Syukka_Yotei_Num[$idx];
            $Syouhin_Code_Num = $arr_kakutei_shouhin_code[$idx];

            print($Syuka_Yotei_Num . "<br>");
            print($Syouhin_Code_Num . "<br>");

            // 数値にキャスト
            $Syuka_Yotei_Num = (int)$Syuka_Yotei_Num;
            $kakutei_Syori_SEQ = (int)$kakutei_Syori_SEQ;
            $Denpyou_SEQ_VAL = (int)$Denpyou_SEQ_VAL;
            $Syouhin_Code_Num = (int)$Syouhin_Code_Num;

            // 処理終了日時
            $end_time = date('Y-m-d H:i:s');

            oci_bind_by_name($stid, ":Piking_NUM", $Syuka_Yotei_Num); // 出荷予定数量（ピッキング数量へ入れる）
            oci_bind_by_name($stid, ":end_time", $end_time); // 処理終了日時
            oci_bind_by_name($stid, ":Syori_SEQ", $kakutei_Syori_SEQ); // 処理 SEQ
            oci_bind_by_name($stid, ":Syouhin_Code", $Syouhin_Code_Num); // 商品コード
            oci_bind_by_name($stid, ":Syuka_Yotei_NUM", $Syuka_Yotei_Num); // 出荷予定数量
            oci_bind_by_name($stid, ":Denpyou_SEQ", $Denpyou_SEQ_VAL); // 伝票 SEQ

            $idx += 1;

            // ステートメントを実行
            $result = oci_execute($stid);
            if (!$result) {
                $e = oci_error($stid);
                echo htmlentities($e['message'], ENT_QUOTES, 'UTF-8');
                // トランザクションをロールバック
                oci_rollback($conn);
                $commit_success = false;
                break;
            }
        } // =============== END foreach


        // 単数（特記・備考あり）
        if (isset($_GET['one_now_sql_zensuu']) && $_GET['one_now_sql_zensuu'] != "") {

            if ($commit_success) {
                oci_commit($conn);
                // コミットが成功した場合

                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#successModal").modal("show");
            setTimeout(function() {
                $("#successModal").modal("hide");
                $("#all_completed_button").prop("disabled", true);

                setTimeout(function() {
                    window.location.href = "./four.php?kakutei_btn=' . '&unsou_code=' . urldecode($get_unsou_code)
                    .
                    '&unsou_name=' . urldecode($get_unsou_name) .
                    '&day=' . UrlEncode_Val_Check($get_day) .
                    '&souko=' . urldecode($get_souko) .
                    '&souko_c=' .
                    '&one_now_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) .
                    '";
                }, 500);


            }, 2000);
        });
    </script>';
            } else {
                // コミットが失敗した場合
                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#errorModal").modal("show");
            setTimeout(function() {
                $("#errorModal").modal("hide");
            }, 2000);
        });
    </script>';
            }

            // 単数便
        } else if (isset($_GET['default_root_sql_zensuu']) && $_GET['default_root_sql_zensuu'] != "") {

            if ($commit_success) {
                oci_commit($conn);
                // コミットが成功した場合

                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#successModal").modal("show");
            setTimeout(function() {
                $("#successModal").modal("hide");
                $("#all_completed_button").prop("disabled", true);

                setTimeout(function() {
                    window.location.href = "./four.php?kakutei_btn=' . '&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                    .
                    '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                    '&day=' . UrlEncode_Val_Check($get_day) .
                    '&souko=' . urldecode($get_souko) .
                    '&default_root_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) .
                    '";
                }, 500);


            }, 2000);
        });
    </script>';
            } else {
                // コミットが失敗した場合
                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#errorModal").modal("show");
            setTimeout(function() {
                $("#errorModal").modal("hide");
            }, 2000);
        });
    </script>';
            }

            // 複数便
        } else if (isset($_GET['multiple_sql_four_sql_zensuu']) && $_GET['multiple_sql_four_sql_zensuu'] != "") {

            if ($commit_success) {
                oci_commit($conn);
                // コミットが成功した場合

                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#successModal").modal("show");
            setTimeout(function() {
                $("#successModal").modal("hide");
                $("#all_completed_button").prop("disabled", true);

                setTimeout(function() {
                    window.location.href = "./four.php?kakutei_btn=' . '&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                    .
                    '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) .
                    '&day=' . UrlEncode_Val_Check($get_day) .
                    '&souko=' . urldecode($get_souko) .
                    '&back_multiple_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) .
                    '";
                }, 500);


            }, 2000);
        });
    </script>';
            } else {
                // コミットが失敗した場合
                echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            $("#errorModal").modal("show");
            setTimeout(function() {
                $("#errorModal").modal("hide");
            }, 2000);
        });
    </script>';
            }
        }
    }
} // =================== END $_GET['kakutei_btn']



//======================


/*
            $Syuka_Yotei_SUM = $_SESSION['Syuka_Yotei_SUM']; // 出荷予定数量 合計
            $kakutei_Syori_SEQ = $_SESSION['kakutei_Syori_SEQ'];
            $arr_kakutei_Denpyou_SEQ = $_SESSION['arr_kakutei_Denpyou_SEQ'];
            $arr_kakutei_Syukka_Yotei_Num = $_SESSION['arr_kakutei_Syukka_Yotei_Num'];
            $arr_kakutei_shouhin_code = $_SESSION['arr_kakutei_shouhin_code'];
            */

if (isset($_SESSION['Count_Num_Val'])) {
    $Count_Num_Val = $_SESSION['Count_Num_Val']; // カウント
} else {
    $_SESSION['Count_Num_Val'] = $Count_Num_Val; // カウント
}

if (isset($_SESSION['Syuka_Yotei_SUM'])) {
    $Syuka_Yotei_SUM = $_SESSION['Syuka_Yotei_SUM']; // カウント
} else {
    $_SESSION['Syuka_Yotei_SUM'] = $Syuka_Yotei_SUM; // 出荷予定数量 合計

}

if (isset($_SESSION['kakutei_Syori_SEQ'])) {
    $kakutei_Syori_SEQ = $_SESSION['kakutei_Syori_SEQ']; // カウント
} else {
    $_SESSION['kakutei_Syori_SEQ'] = $kakutei_Syori_SEQ;
}

if (isset($_SESSION['arr_kakutei_Denpyou_SEQ'])) {
    $arr_kakutei_Denpyou_SEQ = $_SESSION['arr_kakutei_Denpyou_SEQ']; // カウント
} else {
    $_SESSION['arr_kakutei_Syukka_Yotei_Num'] = $arr_kakutei_Syukka_Yotei_Num;
}

if (isset($_SESSION['arr_kakutei_Syukka_Yotei_Num'])) {
    $arr_kakutei_Syukka_Yotei_Num = $_SESSION['arr_kakutei_Syukka_Yotei_Num']; // カウント
} else {
    $_SESSION['arr_kakutei_Syukka_Yotei_Num'] = $arr_kakutei_Syukka_Yotei_Num;
}

if (isset($_SESSION['arr_kakutei_shouhin_code'])) {
    $arr_kakutei_shouhin_code = $_SESSION['arr_kakutei_shouhin_code']; // カウント
} else {
    $_SESSION['arr_kakutei_shouhin_code'] = $arr_kakutei_shouhin_code;
}
