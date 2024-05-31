<?php

ini_set('display_errors', 1);

require __DIR__ . "/conf.php";
require_once(dirname(__FILE__) . "/class/init_val.php");
require(dirname(__FILE__) . "/class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

// 倉庫名
$souko_name = $_SESSION['soko_name'];

// === ログイン ID

$input_login_id = $_SESSION['input_login_id'];

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

    $zaikosu = "";
    $tokuimei = "";

    if (isset($_SESSION['soko_name'])) {
        $get_souko_name = $_SESSION['soko_name'];
        $_SESSION['soko_name'] = $get_souko_name;
    } else {
        $_SESSION['soko_name'] = $souko_name;
    }

    // === ２回目以降の処理
    if (isset($_GET['Denpyou_SEQ'])) {
        $Denpyou_SEQ = $_GET['Denpyou_SEQ'];
        dprint("伝票SEQ:::" . $Denpyou_SEQ);
    }

    // ================================================================
    // =================== 全数完了 を押した場合 ========================
    // ================================================================
    if (isset($_GET['all_completed_button'])) {

        $one_option_Syori_SEQ = $_GET['one_option_Syori_SEQ']; // 処理 SEQ
        $one_option_Denpyou_SEQ = $_GET['one_option_Denpyou_SEQ'];
        $one_option_Syukka_Yotei_Num = $_GET['one_option_Syukka_Yotei_Num'];
        $one_op_shouhin_code = $_GET['one_op_shouhin_code'];


        // SQL を取得 （全数完了 から）

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

        $get_day = $_GET['day'];
        $get_souko = $_GET['souko'];
        $get_unsou_code = $_GET['unsou_code'];

        $get_one_op_tokki = $_GET['one_op_tokki'];

        if (isset($_GET['unsou_name'])) {
            $get_unsou_name = $_GET['unsou_name'];
        }

        if (isset($_SESSION['unsou_name'])) {
            $get_unsou_name = $_SESSION['unsou_name'];

            $_SESSION['unsou_name'] = $get_unsou_name;
        } else {
            $_SESSION['unsou_name'] = $get_unsou_name;
        }



        $arr_one_option_Denpyou_SEQ = explode(',', $one_option_Denpyou_SEQ); // 伝票 SEQ
        $arr_one_option_Syukka_Yotei_Num = explode(',', $one_option_Syukka_Yotei_Num); // 出荷予定数量
        $arr_one_op_shouhin_code = explode(',', $one_op_shouhin_code);  // 商品コード

        /*
        print_r($arr_one_option_Denpyou_SEQ);
        print_r($arr_one_option_Syukka_Yotei_Num);
        print_r($arr_one_op_shouhin_code);
        */

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
            echo htmlentities(
                $e['message'],
                ENT_QUOTES,
                'UTF-8'
            );
            exit;
        }

        // コミット用　フラグ
        $commit_success = true;
        $idx = 0;
        foreach ($arr_one_option_Denpyou_SEQ as $Denpyou_SEQ_VAL) {
            $Syuka_Yotei_Num = $arr_one_option_Syukka_Yotei_Num[$idx];
            $Syouhin_Code_Num = $arr_one_op_shouhin_code[$idx];

            // 数値にキャスト
            $Syuka_Yotei_Num = (int)$Syuka_Yotei_Num;
            $one_option_Syori_SEQ = (int)$one_option_Syori_SEQ;
            $Denpyou_SEQ_VAL = (int)$Denpyou_SEQ_VAL;
            $Syouhin_Code_Num = (int)$Syouhin_Code_Num;

            // 処理終了日時
            $end_time = date('Y-m-d H:i:s');

            oci_bind_by_name($stid, ":Piking_NUM", $Syuka_Yotei_Num);     // 出荷予定数量（ピッキング数量へ入れる）
            oci_bind_by_name($stid, ":end_time", $end_time); // 処理終了日時
            oci_bind_by_name($stid, ":Syori_SEQ", $one_option_Syori_SEQ); // 処理 SEQ
            oci_bind_by_name($stid, ":Syouhin_Code", $Syouhin_Code_Num);  // 商品コード
            oci_bind_by_name($stid, ":Syuka_Yotei_NUM", $Syuka_Yotei_Num); // 出荷予定数量
            oci_bind_by_name($stid, ":Denpyou_SEQ", $Denpyou_SEQ_VAL);    // 伝票 SEQ

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
                          window.location.href = "./four.php?all_completed_button=' . '&unsou_code=' . urldecode($get_unsou_code)
                    . '&unsou_name=' . urldecode($get_unsou_name) . '&day=' . UrlEncode_Val_Check($get_day) . '&souko=' . urldecode($get_souko) . '&one_now_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) . '";
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
                          window.location.href = "./four.php?all_completed_button=' . '&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                    . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&day=' . UrlEncode_Val_Check($get_day) . '&souko=' . urldecode($get_souko) . '&default_root_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) . '";
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
                          window.location.href = "./four.php?all_completed_button=' . '&unsou_code=' . UrlEncode_Val_Check($get_unsou_code)
                    . '&unsou_name=' . UrlEncode_Val_Check($get_unsou_name) . '&day=' . UrlEncode_Val_Check($get_day) . '&souko=' . urldecode($get_souko) . '&back_multiple_sql_zensuu=' .
                    UrlEncode_Val_Check($get_now_sql) . '";
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

    // ================================================================
    // =================== 全数完了 を押した場合  END ========================
    // ================================================================




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

        // GET urlパラメーター取得
        $select_day = $_GET['select_day'];
        $souko_code = $_GET['souko_code'];
        $unsou_code = $_GET['unsou_code'];

        // 運送名
        if (isset($_GET['unsou_name']) && !empty($_GET['unsou_name'])) {
            $unsou_name = $_GET['unsou_name'];
        } else {
            $unsou_name = "";
        }

        $shipping_moto = $_GET['shipping_moto'];
        $shipping_moto_name = $_GET['shipping_moto_name'];
        $Shouhin_code = $_GET['Shouhin_code'];
        $Shouhin_name = $_GET['Shouhin_name'];
        $Shouhin_num = $_GET['Shouhin_num'];

        $Tana_num = $_GET['tana_num'];
        $case_num = $_GET['case_num'];
        $bara_num = $_GET['bara_num'];
        $shouhin_jan = $_GET['shouhin_jan'];

        /*
        dprint("GETデータ出力:::" . "<br>");
        dprint("select_day:::" . $select_day . "<br>");
        dprint("souko_code:::" . $souko_code . "<br>");
        dprint("unsou_code:::" . $unsou_code . "<br>");
        dprint("unsou_name:::" . $unsou_name . "<br>");
        dprint("shipping_moto:::" . $shipping_moto . "<br>");
        dprint("shipping_moto_name:::" . $shipping_moto_name . "<br>");
        dprint("Shouhin_code:::" . $Shouhin_code . "<br>");
        dprint("Shouhin_name:::" . $Shouhin_name . "<br>");
        dprint("Shouhin_num:::" . $Shouhin_num . "<br>");

        dprint("Tana_num:::" . $Tana_num . "<br>");
        dprint("case_num:::" . $case_num . "<br>");
        dprint("bara_num:::" . $bara_num . "<br>");
        dprint("shouhin_jan:::" . $shouhin_jan . "<br>");
        */

        // 特記
        if (isset($_GET['tokki_zikou'])) {
            $tokki_zikou = $_GET['tokki_zikou'];
            dprint("特記:::" . $tokki_zikou . "<br>");
        }

        // === 備考
        if (isset($_GET['shipping_moto_name'])) {
            $shipping_moto = $_GET['shipping_moto_name'];
        }

        // ケース数
        $Case_Num_Val = $case_num;
        // バラ数

        /*
        // ケース数
        if (($Shouhin_num != 0) && ($Konpou_num != 0)) {
            $Case_Num_Val = floor($Shouhin_num / $Konpou_num);
        }
        // バラ数
        if (($Konpou_num != 0) && ($Shouhin_num != 0)) {
            $bara_num_tmp = $Shouhin_num % $Konpou_num;
        }
        */

        // dprint("倉庫名:::" . $souko_name . "<br>");

        // === 40バイトで分ける
        // 商品名
        $Shouhin_name_part1 = mb_substr($Shouhin_name, 0, 20);
        // 品番
        $Shouhin_name_part2 = mb_substr($Shouhin_name, 20);

        // === 得意先  コメントアウト
        /*
        $arr_Tokuisaki_name = [];
        $arr_Tokuisaki_name = SplitString_FUNC($Tokuisaki_name);
        */

        //   print_r($arr_Tokuisaki_name);
        // $Shouhin_Detail_DATA = [];
        // 取得データ
        $Shouhin_Detail_DATA = [
            $Tana_num,
            "",
            $Shouhin_name_part1, // 商品名
            $Shouhin_name_part2, // 品番
            $shouhin_jan,      // 商品コード 01 （JAN）
            $Shouhin_code, // 商品コード 02
            $Shouhin_num,  // 数量
            $case_num,    // ケース数
            $bara_num,    // バラ数
            "1",
            $shipping_moto_name, // 備考名
            $unsou_name,  // 運送名
            $tokki_zikou, // 特記
            "カインズ × 2",
            "ムサシ × 1"
        ];


        // *******************************
        // === 可変の SQL 取得
        // *******************************
        if (isset($_GET['now_sql']) || $_GET['four_status'] == 'one_bikou_tokki') {

            // === 全数完了　ルートで完了後、 four.phpから来た場合
            if (empty($_GET['now_sql'])) {
                $one_condition = getCondition($_SESSION['back_one_option_zensuu_kanryou']);
                dprint($one_condition);
                // === 通常の four.php　から来た場合
            } else {
                $one_condition = getCondition($_GET['now_sql']);
                dprint($one_condition);
            }



            $now_sql_multiple = "";
            $GET_now_sql_multiple_url = "";
        } else if (isset($_GET['now_sql_multiple'])) {
            $GET_now_sql_multiple = $_GET['now_sql_multiple'];
            $GET_now_sql_multiple_url = urldecode($GET_now_sql_multiple);
            $sql_multiple_condition = getCondition_Multiple($_GET['now_sql_multiple']);

            $one_condition = "";
        } else {
            $one_condition = "";

            $now_sql_multiple = "";
            $GET_now_sql_multiple = "";
            $GET_now_sql_multiple_url = "";
            $sql_multiple_condition  = "";
        }


        // ============================= インサート用データ 取得 =============================
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        /*
        $sql = " SELECT SL.伝票ＳＥＱ,SL.伝票番号, SL.伝票行番号, SL.伝票行枝番, SL.商品Ｃ, SL.倉庫Ｃ,SL.出荷元,SL.数量
    FROM SLTR SL
    WHERE SL.商品Ｃ = :syouhin_Code and SL.倉庫Ｃ = :souko_Code and 
   ORDER BY SL.伝票ＳＥＱ DESC
    FETCH FIRST 1 ROW ONLY";
    */

        //+++++++++++++++++++++++++
        // 在庫数取得
        $sql_zai = "SELECT RZ.在庫数量 
                      FROM RZMF RZ 
                     WHERE RZ.商品Ｃ = :Shouhin_code 
                       AND RZ.倉庫Ｃ = :souko_code";
        $stid_zai = oci_parse($conn, $sql_zai);
        if (!$stid_zai) {
            $e = oci_error($conn);
            // エラーハンドリングを行う
        }

        oci_bind_by_name($stid_zai, ":Shouhin_code", $Shouhin_code);       // 商品Ｃ
        oci_bind_by_name($stid_zai, ":souko_Code", $souko_code);           // 倉庫Ｃ

        $result_zai = oci_execute($stid_zai);
        if (!$result_zai) {
            $e = oci_error($stid_zai);
            // エラーハンドリングを行う
        }

        $new_zai = oci_fetch_assoc($stid_zai);

        if ($new_zai) {
            $zaikosu = $new_zai['在庫数量'];
        } else {
            // フェッチエラーのハンドリング
            trigger_error("処理ＳＥＱ 値取得エラー.", E_USER_ERROR);
        }
        oci_free_statement($stid_zai);
        //+++++++++++++++++++++++++

        // =================================================
        // ========================== 処理 SEQ 取得
        // =================================================
        $sql_syori_SEQ = "SELECT ROW_ID.NEXTVAL AS 処理ＳＥＱ FROM DUAL";

        $syori_SEQ_stid = oci_parse($conn, $sql_syori_SEQ);
        if (!$syori_SEQ_stid) {
            $e = oci_error($conn);
            // エラーハンドリングを行う
        }

        $result_syori_SEQ = oci_execute($syori_SEQ_stid);
        if (!$result_syori_SEQ) {
            $e = oci_error($syori_SEQ_stid);
            // エラーハンドリングを行う
        }

        $new_syori_SEQ = oci_fetch_assoc($syori_SEQ_stid);

        if ($new_syori_SEQ) {
            $syori_SEQ_value = $new_syori_SEQ['処理ＳＥＱ'];
        } else {
            // フェッチエラーのハンドリング
            trigger_error("処理ＳＥＱ 値取得エラー.", E_USER_ERROR);
        }

        // print "処理 SEQ: " . $syori_SEQ_value;

        oci_free_statement($syori_SEQ_stid);

        //HTPK 作成 SQL
        $sql_ins_HTPK = "INSERT INTO HTPK(
                                  処理ＳＥＱ     ,
                                  伝票ＳＥＱ     ,
                                  伝票番号       ,
                                  伝票行番号     ,
                                  伝票行枝番     ,
                                  入力担当       ,
                                  商品Ｃ         ,
                                  倉庫Ｃ         ,
                                  運送Ｃ         ,
                                  出荷元         ,
                                  特記事項       ,
                                  出荷予定数量   ,
                                  ピッキング数量 ,
                                  出荷日         ,
                                  処理開始日時   ,
                                  処理終了日時   ,
                                  登録日         ,
                                  登録端末       ,
                                  処理Ｆ         )
                                  SELECT
                                        :syori_SEQ     ,
                                        SL.伝票ＳＥＱ  ,
                                        SL.伝票番号    ,
                                        SL.伝票行番号  ,
                                        SL.伝票行枝番  ,
                                        :input_tantou  ,
                                        SL.商品Ｃ      ,
                                        SL.倉庫Ｃ      ,
                                        SJ.運送Ｃ      ,
                                        SL.出荷元      ,
                                        SK.特記事項    ,
                                        SL.数量        ,
                                        0              ,
                                        SJ.出荷日      ,
                                        TO_DATE(:start_time, 'YYYY-MM-DD HH24:MI:SS'),
                                        NULL           ,
                                        TO_DATE(:touroku_date, 'YYYY-MM-DD HH24:MI:SS'),
                                        :tanmatu_id    ,
                                        2             
                                   FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
                                        ,RZMF RZ
                                        ,HTPK PK
                                   WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                                     AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                                     AND SK.伝票番号   = SL.伝票番号
                                     AND SK.伝票行番号 = SL.伝票行番号
                                     AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                                     AND SL.伝票番号   = PK.伝票番号(+)
                                     AND SL.伝票行番号 = PK.伝票行番号(+)
                                     AND SL.伝票行枝番 = PK.伝票行枝番(+)
                                     AND PK.伝票ＳＥＱ IS NULL
                                     AND SL.倉庫Ｃ = SO.倉庫Ｃ
                                     AND SL.出荷元 = SM.出荷元Ｃ(+)
                                     AND SJ.運送Ｃ = US.運送Ｃ
                                     AND SL.商品Ｃ = SH.商品Ｃ
                                     AND SL.倉庫Ｃ = RZ.倉庫Ｃ
                                     AND SL.商品Ｃ = RZ.商品Ｃ
                                     AND SJ.出荷日 = :select_day
                                     AND SL.倉庫Ｃ = :souko_Code 
                                     AND SL.商品Ｃ = :syouhin_Code ";

        //得意先名情報取得SQL作成
        /* 得意先別
        $sql_Sel_TkNm = "SELECT SJ.得意先名 ,COUNT(SJ.得意先名) AS CNT
                           FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, USMF US, HTPK PK
                          WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                            AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                            AND SK.伝票番号   = SL.伝票番号
                            AND SK.伝票行番号 = SL.伝票行番号
                            AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                            AND SL.伝票番号   = PK.伝票番号(+)
                            AND SL.伝票行番号 = PK.伝票行番号(+)
                            AND SL.伝票行枝番 = PK.伝票行枝番(+)
                            AND SL.倉庫Ｃ = SO.倉庫Ｃ
                            AND SJ.運送Ｃ = US.運送Ｃ
                            AND PK.処理ＳＥＱ = :syori_SEQ_value
                            AND SJ.出荷日 = :select_day
                            AND SL.倉庫Ｃ = :souko_Code 
                            AND SL.商品Ｃ = :syouhin_Code ";
*/
        $sql_Sel_TkNm = "SELECT COUNT(CM2.得意先名) AS CNT,CM.集計得意先Ｃ,CM2.得意先名 AS 集計得意先名
                           FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, USMF US, HTPK PK, CMMF CM, CMMF CM2
                          WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                            AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                            AND SK.伝票番号   = SL.伝票番号
                            AND SK.伝票行番号 = SL.伝票行番号
                            AND SJ.得意先Ｃ = CM.得意先Ｃ
                            AND CM.集計得意先Ｃ = CM2.得意先Ｃ
                            AND SL.伝票ＳＥＱ = PK.伝票ＳＥＱ(+)
                            AND SL.伝票番号   = PK.伝票番号(+)
                            AND SL.伝票行番号 = PK.伝票行番号(+)
                            AND SL.伝票行枝番 = PK.伝票行枝番(+)
                            AND SL.倉庫Ｃ = SO.倉庫Ｃ
                            AND SJ.運送Ｃ = US.運送Ｃ
                            AND PK.処理ＳＥＱ = :syori_SEQ_value
                            AND SJ.出荷日 = :select_day
                            AND SL.倉庫Ｃ = :souko_Code 
                            AND SL.商品Ｃ = :syouhin_Code ";

        $flg_TkNm_Unso = 0;

        //条件作成
        if (isset($_GET['four_status']) && $_GET['four_status'] == 'multiple_sql_four') {
            //【運送便（複数）,備考・特記あり】　複数　 処理
            $five_multiple_sql_cut = $_SESSION['multiple_sql_cut'];

            dprint("five_multiple_sql_cut 値:::" . $five_multiple_sql_cut);

            //条件記述 ****************************************************
            //$sql_ins_HTPK .= "     AND SJ.運送Ｃ = :unsou_code ";
            //運送Ｃ・備考・特記 条件

            //  if (!empty($one_condition) && isset($one_condition)) {
            if (!empty($five_multiple_sql_cut) && isset($five_multiple_sql_cut)) {

                //例.
                //   AND ((SJ.運送Ｃ = '1') AND (SL.出荷元 = '0') AND (SK.特記事項 = '特記新潟'))
                //dprint("one_condition" . $one_condition . "<br>");
                $sql_ins_HTPK .= $five_multiple_sql_cut;
            }
            $sql_ins_HTPK .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                                      ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
                                      ,SL.伝票ＳＥＱ,SL.伝票番号,SL.伝票行番号,SL.伝票行枝番,SL.数量";
            /*
            $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ
                                      ,PK.処理Ｆ,SJ.得意先名";
*/
            $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ ,PK.処理Ｆ
                                      ,CM.集計得意先Ｃ,CM2.得意先Ｃ, CM2.得意先名";
        } else if (isset($_GET['now_sql']) || $_GET['four_status'] == 'one_bikou_tokki') {
            //【運送便（単数）,備考・特記あり】 処理
            //運送便（単数） 条件
            //    $sql_ins_HTPK .= "     AND SJ.運送Ｃ = :unsou_code ";
            //備考・特記 条件
            if (!empty($one_condition) && isset($one_condition)) {
                //例.
                //   AND ((SJ.運送Ｃ = '1') AND (SL.出荷元 = '0') AND (SK.特記事項 = '特記新潟'))
                //dprint("one_condition" . $one_condition . "<br>");
                $sql_ins_HTPK .= $one_condition;
                $sql_Sel_TkNm .= $one_condition;
            } else {
                $sql_ins_HTPK .= "     AND SJ.運送Ｃ = :unsou_code ";
                $sql_Sel_TkNm .= "     AND SJ.運送Ｃ = :unsou_code ";
                $flg_TkNm_Unso = 1;
            }

            $sql_ins_HTPK .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                                      ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
                                      ,SL.伝票ＳＥＱ,SL.伝票番号,SL.伝票行番号,SL.伝票行枝番,SL.数量";
            /*
            $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ
                                      ,PK.処理Ｆ,SJ.得意先名";
*/
            $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ ,PK.処理Ｆ
                                      ,CM.集計得意先Ｃ,CM2.得意先Ｃ, CM2.得意先名";
        } else if (isset($_GET['four_status']) && $_GET['four_status'] = 'default_root') {
            //【通常】 処理
            //条件記述 ****************************************************

            // four.php から SQL取得
            if (isset($_SESSION['four_five_default_SQL'])) {
                $four_five_default_SQL = $_SESSION['four_five_default_SQL'];

                $sql_ins_HTPK .= "     AND SJ.運送Ｃ = :unsou_code ";
                $sql_Sel_TkNm .= "     AND SJ.運送Ｃ = :unsou_code ";
                $flg_TkNm_Unso = 1;

                $sql_ins_HTPK .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                                          ,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項
                                          ,SL.伝票ＳＥＱ,SL.伝票番号,SL.伝票行番号,SL.伝票行枝番,SL.数量";
                /*
                $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ
                                          ,PK.処理Ｆ,SJ.得意先名";
*/
                $sql_Sel_TkNm .= "GROUP BY SJ.出荷日,SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SK.特記事項,SL.商品Ｃ ,PK.処理Ｆ
                                          ,CM.集計得意先Ｃ,CM2.得意先Ｃ, CM2.得意先名";
                // 出力 OK
                //  dprint($four_five_default_SQL);
            }
        }


        // ===============================================================================
        //                　　　　【運送便（複数）,備考・特記あり】　複数　 処理
        // ===============================================================================
        if (isset($_GET['four_status']) && $_GET['four_status'] == 'multiple_sql_four') {

            dprint("複数");

            $five_multiple_sql = $_SESSION['multiple_sql'];

            //dprint("five_multiple_sql::::" . $five_multiple_sql. "<br>");

            $five_multiple_sql_cut = $_SESSION['multiple_sql_cut'];

            //dprint("five_multiple_sql_cut::::" . $five_multiple_sql_cut. "<br>");

            //=========================
            $sql = $sql_ins_HTPK;

            //   dprint("<br><br>");
            //   dprint($sql);

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
                echo htmlentities($e['message']);
            }

            // 処理開始日時
            $syori_start_datetime = date('Y-m-d H:i:s');
            // 登録日
            $touroku_day = date('Y-m-d H:i:s');

            $tantou_day = date('Y-m-d');

            // === 担当者
            $input_tantou = $input_login_id;
            // === 識別名
            // PC名は取得できないので、担当者Ｃ_日付
            $tanmatu_id = $input_tantou . "_" . $tantou_day;

            oci_bind_by_name($stid, ":syori_SEQ", $syori_SEQ_value);       // 処理 SEQ
            oci_bind_by_name($stid, ":input_tantou", $input_tantou);       // 担当 
            oci_bind_by_name($stid, ':start_time', $syori_start_datetime); // 処理開始日時
            oci_bind_by_name($stid, ':touroku_date', $touroku_day);        // 登録日
            oci_bind_by_name($stid, ":tanmatu_id", $tanmatu_id);           // 端末

            oci_bind_by_name($stid, ":select_day", $select_day);           // 指示日
            oci_bind_by_name($stid, ":souko_Code", $souko_code);           // 倉庫Ｃ
            oci_bind_by_name($stid, ":syouhin_Code", $Shouhin_code);       // 商品Ｃ

            oci_execute($stid);

            oci_free_statement($stid);

            //以下重複処理

            // 処理SEQ
            $sql_seq = "SELECT 処理ＳＥＱ, 伝票ＳＥＱ, 伝票番号, 伝票行番号,伝票行枝番,商品Ｃ,倉庫Ｃ,出荷元,特記事項,出荷予定数量
                          FROM HTPK
                         WHERE 処理ＳＥＱ = :get_Syori_SEQ";

            $stid_syori_seq = oci_parse($conn, $sql_seq);
            if (!$stid_syori_seq) {
                $e = oci_error($stid_syori_seq);
                echo htmlentities($e['message']);
            }

            oci_bind_by_name($stid_syori_seq, ":get_Syori_SEQ", $syori_SEQ_value);

            //  dprint($sql_seq);
            oci_execute($stid_syori_seq);

            $arr_Insert_Picking = array();
            //   $Syuka_Yotei_SUM_NUM = 0; // 出荷数量　合計

            $arr_Denpyou_SEQ = []; // 伝票SEQ 用
            $arr_Syukka_Yotei_Num = [];   // 出荷予定数量
            $arr_Shouhin_Code = [];

            $Syuka_Yotei_SUM_NUM = 0;

            $idx = 0;
            while ($row = oci_fetch_assoc($stid_syori_seq)) {
                // カラム名を指定して値を取得
                $IN_Dennpyou_SEQ = $row['伝票ＳＥＱ'];
                $IN_Dennpyou_num = $row['伝票番号'];
                $IN_Dennpyou_Gyou_num = $row['伝票行番号'];
                $IN_Dennpyou_Eda_num = $row['伝票行枝番'];
                $IN_Syouhin_Code = $row['商品Ｃ'];
                $IN_Souko_Code = $row['倉庫Ｃ'];
                $IN_Syukamoto = $row['出荷元'];
                $IN_Tokki_zikou = $row['特記事項'];
                $IN_Syukka_Num = $row['出荷予定数量'];

                // 出荷予定 合計
                // $Syuka_Yotei_SUM_NUM = $Syuka_Yotei_SUM_NUM + $IN_Syukka_Num;
                //   $Syuka_Yotei_SUM_NUM = $IN_Syukka_Num;
                // 伝票 SEQ
                $arr_Denpyou_SEQ[$idx] = $IN_Dennpyou_SEQ;
                // 出荷予定数量
                $arr_Syukka_Yotei_Num[$idx] = $IN_Syukka_Num;

                // 出荷予定 合計
                $Syuka_Yotei_SUM_NUM = $Syuka_Yotei_SUM_NUM + $arr_Syukka_Yotei_Num[$idx];

                // 商品コード 
                $arr_Shouhin_Code[$idx] = $IN_Syouhin_Code;

                /*
                dprint("<br>");
                dprint("データ:" . $idx . "件");
                dprint("伝票ＳＥＱ:::" . $IN_Dennpyou_SEQ . "<br />");
                dprint("伝票番号:::" . $IN_Dennpyou_num . "<br />");
                dprint("伝票行番号:::" . $IN_Dennpyou_Gyou_num . "<br />");
                dprint("伝票行枝番:::" . $IN_Dennpyou_Eda_num . "<br />");
                dprint("商品Ｃ:::" . $IN_Syouhin_Code . "<br />");
                dprint("倉庫Ｃ:::" . $IN_Souko_Code . "<br />");
                dprint("出荷元:::" . $IN_Syukamoto . "<br />");
                dprint("特記事項:::" . $IN_Tokki_zikou . "<br />");
                dprint("出荷予定数量:::" . $IN_Syukka_Num  . "<br />");
                dprint("<br /><br />");
                */

                $idx = $idx + 1;
            }
            // 処理 SEQ
            $In_Syori_SEQ = $syori_SEQ_value;
            // 伝票 SEQ
            $Strs_Denpyou_SEQ = implode(',', $arr_Denpyou_SEQ);
            // 出荷予定数量
            $Strs_Syukka_Yotei_Num = implode(',', $arr_Syukka_Yotei_Num);
            // 商品コード
            $strs_Shouhin_Code = implode(',', $arr_Shouhin_Code);

            //以下重複処理 End
            //**********************************************************

        } else if (isset($_GET['now_sql']) || $_GET['four_status'] == 'one_bikou_tokki') {

            // ===============================================================================
            //                　　　　【運送便（単数）,備考・特記あり】 処理
            // ===============================================================================


            dprint("単数, 備考・特記あり");


            $sql = $sql_ins_HTPK;

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
                echo htmlentities($e['message']);
            }

            // 処理開始日時
            $syori_start_datetime = date('Y-m-d H:i:s');
            // 登録日
            $touroku_day = date('Y-m-d H:i:s');

            $tantou_day = date('Y-m-d');

            // === 担当者
            $input_tantou = $input_login_id;
            // === 識別名
            // PC名は取得できないので、担当者Ｃ_日付
            $tanmatu_id = $input_tantou . "_" . $tantou_day;

            oci_bind_by_name($stid, ":syori_SEQ", $syori_SEQ_value);       // 処理 SEQ
            oci_bind_by_name($stid, ":input_tantou", $input_tantou);       // 担当 
            oci_bind_by_name($stid, ':start_time', $syori_start_datetime); // 処理開始日時
            oci_bind_by_name($stid, ':touroku_date', $touroku_day);        // 登録日
            oci_bind_by_name($stid, ":tanmatu_id", $tanmatu_id);           // 端末

            oci_bind_by_name($stid, ":select_day", $select_day);           // 指示日
            oci_bind_by_name($stid, ":souko_Code", $souko_code);           // 倉庫Ｃ

            // === 単数便、備考・特記　あり , 複数便　ありなら、 oci_bind_by_name を抜かす
            if (!empty($one_condition) && isset($one_condition) || !empty($five_multiple_sql_cut) && isset($five_multiple_sql_cut)) {
            } else {
                oci_bind_by_name($stid, ":unsou_code", $unsou_code);           // 運送Ｃ
            }

            oci_bind_by_name($stid, ":syouhin_Code", $Shouhin_code);       // 商品Ｃ

            //          dprint("<br><br>");
            //          dprint($sql);

            oci_execute($stid);

            oci_free_statement($stid);

            // 処理SEQ
            $sql_seq = "SELECT 処理ＳＥＱ, 伝票ＳＥＱ, 伝票番号, 伝票行番号,
             伝票行枝番,商品Ｃ,倉庫Ｃ,出荷元,特記事項,出荷予定数量 FROM HTPK WHERE 処理ＳＥＱ = :get_Syori_SEQ";

            $stid_syori_seq = oci_parse($conn, $sql_seq);
            if (!$stid_syori_seq) {
                $e = oci_error($stid_syori_seq);
                echo htmlentities($e['message']);
            }

            oci_bind_by_name($stid_syori_seq, ":get_Syori_SEQ", $syori_SEQ_value);

            //  dprint($sql_seq);
            oci_execute($stid_syori_seq);

            $arr_Insert_Picking = array();
            $Syuka_Yotei_SUM_NUM = 0; // 出荷数量　合計

            $arr_Denpyou_SEQ = []; // 伝票SEQ 用
            $arr_Syukka_Yotei_Num = [];   // 出荷予定数量
            $arr_Shouhin_Code = [];

            $idx = 0;
            while ($row = oci_fetch_assoc($stid_syori_seq)) {
                // カラム名を指定して値を取得
                $IN_Dennpyou_SEQ = $row['伝票ＳＥＱ'];
                $IN_Dennpyou_num = $row['伝票番号'];
                $IN_Dennpyou_Gyou_num = $row['伝票行番号'];
                $IN_Dennpyou_Eda_num = $row['伝票行枝番'];
                $IN_Syouhin_Code = $row['商品Ｃ'];
                $IN_Souko_Code = $row['倉庫Ｃ'];
                $IN_Syukamoto = $row['出荷元'];
                $IN_Tokki_zikou = $row['特記事項'];
                $IN_Syukka_Num = $row['出荷予定数量'];

                // 出荷予定 合計
                $Syuka_Yotei_SUM_NUM = $Syuka_Yotei_SUM_NUM + $IN_Syukka_Num;
                // 伝票 SEQ
                $arr_Denpyou_SEQ[$idx] = $IN_Dennpyou_SEQ;
                // 出荷予定数量
                $arr_Syukka_Yotei_Num[$idx] = $IN_Syukka_Num;
                // 商品コード 
                $arr_Shouhin_Code[$idx] = $IN_Syouhin_Code;

                /*
                dprint("<br>");
                dprint("データ:" . $idx . "件");
                dprint("伝票ＳＥＱ:::" . $IN_Dennpyou_SEQ . "<br />");
                dprint("伝票番号:::" . $IN_Dennpyou_num . "<br />");
                dprint("伝票行番号:::" . $IN_Dennpyou_Gyou_num . "<br />");
                dprint("伝票行枝番:::" . $IN_Dennpyou_Eda_num . "<br />");
                dprint("商品Ｃ:::" . $IN_Syouhin_Code . "<br />");
                dprint("倉庫Ｃ:::" . $IN_Souko_Code . "<br />");
                dprint("出荷元:::" . $IN_Syukamoto . "<br />");
                dprint("特記事項:::" . $IN_Tokki_zikou . "<br />");
                dprint("出荷予定数量:::" . $IN_Syukka_Num  . "<br />");
                dprint("<br /><br />");
                */

                $idx = $idx + 1;
            }

            // 処理 SEQ
            $In_Syori_SEQ = $syori_SEQ_value;
            // 伝票 SEQ
            $Strs_Denpyou_SEQ = implode(',', $arr_Denpyou_SEQ);
            // 出荷予定数量
            $Strs_Syukka_Yotei_Num = implode(',', $arr_Syukka_Yotei_Num);
            // 商品コード
            $strs_Shouhin_Code = implode(',', $arr_Shouhin_Code);
        } else if (isset($_GET['four_status']) && $_GET['four_status'] = 'default_root') {

            // ===============================================================================
            //                                  【通常】 処理
            // ===============================================================================

            $sql = $sql_ins_HTPK;

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
                echo htmlentities($e['message']);
            }

            // 処理開始日時
            $syori_start_datetime = date('Y-m-d H:i:s');
            // 登録日
            $touroku_day = date('Y-m-d H:i:s');

            $tantou_day = date('Y-m-d');

            // === 担当者
            $input_tantou = $input_login_id;
            // === 識別名
            // PC名は取得できないので、担当者Ｃ_日付
            $tanmatu_id = $input_tantou . "_" . $tantou_day;

            oci_bind_by_name($stid, ":syori_SEQ", $syori_SEQ_value);       // 処理 SEQ
            oci_bind_by_name($stid, ":input_tantou", $input_tantou);       // 担当 
            oci_bind_by_name($stid, ':start_time', $syori_start_datetime); // 処理開始日時
            oci_bind_by_name($stid, ':touroku_date', $touroku_day);        // 登録日
            oci_bind_by_name($stid, ":tanmatu_id", $tanmatu_id);           // 端末

            oci_bind_by_name($stid, ":select_day", $select_day);           // 指示日
            oci_bind_by_name($stid, ":souko_Code", $souko_code);           // 倉庫Ｃ
            oci_bind_by_name($stid, ":unsou_code", $unsou_code);           // 運送Ｃ
            oci_bind_by_name($stid, ":syouhin_Code", $Shouhin_code);       // 商品Ｃ

            oci_execute($stid);

            oci_free_statement($stid);

            // 処理SEQ
            $sql_seq = "SELECT 処理ＳＥＱ, 伝票ＳＥＱ, 伝票番号, 伝票行番号,
             伝票行枝番,商品Ｃ,倉庫Ｃ,出荷元,特記事項,出荷予定数量 FROM HTPK WHERE 処理ＳＥＱ = :get_Syori_SEQ";

            $stid_syori_seq = oci_parse($conn, $sql_seq);
            if (!$stid_syori_seq) {
                $e = oci_error($stid_syori_seq);
                echo htmlentities($e['message']);
            }

            oci_bind_by_name($stid_syori_seq, ":get_Syori_SEQ", $syori_SEQ_value);

            //  dprint($sql_seq);
            oci_execute($stid_syori_seq);

            $arr_Insert_Picking = array();
            $Syuka_Yotei_SUM_NUM = 0; // 出荷数量　合計

            $arr_Denpyou_SEQ = []; // 伝票SEQ 用
            $arr_Syukka_Yotei_Num = [];   // 出荷予定数量
            $arr_Shouhin_Code = [];

            $idx = 0;
            while ($row = oci_fetch_assoc($stid_syori_seq)) {
                // カラム名を指定して値を取得
                $IN_Dennpyou_SEQ = $row['伝票ＳＥＱ'];
                $IN_Dennpyou_num = $row['伝票番号'];
                $IN_Dennpyou_Gyou_num = $row['伝票行番号'];
                $IN_Dennpyou_Eda_num = $row['伝票行枝番'];
                $IN_Syouhin_Code = $row['商品Ｃ'];
                $IN_Souko_Code = $row['倉庫Ｃ'];
                $IN_Syukamoto = $row['出荷元'];
                $IN_Tokki_zikou = $row['特記事項'];
                $IN_Syukka_Num = $row['出荷予定数量'];

                // 出荷予定 合計
                $Syuka_Yotei_SUM_NUM = $Syuka_Yotei_SUM_NUM + $IN_Syukka_Num;
                // 伝票 SEQ
                $arr_Denpyou_SEQ[$idx] = $IN_Dennpyou_SEQ;
                // 出荷予定数量
                $arr_Syukka_Yotei_Num[$idx] = $IN_Syukka_Num;
                // 商品コード 
                $arr_Shouhin_Code[$idx] = $IN_Syouhin_Code;

                /*
                dprint("<br>");
                dprint("データ:" . $idx . "件");
                dprint("伝票ＳＥＱ:::" . $IN_Dennpyou_SEQ . "<br />");
                dprint("伝票番号:::" . $IN_Dennpyou_num . "<br />");
                dprint("伝票行番号:::" . $IN_Dennpyou_Gyou_num . "<br />");
                dprint("伝票行枝番:::" . $IN_Dennpyou_Eda_num . "<br />");
                dprint("商品Ｃ:::" . $IN_Syouhin_Code . "<br />");
                dprint("倉庫Ｃ:::" . $IN_Souko_Code . "<br />");
                dprint("出荷元:::" . $IN_Syukamoto . "<br />");
                dprint("特記事項:::" . $IN_Tokki_zikou . "<br />");
                dprint("出荷予定数量:::" . $IN_Syukka_Num  . "<br />");
                dprint("<br /><br />");
                */

                $idx = $idx + 1;
            }

            // 処理 SEQ
            $In_Syori_SEQ = $syori_SEQ_value;
            // 伝票 SEQ
            $Strs_Denpyou_SEQ = implode(',', $arr_Denpyou_SEQ);
            // 出荷予定数量
            $Strs_Syukka_Yotei_Num = implode(',', $arr_Syukka_Yotei_Num);
            // 商品コード
            $strs_Shouhin_Code = implode(',', $arr_Shouhin_Code);

            //            oci_free_statement($stid_syori_seq);

            dprint("通常");
            /**
            // === 運送便（単数）
            $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SL.商品Ｃ,SH.品名
      ,RZ.棚番,SH.梱包入数,SUM(SL.数量) AS 数量,SUM(PK.ピッキング数量) AS ピッキング数量
      ,PK.処理Ｆ,SH.ＪＡＮ,SK.特記事項
      ,RZ.在庫数量 
      ,SL.伝票ＳＥＱ,SL.伝票番号,SL.伝票行番号,SL.伝票行枝番
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
   AND SJ.出荷日 = :select_day
   AND SL.倉庫Ｃ = :souko_Code
   AND SJ.運送Ｃ = :unsou_code
   AND RZ.倉庫Ｃ = :souko_Code_02
   AND SL.商品Ｃ = :syouhin_Code
   AND DECODE(NULL,PK.処理Ｆ,0) <> 9
 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名 
,SL.商品Ｃ,SH.品名,PK.処理Ｆ,RZ.棚番,SH.梱包入数,SH.ＪＡＮ,SK.特記事項,RZ.在庫数量,SL.伝票ＳＥＱ,SL.伝票ＳＥＱ,SL.伝票番号,SL.伝票行番号,SL.伝票行枝番,SL.伝票行番号,SL.伝票行枝番
 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SM.出荷元名,SL.商品Ｃ,SL.出荷元,SK.特記事項";
            $stid = oci_parse(
                $conn,
                $sql
            );
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":select_day", $select_day);
            oci_bind_by_name($stid, ":souko_Code", $souko_code);
            oci_bind_by_name($stid, ":unsou_code", $unsou_code);
            oci_bind_by_name($stid, ":souko_Code_02", $souko_code);
            oci_bind_by_name($stid, ":syouhin_Code", $Shouhin_code);

            oci_execute($stid);
             **/
        }

        /**
        $arr_Select_Picking = array();
        while ($row = oci_fetch_assoc($stid)) {
            // カラム名を指定して値を取得                 index 
            $IN_Dennpyou_SEQ = $row['伝票ＳＥＱ'];      // 0 
            $IN_Dennpyou_num = $row['伝票番号'];        // 1
            $IN_Dennpyou_Gyou_num = $row['伝票行番号']; // 2
            $IN_Dennpyou_Eda_num = $row['伝票行枝番'];  // 3
            $IN_Syouhin_Code = $row['商品Ｃ'];          // 4 
            $IN_Souko_Code = $row['倉庫Ｃ'];            // 5
            $IN_Syukamoto = $row['出荷元'];             // 6
            $IN_Syuka_Yotei_num = $row['数量'];        //  7
            $IN_Tokki_zikou = $row['特記事項'];        //  8
            $IN_Shouhin_JAN = $row['ＪＡＮ'];          // 9
            $IN_Zaiko_Num = $row['在庫数量'];          // 10

            dprint("<br><br>");
            dprint("伝票ＳＥＱ:::" . $IN_Dennpyou_SEQ . "<br />");
            dprint("伝票番号:::" . $IN_Dennpyou_num . "<br />");
            dprint("伝票行番号:::" . $IN_Dennpyou_Gyou_num . "<br />");
            dprint("伝票行枝番:::" . $IN_Dennpyou_Eda_num . "<br />");
            dprint("商品Ｃ:::" . $IN_Syouhin_Code . "<br />");
            dprint("倉庫Ｃ:::" . $IN_Souko_Code . "<br />");
            dprint("出荷元:::" . $IN_Syukamoto . "<br />");
            dprint("数量:::" . $IN_Syuka_Yotei_num . "<br />");
            dprint("特記事項:::" . $IN_Tokki_zikou . "<br />");
            dprint("ＪＡＮ:::" . $IN_Shouhin_JAN  . "<br />");
            dprint("在庫数量:::" . $IN_Zaiko_Num  . "<br />");
        }

        oci_free_statement($stid);
         **/
        //+++++++++++++++++++++++++
        // 得意先名取得
        $stid_TkNm = oci_parse($conn, $sql_Sel_TkNm);
        if (!$stid_TkNm) {
            $e = oci_error($conn);
            // エラーハンドリングを行う
        }

        oci_bind_by_name($stid_TkNm, ":syori_SEQ_value", $syori_SEQ_value); // 処理ＳＥＱ
        oci_bind_by_name($stid_TkNm, ":select_day", $select_day);           // 指示日
        oci_bind_by_name($stid_TkNm, ":souko_Code", $souko_code);           // 倉庫Ｃ
        oci_bind_by_name($stid_TkNm, ":syouhin_Code", $Shouhin_code);       // 商品Ｃ
        if ($flg_TkNm_Unso = 1) {
            //運送Ｃ指定有り
            //oci_bind_by_name($stid_TkNm, ":unsou_code", $unsou_code);         // 運送Ｃ
        }
        /*
    dprint("<br>");
    dprint("********** SQL >>>");
    dprint("<br>");
    print_r($sql_Sel_TkNm);
    dprint("<br>");
*/
        dprint($syori_SEQ_value . "<br>"); // 処理ＳＥＱ
        dprint($select_day . "<br>");           // 指示日
        dprint($souko_code . "<br>");           // 倉庫Ｃ
        dprint($Shouhin_code . "<br>");       // 商品Ｃ

        dprint("********** SQL <<<");
        dprint("<br>");

        $result_TkNm = oci_execute($stid_TkNm);
        if (!$result_TkNm) {
            $e = oci_error($stid_TkNm);
            // エラーハンドリングを行う
        }

        //$new_TkNm = oci_fetch_assoc($stid_TkNm);

        $tokuimei = "";

        while ($row = oci_fetch_assoc($stid_TkNm)) {
            // カラム名を指定して値を取得
            $tokuimei .= $row['集計得意先名'];
            $tokuimei .= " × ";
            $tokuimei .= $row['CNT'];
            $tokuimei .= "<br>";
        }

        //          $tokuimei = $new_TkNm['集計得意先名'];

        oci_free_statement($stid_TkNm);
        /*
    dprint("<br>");
    dprint("***************************************************************");
    dprint("<br>");
    dprint($tokuimei);
    dprint("<br>");
    dprint("***************************************************************");
    dprint("<br>");
*/
        //+++++++++++++++++++++++++

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

    <link href="./css/all.css" rel="stylesheet">
    <!-- Bootstrap CSS 読み込み -->
    <link rel="stylesheet" href="./css/bootstrap.min.css">

    <title>ピッキング</title>

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
                <a href="#"><img src="./img/home_img.png"></a>
            </span>

            <span class="App_name">
                グリーンライフ ピッキング
            </span>
        </div>
    </div> <!-- ===============  head_box END =============== -->


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <a href="#"><img src="./img/page_img.png"></a>
            </span>

            <span class="page_title">
                ピッキング数量入力画面
            </span>
        </div>
    </div> <!-- ===============  head_box_02 END =============== -->


    <div class="container_detail">
        <div class="content_detail">

            <div class="detail_item_01_box">
                <p><span class="detail_midashi">ロケ：</span>

                    <?php if (isset($_GET['one_op_tana_num'])) : ?>
                        <?php print $_GET['one_op_tana_num']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[0]; ?>
                    <?php endif; ?>
                </p>
                <p><span class="detail_midashi">在庫数：</span>
                    <?php if (isset($_GET['four_status']) && $_GET['four_status'] == 'default_root') : ?>
                        <?php  ?>
                    <?php else : ?>
                        <?php print $zaikosu; ?>
                    <?php endif; ?>
                </p>
            </div>

            <p class="detail_item_02">
                <span class="detail_midashi">品名：</span>

                <?php if (isset($_GET['shouhin_name'])) : ?>
                    <?php print $_GET['shouhin_name']; ?>
                <?php else : ?>
                    <?php print wordwrap($Shouhin_Detail_DATA[2]); ?>
                <?php endif; ?>
            </p>

            <p class="detail_item_03">
                <span class="detail_midashi">品番：</span>
                <?php if (isset($_GET['one_op_hinban'])) : ?>
                    <?php print $_GET['one_op_hinban']; ?>
                <?php else : ?>
                    <?php print $Shouhin_name_part2; ?>
                <?php endif; ?>
            </p>

            <p class="detail_item_04">
                <span class="detail_midashi">JAN：</span>

                <span id="detail_var_code">
                    <?php if (isset($_GET['shouhin_jan'])) : ?>
                        <?php print $_GET['shouhin_jan']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[4]; ?>
                    <?php endif; ?>
                </span>

                <span id="detail_data_code">
                    <?php if (isset($_GET['shouhin_code'])) : ?>
                        <?php print $_GET['shouhin_code']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[5]; ?>
                    <?php endif; ?>
                </span>
            <div class="cp_iptxt_02">
                <label class="ef_02">
                    <input type="number" id="scan_val" name="scan_val" placeholder="Scan JAN">
                </label>
            </div>

            <span id="result_val"></span>
            </p>

            <p>

            </p>

            <div class="detail_item_05_box">
                <p><span class="detail_midashi" id="detail_num_box">数量：</span><span id="suuryou_num">
                        <?php if (isset($_GET['one_op_suuryou_num'])) : ?>
                            <?php print $_GET['one_op_suuryou_num']; ?>
                        <?php else : ?>
                            <?php print $Shouhin_Detail_DATA[6]; ?>
                        <?php endif; ?>
                    </span>
                </p>
                <p><span class="detail_midashi">ケース：</span>
                    <?php if (isset($_GET['one_op_case_num'])) : ?>
                        <?php print $_GET['one_op_case_num']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[7]; ?>
                    <?php endif; ?>
                </p>
                <p><span class="detail_midashi">バラ：</span>
                    <?php if (isset($_GET['one_op_bara_num'])) : ?>
                        <?php print $_GET['one_op_bara_num']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[8]; ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="cp_iptxt">
                <!-- 
                <span class="detail_midashi">カウント：</span>
    -->

                <?php if (!empty($scan_b)) :  ?>
                    <?php $count_num = 1; ?>
                <?php else : ?>
                    <?php if (isset($Syuka_Yotei_SUM_NUM)) : ?>
                        <?php $count_num = $Syuka_Yotei_SUM_NUM; ?>
                    <?php else : ?>
                        <?php if (isset($count_num)) : ?>
                            <?php print $count_num  ?>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php endif; ?>

                <input type="number" class="ef" name="count_num" id="count_num" value="<?php echo
                                                                                        isset($_GET['one_op_count_num']) ? htmlspecialchars($_GET['one_op_count_num'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($count_num, ENT_QUOTES, 'UTF-8'); ?>"> <label>カウント</label>
                <span class="focus_line"></span>
            </div>

            <p class="detail_item_07">
                <span class="detail_midashi">備考：
                    <?php if (isset($_GET['shipping_moto_name'])) : ?>
                        <?php print $_GET['shipping_moto_name']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[10]; ?>
                    <?php endif; ?>
                </span>
            </p>

            <p class="detail_item_08">
                <span class="detail_midashi">特記：
                    <?php if (isset($_GET['tokki_zikou'])) : ?>
                        <?php print $_GET['tokki_zikou']; ?>
                    <?php elseif (isset($_GET['one_op_tokki'])) : ?>
                        <?php print $_GET['one_op_tokki']; ?>
                    <?php else : ?>
                        <?php print $Shouhin_Detail_DATA[12]; ?>
                    <?php endif; ?>
                </span>

            </p>

            <p class="detail_item_09">
                <span class="detail_midashi">
                    運送便：
                </span>

                <?php if (isset($_GET['unsou_name'])) : ?>
                    <?php print $_GET['unsou_name']; ?>
                <?php else : ?>
                    <?php print $Shouhin_Detail_DATA[11]; ?>
                <?php endif; ?>
                <br />

            </p>

            <!-- 得意先 -->
            <p class="detail_item_10">
                <span class="detail_midashi">得意先：</span>
                <?php print $tokuimei; ?>
            </p>

        </div>

    </div> <!-- ===============  container_detail END =============== -->



    <!-- モーダル 「戻る 用」 -->
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

    <!-- ====================================================================================== -->
    <!-- =============================== 全数完了　用　モーダル ================================== -->
    <!-- ====================================================================================== -->
    <!-- 完了メッセージ用のモーダル -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">完了</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    全数処理が完了しました。
                </div>
            </div>
        </div>
    </div>

    <!-- エラーメッセージ用のモーダル -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">エラー</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    処理中にエラーが発生しました。
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================================== -->
    <!-- =============================== 全数完了　用　モーダル END ================================== -->
    <!-- ====================================================================================== -->

    <!-- フッターメニュー -->
    <footer class="footer-menu_02">
        <ul>


            <?php if (isset($one_condition) &&  $one_condition != "") : ?>
                <input type="hidden" name="back_sql_one_tokki" id="back_sql_one_tokki" value="<?php print $one_condition; ?>">
            <?php endif; ?>

            <?php if (isset($sql_multiple_condition) && $sql_multiple_condition != "") : ?>
                <input type="hidden" name="back_now_sql_multiple" id="back_now_sql_multiple" value="<?php print $sql_multiple_condition; ?>">
            <?php endif; ?>


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

                    <!-- 伝票 SEQ -->
                    <input type="hidden" name="IN_Denpyou_SEQ" id="IN_Denpyou_SEQ" value="<?php print $syori_SEQ_value; ?>">

                    <input type="hidden" name="Kakutei_Btn_Flg" id="Kakutei_Btn_Flg" value="">

                    <!-- 備考・特記 , & 複数処理 -->
                    <?php if (isset($_GET['now_sql'])) : ?>
                        <input type="hidden" name="one_now_sql_kakutei" id="one_now_sql_kakutei" value="<?php dprint($_GET['now_sql']); ?>">
                    <?php endif; ?>

                    <!-- 運送便 複数 , 特記・備考 （複数） -->
                    <?php if (isset($_GET['now_sql_multiple'])) : ?>
                        <input type="hidden" name="now_sql_multiple_kakutei" id="now_sql_multiple_kakutei" value="<?php dprint($_GET['now_sql_multiple']); ?>">
                    <?php endif; ?>

                    <button type="submit" name="kakutei_btn" id="kakutei_btn">確定</button>

                </form>

                <!-- 
                <a href="./four.php?test=1">確定</a>
                -->

            </li>

            <li>
                <!-- 
                <a href="#">全数完了</a>
                -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET" name="all_completed_post" id="all_completed_post">
                    <input type="hidden" name="unsou_code" value="<?php print $unsou_code; ?>">
                    <input type="hidden" name="unsou_name" value="<?php print $unsou_name; ?>">
                    <input type="hidden" name="day" value="<?php print $select_day; ?>">
                    <input type="hidden" name="souko" value="<?php print $souko_code; ?>">
                    <input type="hidden" name="shouhin_code" value="<?php print $Shouhin_code; ?>">
                    <input type="hidden" name="shouhin_name" value="<?php print $Shouhin_name; ?>">
                    <input type="hidden" name="shouhin_num" value="<?php print $Shouhin_num; ?>">
                    <input type="hidden" name="shipping_moto_name" value="<?php print $shipping_moto_name; ?>">
                    <input type="hidden" name="get_souko_name" value="<?php print $get_souko_name; ?>">


                    <!-- 品番 -->
                    <input type="hidden" name="one_op_hinban" value="<?php print $Shouhin_name_part2; ?>">

                    <input type="hidden" name="one_op_count_num" value="<?php print $count_num; ?>">
                    <!-- 数量 -->
                    <input type="hidden" name="one_op_suuryou_num" value="<?php print $Shouhin_Detail_DATA[6];; ?>">
                    <!-- ロケ -->
                    <input type="hidden" name="one_op_tana_num" value="<?php print $Shouhin_Detail_DATA[0];; ?>">
                    <!-- ケース数 -->
                    <input type="hidden" name="one_op_case_num" value="<?php print $Shouhin_Detail_DATA[7];; ?>">
                    <!-- バラ数 -->
                    <input type="hidden" name="one_op_bara_num" value="<?php print $Shouhin_Detail_DATA[8]; ?>">

                    <!-- JAN -->
                    <input type="hidden" name="shouhin_jan" value="<?php print $Shouhin_Detail_DATA[4]; ?>">
                    <!-- 特記 -->
                    <input type="hidden" name="one_op_tokki" value="<?php print $Shouhin_Detail_DATA[12]; ?>">
                    <!-- 備考 -->
                    <input type="hidden" name="one_op_bikou" value="<?php print $Shouhin_Detail_DATA[10]; ?>">

                    <!-- ============== 全数選択で、配列に戻して使う ============= -->
                    <!-- 処理ＳＥＱ  -->
                    <input type="hidden" name="one_option_Syori_SEQ" value="<?php print $syori_SEQ_value; ?>">
                    <!-- 伝票ＳＥＱ  -->
                    <input type="hidden" name="one_option_Denpyou_SEQ" value="<?php print $Strs_Denpyou_SEQ; ?>">
                    <!-- 出荷予定数量 -->
                    <input type="hidden" name="one_option_Syukka_Yotei_Num" value="<?php print $Strs_Syukka_Yotei_Num; ?>">
                    <!-- 商品コード -->
                    <input type="hidden" name="one_op_shouhin_code" value="<?php print $strs_Shouhin_Code; ?>">
                    <!-- ============== 全数選択で、配列に戻して使う END ============= -->

                    <!-- 備考・特記 , & 複数処理 -->
                    <?php if (isset($_GET['now_sql']) && $_GET['now_sql'] != "") : ?>
                        <input type="hidden" name="one_now_sql_zensuu" id="one_now_sql_zensuu" value="<?php echo ($_GET['now_sql']); ?>">

                        <!-- 通常処理 （運送便 単数） -->
                    <?php elseif (isset($_GET['four_status']) && $_GET['four_status'] == 'default_root') : ?>
                        <input type="hidden" name="default_root_sql_zensuu" id="default_root_sql_zensuu" value="<?php echo ($_SESSION['four_five_default_SQL']); ?>">

                        <!-- 複数運送便 -->
                    <?php elseif (isset($_GET['four_status']) && $_GET['four_status'] == 'multiple_sql_four') : ?>
                        <input type="hidden" name="multiple_sql_four_sql_zensuu" id="multiple_sql_four_sql_zensuu" value="<?php echo ($_SESSION['multiple_sql']); ?>">
                    <?php endif; ?>


                    <input type="hidden" name="ZEN_SUU_VAL" id="ZEN_SUU_VAL" value="">

                    <button type="submit" name="all_completed_button" id="all_completed_button">全数完了</button>
                </form>


            </li>
        </ul>
    </footer>



    <script src="./js/jquery-3.2.1.slim.min.js"></script>
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js//popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>

    <script type="text/javascript">
        // F5キーのリロードを防止
        // 一旦コメントアウト
        document.addEventListener("keydown", function(event) {
            if (event.keyCode === 116 || (event.ctrlKey && event.keyCode === 82)) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    </script>

    <script>
        $(document).ready(function() {

            function convertToHalfWidth(input) {
                return input.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                    return String.fromCharCode(s.charCodeAt(0) - 65248); // 全角文字のUnicode値から半角文字に変換
                });
            }


            // ========= モーダル処理 表示　「確定」
            function showModal() {
                $('#myModal').css('display', 'block');
            }

            // ========= モーダル処理 表示　「確定」
            function hideModal() {
                $('#myModal').css('display', 'none');
            }

            // ========= モーダル処理 表示　「戻る」
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

                var Jan_Val = $("#detail_var_code").text().trim();
                var input_JAN = $('#scan_val').val().trim();
                var convertedValue_JAN = convertToHalfWidth(input_JAN);
                var converte_JAN_Val = convertToHalfWidth(Jan_Val);

                if (convertedValue_JAN == converte_JAN_Val) {
                    var current_count = parseInt($("#count_num").val(), 10); // 数値として取得
                    $("#count_num").val(current_count + 1);
                    $("#scan_val").focus();
                    $("#scan_val").val("");
                    $("#result_val").text("OK:::" + convertedValue_JAN + "Jan_Val::" + Jan_Val);
                } else {
                    $("#result_val").text("NG:::" + convertedValue_JAN + "Jan_Val::" + Jan_Val);
                    $("#scan_val").focus();
                    $("#scan_val").val("");
                }

            });

            // ======================= JANコード　カウントアップ


            // ======================= 「戻る」ボタン

            /*
            $('#five_back_btn').on('click', function() {
                console.log("戻るボタン_click");
                window.location.href = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + select_day + '&souko=' + souko_code + '&get_souko_name=' + souko_name;
            });
            */




            // ********* 「確定ボタン」処理 *********
            /*
            $('#kakutei_btn').on('click', function() {

                var suuryou_num = $('#suuryou_num').text();
                var count_num = $('#count_num').val();

                console.log("数量:" + suuryou_num);
                console.log("カウント:" + count_num);

                // === 「数量」「カウント」比較　100 => 残 ,200 => 通常処理
                if (suuryou_num > count_num) {
                    console.log("残");
                    $('#Kakutei_Btn_Flg').val(100);
                    $('#count_num_val').val(count_num);

                    $('#kakutei_btn').submit();

                } else {
                    console.log("NO-残");
                    $('#Kakutei_Btn_Flg').val(200);
                    $('#count_num_val').val(count_num);

                    $('#kakutei_btn').submit();
                }

            });
            */

            // === モーダル「確定」

            // ********* 「全数完了ボタン」処理 *********

            /*
            $('#all_completed_button').on('click', function() {
                //$("#count_num").focus();

                //  event.preventDefault();

                var suuryou_num = $('#suuryou_num').text();
                var tmp = $("#count_num").val();
                var count_num = tmp;

                $('#ZEN_SUU_VAL').val(suuryou_num);


                //count_num =suuryou_num;
                console.log(tmp);
                console.log("数量:" + suuryou_num);
                console.log("カウント:" + count_num);
                console.log("出力:値:" + $('#ZEN_SUU_VAL').val());
                //  return false;
                $('#ZEN_SUU_VAL').submit();

            });

            */

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