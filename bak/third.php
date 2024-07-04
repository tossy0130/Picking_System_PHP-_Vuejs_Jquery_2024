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

    // ========= 通常処理 =========
    if (isset($_GET['selectedSouko'])) {
        $selectedSouko = $_GET['selectedSouko'];
        $selected_day = $_GET['selected_day'];
        $get_souko_name = $_GET['souko_name'];

        //  dprintBR($get_souko_name . ":" . "get_souko_name");

        $_SESSION['soko_name'] = $get_souko_name;

        // === ********* four.php での　状態判別用　セッション削除 *********
        // セッション変数を削除する

        // 単数 , 特記・備考
        unset($_SESSION['forth_pattern']); // four.php, five.php 状態判別用
        unset($_SESSION['selectedToki_Code']);

        // 複数
        unset($_SESSION['back_multiple_sql']);
        unset($_SESSION['fukusuu_select']); // four.php, five.php 状態判別用
        unset($_SESSION['fukusuu_unsouo_num']);
        unset($_SESSION['fukusuu_select_val']);
        unset($_SESSION['back_multiple_sql']);


        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }
        /* 24/06/21 ピッキング処理済の運送便は表示させない
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称 AS 倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,
                       SM.出荷元名,SK.特記事項
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US
                 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                   AND SK.伝票行番号 = SL.伝票行番号
                   AND SL.倉庫Ｃ = SO.倉庫Ｃ
                   AND SL.出荷元 = SM.出荷元Ｃ(+)
                   AND SJ.運送Ｃ = US.運送Ｃ
                   AND SJ.出荷日 = :GET_DATE
                   AND SL.倉庫Ｃ = :GET_SOUKO
                 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
                 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SM.出荷元名 ,SK.特記事項";
*/
        $sql = "SELECT SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称 AS 倉庫名,SJ.運送Ｃ,US.運送略称,SL.出荷元,
                       SM.出荷元名,SK.特記事項
                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US, HTPK PK
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
                   AND NVL(PK.処理Ｆ,0) <> 9
                   AND SJ.出荷日 = :GET_DATE
                   AND SL.倉庫Ｃ = :GET_SOUKO
                 GROUP BY SJ.出荷日,SL.倉庫Ｃ,SO.倉庫略称,SJ.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
                 ORDER BY SL.倉庫Ｃ,SJ.運送Ｃ,SL.出荷元,SM.出荷元名 ,SK.特記事項";

        $stid = oci_parse($conn, $sql);
        if (!$stid) {
            $e = oci_error($stid);
        }

        oci_bind_by_name($stid, ":GET_DATE", $selected_day);
        oci_bind_by_name($stid, ":GET_SOUKO", $selectedSouko);

        oci_execute($stid);

        // 結果を取得して表示
        $data = array();
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
    } else {
    }
}


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/third.css">

    <link rel="stylesheet" href="./css/second_02.css">

    <link href="./css/all.css" rel="stylesheet">

    <title>運送便選択</title>


    <style>
        .show {
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
    </div>


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <a href="#"><img src="./img/page_img.png"></a>
            </span>

            <span class="page_title">
                運送便 選択
            </span>
        </div>
    </div>

    <div id="app">

        <div class="container">
            <div class="content_03">

                <div class="souko_box_v">
                    <?php
                    $idx = 1;
                    //ピッキング未処理対応 $arr_Unsou_data 24/06/21
                    if (isset($arr_Unsou_data)) {
                        foreach ($arr_Unsou_data as $row) {
                            echo '<div class="dropdown_v" data-menuid="' . $idx . '">';
                            echo '<button class="dropbtn_v" value="' . $row["Unsou_code"] . '" data-unsou-code="' . $row["Unsou_code"] . '" data-unsou-name="' . $row["Unsou_name"] . '">' . $row["Unsou_name"] . '</button>';
                            echo '<button class="dropdown-toggle_v" data-menuid="' . $idx . '">&#9660;</button>'; // ドロップダウンアイコンボタンを追加
                            echo '<div class="dropdown-content_v" data-menuid="' . $idx . '">';
                            foreach ($row["details"] as $detail) {
                                // 備考が空の場合
                                if ($detail["shipping_moto_name"] == null) {
                                    $detail["shipping_moto_name"] = "-";
                                    $detail["shipping_moto"] = "-";
                                }

                                // 特記事項が空の場合
                                if ($detail["tokki_zikou"] == null) {
                                    $detail["tokki_zikou"] = "---";
                                }


                                echo '<button type="button" data-company="' . $detail["shipping_moto_name"] .
                                    '" data-value="' . $detail["shipping_moto"] . '" data-tokki="' . $detail["tokki_zikou"] . '">' . $detail["shipping_moto_name"]  .  ' ' . $detail["tokki_zikou"] . '</button>';
                            }
                            echo '</div></div>';
                            $idx++;
                        }
                    } else {
                        echo 'ピッキング未処理の運送便はありません';
                    }
                    ?>
                </div>

            </div>

            <!-- 選択した値を表示する部分 -->


            <!--
            <div class="selected-value">    
                特記・備考: <span id="selectedToki_Code"></span><br>
            </div>
                -->

            <hr>

            <!-- 選択した値表示 （備考・特記あり） -->

            <div id="selectedValues_set_next_val_View_Tan" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi_01" style="display:block;">■選択(単数選択)</span>

                <span class="single_select_title_0">運送コード（単数）</span> <span id="selectedUnsouCode"></span><br>
                <span class="single_select_title">運送会社（単数）</span><span id="selectedUnsouName"></span><br>
                <span class="single_select_title_2"> 特記・備考 絞り込み（単数）</span> <br /><span id="selectedToki_Code"></span><br>
            </div>

            <!--
            <div id="selectedValues_set_next_val_View_Tan" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi" style="display:block;">■選択(単数便)</span>
            </div>
                -->

            <div class="fukusuu_select_views">
                <!-- 選択した値表示 運送便　複数 -->
                <div id="selectedValues_set_next_val_View_UNSOU" style="margin: 15px 0 25px 0;">
                    <span class="third_view_midashi" style="display:block;">■選択(複数条件, 運送便 )</span>
                    <span class="single_select_title_0">運送コード（複数）</span><span id="fukusuu_select"></span><br />
                    <span class="single_select_title">運送会社（複数）</span><span id="fukusuu_select_unsou"></span><br />
                </div>


                <!-- 選択した値表示 （備考・特記あり） -->
                <div class="selected-value" id="selectedValues_set_next_val" style="margin: 15px 0 25px 0;">
                    <span class="third_view_midashi" style="display:block;">■選択(複数条件, 備考・特記)</span>
                    <span class="single_select_title_2">備考・特記 絞り込み</span>
                </div>
            </div>

            <!-- 
            <div id="selectedValues_set_next_val">
                <span style="display:inline-block;">■選択(複数条件)</span>
            </div>
                -->
            <!--
            <div id="selectedValues_set_next_val_View" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi" style="display:block;">■選択(複数条件, 備考・特記)</span>
            </div>
                -->

            <!--
            <div style="opacity: 0;">
                -->
            <div style="opacity: 0;">
                <span id="op_01" style="display: inline-block;">複数選択</span>

                <!-- 
                <span id="fukusuu_select">：</span><br />
                -->

            </div>

            <!--
            <div style="opacity: 0;">
                -->
            <div class="select_data_box">
                <span id="op_00">選択した運送会社名: </span><span id="selectedUnsouName_all"></span><br>

                <span id="op_02">複数選択（特記）</span><span id="fukusuu_select_option_01">：</span><br />
                <span id="op_03">複数選択名</span><span id="fukusuu_select_name">：</span><br /><br />

                <span id="op_04">複数選択:::</span><span id="f_select"></span>
            </div>


            <!-- 複数選択 -->
            <!--
            <div id="selectedValues_set_next_val" style="opacity: 0;">
                -->

            <!--
            <div id="selectedValues_set_next_val">
                <span style="display:inline-block;">■選択(複数条件)</span>
            </div>
                -->

            <p id="err_text" style="color:red;text-align:center;"></p>

            <!-- ================================================= -->
            <!-- ================     運送便 選択ボタン   ==================== -->
            <!-- ================================================= -->
            <div class="third_btn_flex_box">
                <div id="sendSelectedValues_box">
                    <button id="sendSelectedValues">単数選択</button>
                </div>

                <div id="sendSelectedValues_box">
                    <button id="sendSelectedValues_multiple">複数選択</button>
                </div>

                <div id="sendSelectedValues_box">
                    <button id="sendSelectedValues_clear">条件クリア</button>
                </div>
            </div>

            <!-- ================================================= -->
            <!-- ================     運送便 選択ボタン  END  ==================== -->
            <!-- ================================================= -->

        </div>
    </div> <!-- container END -->

    <!-- フッターメニュー -->
    <div>
        <footer class="footer-menu">
            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./second.php?selected_day=" . urlencode($selected_day) . "&back_flg=" . $back_flg; ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
                <li><a href="" id="Kousin_Btn">更新</a></li>
            </ul>
        </footer>
    </div>

    <script src="./js/jquery.min.js"></script>

    <script>
        (function($) {
            $(document).ready(function() {


                // =========================================
                // === ボタンの状態判別
                // =========================================
                function Button_Check() {
                    var text = $('#fukusuu_select').text().trim();
                    // 末尾のカンマを削除して分割
                    var values = text.replace(/,$/, '').split(',').filter(Boolean);
                    // 配列の長さを取得
                    var count = values.length;
                    // 同じ運送コードで複数対応
                    var count_set_next_val = $('#selectedValues_set_next_val .set_next_val').length;

                    // シングル　運送名
                    var selectedUnsouName = $('#selectedUnsouName').text();
                    // シングル　備考・特記
                    var selectedToki_Code = $('#selectedToki_Code').text();

                    if (count > 1 || count_set_next_val > 1 || (selectedUnsouName != "" && selectedToki_Code != "")) {
                        // if (count > 1 || count_set_next_val > 1) {
                        $('#sendSelectedValues').attr('disabled', true).css({
                            'background-color': '#ccc',
                            'cursor': 'not-allowed'
                        });; // 無効化

                        $('#sendSelectedValues_multiple').attr('disabled', false).css({
                            'background-color': '#3498db',
                            'cursor': 'pointer'
                        }); // 有効化

                        // エラーメッセージを非表示
                        $("#err_text").text("");

                        // === 表示エリア　表示　非表示
                        $('#selectedValues_set_next_val_View_UNSOU').show();
                        $('.selected-value').show();

                        $('.third_view_midashi').show();


                        $('#selectedValues_set_next_val_View_Tan').hide();

                        // ==================== 単層便  有効 =====================
                    } else {
                        $('#sendSelectedValues').attr('disabled', false).css({
                            'background-color': '#3498db',
                            'cursor': 'pointer'
                        });

                        $('#sendSelectedValues_multiple').attr('disabled', true).css({
                            'background-color': '#ccc',
                            'cursor': 'not-allowed'
                        });;

                        $("#err_text").text("");

                        // === 表示エリア　表示　非表示
                        $('#selectedValues_set_next_val_View_Tan').show();
                        $('#selectedValues_set_next_val_View_UNSOU').hide();
                        $('.selected-value').hide();

                    }

                } // =============================== END function


                // 重複を削除する関数
                function removeDuplicates(array) {
                    return array.filter((item, index) => array.indexOf(item) === index);
                }


                // =================================================
                // =============================  階層 1 ボタンの、運送便　重複削除処理
                // =================================================
                function removeOldValues_Button() {

                    var arr_unsou_Name = [];
                    $('#selectedValues_set_next_val_View_UNSOU .tansou_select_VAL').each(function() {
                        var unsou_Name = $(this).text();
                        arr_unsou_Name.push(unsou_Name);
                    });

                    //     console.log("複数セレクト:::" + arr_unsou_Name);
                    //     console.log("更新後の複数セレクト 備考・特記*****" + arr_unsou_Name_bikou_tokki);

                    // 重複する値を DOM から削除
                    $('#selectedValues_set_next_val_View .view_item_01_val').each(function() {
                        var unsou_Name_bikou_tokki = $(this).text();
                        if (arr_unsou_Name.includes(unsou_Name_bikou_tokki)) {
                            $(this).closest('.set_next_val_view').remove();
                        }
                    });

                } // =========== removeOldValues_Button

                // =================================================
                // =============================  １階層　上の　親ボタンを削除
                // =================================================
                function removeOldValues_SUB_Button() {

                    var arr_unsou_Name_bikou_tokki = [];
                    $('#selectedValues_set_next_val_View .view_item_01_val').each(function() {
                        var nsou_Name_bikou_tokki = $(this).text();
                        arr_unsou_Name_bikou_tokki.push(nsou_Name_bikou_tokki);
                    });



                    //     console.log("複数セレクト:::" + arr_unsou_Name);
                    //     console.log("更新後の複数セレクト 備考・特記*****" + arr_unsou_Name_bikou_tokki);

                    // 重複する値を DOM から削除
                    $('#selectedValues_set_next_val_View .tansou_select_VAL').each(function() {
                        var unsou_Name = $(this).text();
                        if (arr_unsou_Name_bikou_tokki.includes(unsou_Name)) {
                            $(this).closest('.tansou_select_VAL').remove();
                            $(this).css('background-color', '#6d6666').removeClass('color-changed');
                        }
                    });

                } // =========== removeOldValues_Button

                function Color_All() {

                    var valuesToMatch = [];

                    $('#selectedValues_set_next_val_View .tansou_select_VAL').each(function() {
                        valuesToMatch.push($(this).text().trim());
                    });

                    $('#selectedValues_set_next_val_View .view_item_01_val').each(function() {
                        valuesToMatch.push($(this).text().trim());
                    });

                    // .souko_box_v .dropbtn_v のボタンの値と一致するか確認し、一致するものの色を変える
                    $('.souko_box_v .dropbtn_v').each(function() {
                        var buttonText = $(this).text().trim();
                        if (valuesToMatch.includes(buttonText)) {
                            $(this).css('background-color', '#45a049').addClass('color-changed');
                        }
                    });

                }

                // =================================================================================================================
                // ==================================== ボタン処理内　で使う function  一覧 ===========================================   
                // =================================================================================================================         

                /*  
                    ＊＊＊ ドロップダウン　メニューの　開く、閉じる ＊＊＊
                */
                function Drop_Down_Menu_Open_Close(button) {
                    var menuId = $(button).data('menuid');
                    var dropdownContent = $('.dropdown-content_v[data-menuid=' + menuId + ']');

                    dropdownContent.toggleClass('show');
                    // 他のドロップダウンメニューを閉じる
                    $('.dropdown-content_v').not(dropdownContent).removeClass('show');
                }

                /*

                ※　単数用（備考・特記なし）　の処理も追加

                複数選択用 （単送便（複数用）） 運送便コード 、運送便名 「挿入」「削除」
                #fukusuu_select , #fukusuu_select_unsou

                */
                function Multiple_PUSH_DEL(val, class_name, unsouCode, unsouName) {
                    // ===================================== 　複数選択用 （単送便）処理 ====================
                    // ========= 複数選択用 （単送便（複数用）） 運送便コード
                    var fukusuuText = $('#fukusuu_select').text();
                    var newText;

                    // ========= 複数選択用 （単送便（複数用）） 運送便名
                    var fukusuu_select_unsou = $('#fukusuu_select_unsou').text().trim();
                    var newText_name;

                    // ======== 単数　表示用（運送コード）
                    var arr_Tansuu_unsou_Code = fukusuuText.split(',').filter(code => code.trim() !== '');
                    var arr_Tansuu_unsou_Name = fukusuu_select_unsou.split(',').filter(name => name.trim() !== '');

                    if (fukusuuText.includes(unsouCode)) {

                        // === 複数、運送便コード・運送便名　*** 処理 *** 同じ値を削除
                        newText = fukusuuText.replace(new RegExp(unsouCode + ',', 'g'), '');
                        newText_name = fukusuu_select_unsou.replace(new RegExp(unsouName + ',', 'g'), '');


                        // ============= 単層便　処理 ============
                        // 配列から削除 , 運送便コード
                        arr_Tansuu_unsou_Code = arr_Tansuu_unsou_Code.filter(code => code !== unsouCode);
                        $('#selectedUnsouCode').text(arr_Tansuu_unsou_Code[arr_Tansuu_unsou_Code.length - 1]);

                        // 配列から削除, 運送便名
                        arr_Tansuu_unsou_Name = arr_Tansuu_unsou_Name.filter(name => name !== unsouName);
                        $('#selectedUnsouName').text(arr_Tansuu_unsou_Name[arr_Tansuu_unsou_Name.length - 1]);


                        // === 色変更 クラス　削除
                        $(val).removeClass(class_name);
                    } else {

                        // === 複数、運送便コード・運送便名　処理
                        newText = fukusuuText + unsouCode + ',';
                        newText_name = fukusuu_select_unsou + unsouName + ',';

                        // ============= 単層便　処理 ============
                        // 配列から削除 , 運送便コード
                        arr_Tansuu_unsou_Code.push(unsouCode);
                        $('#selectedUnsouCode').text(arr_Tansuu_unsou_Code[arr_Tansuu_unsou_Code.length - 1]);

                        // 配列から削除, 運送便名
                        arr_Tansuu_unsou_Name.push(unsouName);
                        $('#selectedUnsouName').text(arr_Tansuu_unsou_Name[arr_Tansuu_unsou_Name.length - 1]);

                        // *************** 「備考・特記」の 値　削除 ***************
                        /*
                        $("#selectedValues_set_next_val .set_next_val").each(function() {
                            var currentText = $(this).text().trim();
                            var startIndex = currentText.indexOf(':');
                            if (startIndex !== -1) {
                                startIndex++; // :の次の文字の位置
                                var endIndex = currentText.indexOf(':', startIndex); // 次の:の位置
                                if (endIndex !== -1) {
                                    var extractedString = currentText.substring(startIndex, endIndex).trim();
                                    var extractedString_Name = currentText.substring(0, startIndex - 1).trim();


                                    if (extractedString == unsouCode) {
                                        LOG_print("削除処理 if 判定", extractedString);
                                        $(this).remove();

                                        // === 色変化 ===
                                        $(this).find('.color-changed-sub').removeClass('color-changed-sub');

                                    }

                                }
                            }
                        });
                        */

                        // === 色変更 クラス　追加  
                        $(val).addClass(class_name);

                    }

                    $('#fukusuu_select').text(newText);

                    $('#fukusuu_select_unsou').text(newText_name);


                    // === ****** ボタン表示上　の合わせるために、#selectedUnsouCode のクリア処理 ******
                    if ($('#fukusuu_select').text() == "") {

                        // ======= 単送便　クリア
                        $('#selectedUnsouCode').empty();
                        $('#selectedUnsouCode').text("");
                        arr_Tansuu_unsou_Code.length = 0; // === 単層 運送コード 配列 *** クリア ***

                        $('#selectedUnsouName').empty();
                        $('#selectedUnsouName').text("");
                        arr_Tansuu_unsou_Name.length = 0; // === 単層 運送名 配列 *** クリア ***

                    }


                    // ===================================== 　複数選択用 （単送便）処理 END ====================
                }

                /*
                    特記・備考 

                    挿入、削除　ロジック
                */

                // 値 挿入用

                // === fukusuu_values　削除　判定用　配列 作成 （運送コード）
                function Hantei_Fkusuu_Values_DEL() {

                    var arr_fukusu_cut_hantei_val = [];
                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();
                        var startIndex = currentText.indexOf(':');
                        if (startIndex !== -1) {
                            startIndex++; // :の次の文字の位置
                            var endIndex = currentText.indexOf(':', startIndex); // 次の:の位置
                            if (endIndex !== -1) {
                                var extractedString = currentText.substring(startIndex, endIndex);
                                arr_fukusu_cut_hantei_val.push(extractedString);

                            }
                        }
                    });
                    return arr_fukusu_cut_hantei_val;
                }

                // === fukusuu_values　削除　判定用　配列 作成 （運送名）
                function Hantei_Fkusuu_Values_DEL_Unsou_Name() {

                    var arr_fukusu_cut_hantei_unsou_name = [];
                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();

                        var endIndex = currentText.indexOf(':'); // 最初の :
                        if (endIndex !== -1) {
                            var extractedString = currentText.substring(0, endIndex);
                            arr_fukusu_cut_hantei_unsou_name.push(extractedString);

                        }
                    });
                    return arr_fukusu_cut_hantei_unsou_name;
                }


                // === 値 格納用
                var selectedValues = [];

                /**
                 *    ************** 備考・特記　ドロップダウン　ボタン処理用 ******************
                 * 
                 */
                function Bikou_Tokki_Push_DEL(val, class_name, unsouName, unsouCode, Bikou_code, Tokki_code, Bikou_name, Tokki_name) {

                    // === 複数用　、備考・特記
                    var fukusuu_bikou_tokki_Text = $("#selectedValues_set_next_val").text().trim();
                    // === 備考・特記　データ形式
                    var newValue = unsouName + ':' + unsouCode + ':' + Bikou_code + ':' + Tokki_code + ',';
                    var valueFound = false;
                    // === 24, 33, 56, データ形式 
                    var fukusuu_values = $('#fukusuu_select').text().trim().split(',').filter(Boolean);
                    // 上記に対応する　運送名
                    var fukusuu_values_unsou = $('#fukusuu_select_unsou').text().trim().split(',').filter(Boolean);

                    // === 単送 , 備考・特記 あり
                    var selectedToki_Code = $('#selectedToki_Code').text();

                    var fukusuu_select = $('#fukusuu_select').text();

                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();
                        if (currentText === newValue) {
                            $(this).remove();

                            // === 色変更 クラス　削除
                            $(val).removeClass(class_name);

                            selectedValues = selectedValues.filter(function(value) {
                                return value !== newValue;
                            });


                            // ============== ****** 単数用 , 特記・備考 ****** 表示 ===============
                            $("#selectedToki_Code").text(selectedValues[selectedValues.length - 1]);
                            valueFound = true;
                        }
                    });

                    // === 同じ値　削除処理 & 色を付ける、外す処理 
                    if (!valueFound) {
                        // 新しい値を追加
                        selectedValues.push(newValue);
                        $("#selectedValues_set_next_val").append('<div class="set_next_val">' + newValue + '</div>');

                        // === fukusuu_values　削除　判定用　配列
                        arr_fukusu_cut_hantei_val = Hantei_Fkusuu_Values_DEL(); // 運送コード
                        arr_fukusu_cut_hantei_unsou_name = Hantei_Fkusuu_Values_DEL_Unsou_Name(); // 運送名

                        // ============== ****** 単数用 , 特記・備考 ****** 表示 ===============
                        $("#selectedToki_Code").text(selectedValues[selectedValues.length - 1]);


                        // ======== 単数時の、運送便コード、運送便名　削除処理

                        // シングル　運送名
                        var selectedUnsouName = $('#selectedUnsouName').text();
                        // シングル　備考・特記
                        var selectedToki_Code = $('#selectedToki_Code').text();

                        //  *** シングル判定処理　*** ここを見直す

                        /*
                        if ($("#selectedToki_Code").text() != "") {
                            $('#selectedUnsouCode').text("");
                            $('#selectedUnsouName').text("");
                        }
                        */



                        //==========================================================
                        // ======= fukusuu_values 重複 削除処理 , 「運送コード」 Start
                        //==========================================================
                        arr_fukusu_cut_hantei_val.forEach(function(code) {
                            code = code.trim();

                            // fukusuu_values 配列内の各値でループ処理して一致する値を削除
                            fukusuu_values = fukusuu_values.filter(function(fukusuu_val) {

                                return fukusuu_val.trim() !== code;
                            });

                            // 上の階層のボタンから color-changed 削除
                            $(`[data-unsou-code="${code}"]`).closest('.dropdown_v').find('button.dropbtn_v').removeClass('color-changed');

                        });

                        // 削除後の値を再度fukusuu_selectにセット
                        $('#fukusuu_select').text(fukusuu_values.join(',') + (fukusuu_values.length > 0 ? ',' : ''));
                        // ======= fukusuu_values 重複 削除処理 END ==================>

                        //==========================================================
                        // ======= fukusuu_values 重複 削除処理　「運送名」 Start
                        //==========================================================
                        arr_fukusu_cut_hantei_unsou_name.forEach(function(name) {
                            name = name.trim();

                            fukusuu_values_unsou = fukusuu_values_unsou.filter(function(fukusuu_values_unsou_val) {
                                return fukusuu_values_unsou_val.trim() !== name;
                            });

                        });

                        $('#fukusuu_select_unsou').text(fukusuu_values_unsou.join(',') + (fukusuu_values_unsou.length > 0 ? ',' : ''));

                        // ======= fukusuu_values 重複 削除処理　「運送名」 ==========>

                        // === 色変更 クラス　追加
                        $(val).addClass(class_name);
                    }


                    // === 値 クリア
                    if ($("#selectedValues_set_next_val .set_next_val").length > 0) {

                    } else {
                        $('#selectedToki_Code').empty();
                        $('#selectedToki_Code').text("");
                        selectedValues.length = 0;
                    }


                    Button_Check();

                    // === 単送　処理 クリア
                    /*
                    if (selectedToki_Code != "") {
                        $('#selectedUnsouCode').text("");
                        $('#selectedUnsouName').text("");

                    } else {

                    }
                    */

                } // =================================================== END function =================


                /* 
                    ＊＊＊　ボタン の色変える（ボタン）　＊＊＊
                */
                function Button_Color_Change(val, class_name) {

                    // === 運送便　コード、　名前　取得
                    var unsouCode = $(val).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-code');
                    var unsouName = $(val).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-name');

                    // ========= 複数選択用 （単送便）処理 ==========
                    Multiple_PUSH_DEL(val, class_name, unsouCode, unsouName);

                    // **************** 途中で他のボタンを押された時対策 ****************
                    $('.dropdown-toggle_v').removeClass('color-changed-drop');
                    // クリックされた要素が.dropdown-toggle_vであるかを確認してクラスを追加
                    $('.dropdown-content_v').each(function() {
                        var content = $(this);
                        var button = content.siblings('.dropdown-toggle_v');

                        if (content.find('button').hasClass('color-changed-sub')) {
                            button.addClass('color-changed-oya');
                        } else {
                            button.removeClass('color-changed-oya');
                        }
                    });

                    // ========= 複数選択用 （単送便）処理 END ==========
                }


                /* 
                    ＊＊＊　ボタン の色変える（ドロップダウン ボタン）＊＊＊
                */
                function Button_Color_Change_SUB(val, class_name) {

                    // =============== 値取得部分  ===============
                    var data_bikou = $(val).attr("data-value");
                    var data_tokki = $(val).attr("data-tokki");
                    var data_bioku_name = $(val).attr("data-company");
                    var data_tokki_name = $(val).attr("data-value");

                    // 親要素の、運送コード, 運送名を取得
                    var unsouCode_m = $(val).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-code');
                    var unsouName_m = $(val).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-name');

                    // ========= 複数選択用 （単送便） 備考、特記  処理 ==========
                    Bikou_Tokki_Push_DEL(val, class_name, unsouName_m, unsouCode_m, data_bikou, data_tokki, data_bioku_name, data_tokki_name);

                }



                /* 
                    ＊＊＊　ボタン の色変える（ドロップダウン ボタン）＊＊＊ // ドロップダウンボタン
                */
                function Button_Color_Change_Drop(val, class_name) {
                    var button = $(val);
                    var data_menuid = button.attr("data-menuid");
                    var targetButton = $('.dropdown-toggle_v[data-menuid="' + data_menuid + '"]');
                    var targetDropdownContent = $('.dropdown-content_v[data-menuid="' + data_menuid + '"]');

                    // === ボタンの色変え
                    if (targetButton.hasClass(class_name)) {
                        targetButton.removeClass(class_name);
                    } else {
                        $('.dropdown-toggle_v').removeClass(class_name);
                        targetButton.addClass(class_name);
                    }

                    // === 下の階層のボタンに color-changed-sub クラスがあるかチェック
                    if (targetDropdownContent.find('button').hasClass('color-changed-sub')) {
                        targetButton.addClass('color-changed-oya');
                    } else {
                        targetButton.removeClass('color-changed-oya');
                    }
                }

                /*
                    ログ　出力 01
                */
                function LOG_print(log_name, val) {
                    console.log(log_name + "::::::" + val);
                }


                // =================================================================================================================
                // ==================================== ボタン処理内　で使う function 一覧  END ===========================================   
                // =================================================================================================================      



                // === ボタンの状態を分岐
                Button_Check();


                // ===================================================================== ボタン 処理 （階層 0）
                // ====== .dropbtn_v　の 上の階層
                // $('.dropdown_v').click(function() {
                $(document).on('click', '.dropdown_v', function() {

                    // 対象のボタンに対応した、ドロップダウンの開け閉め
                    //   Drop_Down_Menu_Open_Close(this);

                });


                // ========================= ドロップダウン　開け・閉め　ボタン
                $(document).on('click', '.dropdown-toggle_v', function() {

                    // 対象のボタンに対応した、ドロップダウンの開け閉め
                    Drop_Down_Menu_Open_Close(this);

                    Button_Color_Change_Drop(this, 'color-changed-drop');

                    // **************** 途中で他のボタンを押された時対策 ****************
                    $('.dropdown-toggle_v').removeClass('color-changed-drop');
                    // クリックされた要素が.dropdown-toggle_vであるかを確認してクラスを追加
                    $('.dropdown-content_v').each(function() {
                        var content = $(this);
                        var button = content.siblings('.dropdown-toggle_v');

                        if (content.find('button').hasClass('color-changed-sub')) {
                            button.addClass('color-changed-oya');
                        } else {
                            button.removeClass('color-changed-oya');
                        }
                    });


                });

                // ===================================================================== ボタン 処理 （階層 01）
                // .dropbtn_v ボタンのクリックイベント
                //  $('.dropbtn_v').click(function() {
                $(document).on('click', '.dropbtn_v', function() {

                    var unsouCode = $(this).data('unsou-code');
                    var unsouName = $(this).data('unsou-name');

                    // clickしたら、ボタンの色を変え、戻す
                    Button_Color_Change(this, 'color-changed');

                    // *************** 単送処理 *******************
                    var selectedToki_Code_Text = $('#selectedToki_Code').text();

                    if (selectedToki_Code_Text != "") {
                        $('#selectedToki_Code').text("");
                    }

                    // *************** 「備考・特記」の 値　削除 ***************
                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();
                        var startIndex = currentText.indexOf(':');
                        if (startIndex !== -1) {
                            startIndex++; // :の次の文字の位置
                            var endIndex = currentText.indexOf(':', startIndex); // 次の:の位置
                            if (endIndex !== -1) {
                                var extractedString = currentText.substring(startIndex, endIndex).trim();

                                if (extractedString == unsouCode) {
                                    console.log("Removing class from 要素:", $(this).html());

                                    $(this).removeClass('color-changed-sub');
                                    //   $(this).find('.color-changed-sub').removeClass('color-changed-sub');
                                    // 要素を削除
                                    $(this).remove();
                                }
                            }
                        }
                    });


                    // ========= ドロップダウンの内容からクラスを削除
                    var menuId = $(this).closest('.dropdown_v').data('menuid');
                    $('.dropdown-content_v[data-menuid="' + menuId + '"] .color-changed-sub').each(function() {
                        console.log("Removing class from dropdown element:", $(this).html());
                        $(this).removeClass('color-changed-sub');
                    });

                    // ========= トグル部分の削除
                    $('.dropdown-toggle_v[data-menuid="' + menuId + '"]').removeClass('color-changed-oya');

                    $('.dropdown-toggle_v[data-menuid="' + menuId + '"]').removeClass('color-changed-drop');

                    var dropdownContent = $('.dropdown-content_v[data-menuid=' + menuId + ']');
                    // dropdownContent.toggleClass('show');
                    // 他のドロップダウンメニューを閉じる
                    $('.dropdown-content_v').not(dropdownContent).removeClass('show');


                    Button_Check();


                });

                // ===================================================================== ボタン（ドロップダウンボタン） 処理 （階層 02）
                // .dropdown-content_v 内のボタンのクリックイベント
                // $('.dropdown-content_v button').click(function() {
                $(document).on('click', '.dropdown-content_v button', function() {

                    // clickしたら、ボタンの色を変え、色を戻す
                    Button_Color_Change_SUB(this, 'color-changed-sub');

                    Button_Check();

                    // =============== 値取得部分 END  ===============

                });


                // ==== ドロップダウン　以外　なにもない箇所の、タップ対応 =====
                // ドロップダウン以外 クリックされたら ドロップダウンメニューを閉じる
                $(document).on('click', function(event) {
                    if (!$(event.target).closest('.dropdown_v').length) {
                        $('.dropdown-content_v').removeClass('show');

                        // ******************* ドロップダウンボタン　処理 *******************
                        // すべての.dropdown-toggle_v要素にcolor-changed-dropクラスを削除
                        $('.dropdown-toggle_v').removeClass('color-changed-drop');
                        // クリックされた要素が.dropdown-toggle_vであるかを確認してクラスを追加
                        $('.dropdown-content_v').each(function() {
                            var content = $(this);
                            var button = content.siblings('.dropdown-toggle_v');

                            if (content.find('button').hasClass('color-changed-sub')) {
                                button.addClass('color-changed-oya');
                            } else {
                                button.removeClass('color-changed-oya');
                            }
                        });


                        /*
                        // クリックされた要素が .dropdown-toggle_v であるかを確認してクラスを追加
                        if ($(event.target).hasClass('dropdown-toggle_v')) {
                            Button_Color_Change_Drop(event.target, 'color-changed-drop');
                        }

                        */

                    }

                });


                // **************************************************
                // 「単数選択」 ボタンを押した時の処理
                // **************************************************
                $('#sendSelectedValues').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();

                    // === 追記
                    var selectedToki_Code = $('#selectedToki_Code').text();

                    // ======= 単数　備考・特記　コード　取得
                    var Tokki_Unsou_Code = "";
                    var currentText = $('#selectedToki_Code').text().trim();
                    var startIndex = currentText.indexOf(':');
                    if (startIndex !== -1) {
                        startIndex++; // :の次の文字の位置
                        var endIndex = currentText.indexOf(':', startIndex); // 次の:の位置
                        if (endIndex !== -1) {
                            var extractedString = currentText.substring(startIndex, endIndex);
                            Tokki_Unsou_Code = extractedString;
                            LOG_print("Tokki_Unsou_Code 値", Tokki_Unsou_Code);
                        }
                    }

                    // ======= 単数　備考・特記　名　取得
                    var Tokki_Unsou_Name = "";
                    var currentText = $('#selectedToki_Code').text().trim();
                    var endIndex = currentText.indexOf(':'); // 次の:の位置
                    if (endIndex !== -1) {
                        var extractedString = currentText.substring(0, endIndex);
                        Tokki_Unsou_Name = extractedString;
                        LOG_print("Tokki_Unsou_Name 値", Tokki_Unsou_Name);
                    }



                    // エラー処理
                    if ($('#selectedUnsouCode').text() === "" && $('#selectedToki_Code').text() === "") {
                        $('#err_text').text("運送便を選択してください。");
                        return false;
                    }

                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSouko = '<?php echo $selectedSouko; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';

                    var tokiCode = $('#selectedToki_Code').text();

                    if (tokiCode.endsWith(',')) {
                        tokiCode = tokiCode.slice(0, -1);
                    }

                    // return false;

                    if (tokiCode === "") {

                        var url = './four.php?unsou_code=' + encodeURIComponent(unsou_code) +
                            '&unsou_name=' + encodeURIComponent(unsou_name) +
                            '&day=' + encodeURIComponent(selectedDay) +
                            '&souko=' + encodeURIComponent(selectedSouko) +
                            '&get_souko_name=' + encodeURIComponent(get_souko_name) +
                            '&forth_pattern=one';
                    } else {



                        // 特記・備考　あり
                        var url = './four.php?unsou_code=' + encodeURIComponent(Tokki_Unsou_Code) +
                            '&unsou_name=' + encodeURIComponent(Tokki_Unsou_Name) +
                            '&day=' + encodeURIComponent(selectedDay) +
                            '&souko=' + encodeURIComponent(selectedSouko) +
                            '&get_souko_name=' + encodeURIComponent(get_souko_name) +
                            '&selectedToki_Code=' + encodeURIComponent(tokiCode) +
                            '&forth_pattern=two';

                    }


                    window.location.href = url;
                });

                // 「複数選択　ボタン」
                $('#sendSelectedValues_multiple').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();
                    //    console.log(unsou_name);

                    // === エラー処理
                    /*
                    if (unsou_code === "") {
                        $('#err_text').text("選択してください。");
                        return false;
                    }
                        */

                    // ==========================================   
                    // =========== 運送名　取得 ===================
                    // ==========================================   

                    var Fukusuu_Unsou_Name = "";
                    var fukusuu_values_unsou = $('#fukusuu_select_unsou').text().trim().split(',').filter(Boolean);

                    arr_fukusu_cut_hantei_unsou_name = Hantei_Fkusuu_Values_DEL_Unsou_Name();
                    var arr_TMP_Fukusuu_unsou_name = removeDuplicates(arr_fukusu_cut_hantei_unsou_name);

                    LOG_print("arr_fukusu_cut_hantei_unsou_name", arr_fukusu_cut_hantei_unsou_name);
                    LOG_print("複数運送", fukusuu_values_unsou);

                    var combinedArray = fukusuu_values_unsou.concat(arr_TMP_Fukusuu_unsou_name);
                    // 重複を削除
                    combinedArray = removeDuplicates(combinedArray);
                    // 結合した配列をカンマで結合して結果を格納
                    Fukusuu_Unsou_Name = combinedArray.join(',');

                    // return false;


                    // *********************************************   
                    // ************* データ　削除処理 *****************
                    // ************************************************
                    var valuesArray = [];

                    $('#selectedValues_set_next_val .set_next_val').each(function() {
                        // 各divのテキストを取得
                        var text = $(this).text();
                        var value = text.split(':')[1];
                        valuesArray.push(value);
                    });

                    //   console.log(".set_next_val ボタン押した時の値:::" + valuesArray);

                    var fukusuuText = $('#fukusuu_select').text();

                    // コンマで分割して配列に変換
                    var fukusuuArray = fukusuuText.split(',');

                    // valuesArrayの各値についてfukusuuArrayから一致するものを削除
                    valuesArray.forEach(function(value) {
                        var index = fukusuuArray.indexOf(value);
                        if (index !== -1) {
                            fukusuuArray.splice(index, 1);
                        }
                    });

                    // 配列を再びコンマ区切りの文字列に変換し、spanに設定
                    $('#fukusuu_select').text(fukusuuArray.join(','));

                    // *********************************************   
                    // ************* データ　削除処理  END *****************
                    // ************************************************

                    var fukusuu_select_name = $('#fukusuu_select').text();

                    //   console.log("fukusuu_select_name::: 削除後値 ::::" + fukusuu_select_name);


                    var fukusuu_select_unsou_name = $('#fukusuu_select_unsou').text();
                    //    console.log(fukusuu_select_unsou_name);

                    var arr_set_next_val = [];
                    // === 選択した値を取得
                    $('#selectedValues_set_next_val > div.set_next_val').each(function() {
                        var set_next_val = $(this).text();

                        // 配列へ値を追加
                        arr_set_next_val.push(set_next_val);
                    });

                    // URLに追加するパラメータの値をエンコード
                    var encodedValues = arr_set_next_val.map(function(value) {
                        return encodeURIComponent(value);
                    }).join('-');

                    //    console.log("encodedValues:::" + encodedValues);


                    // === 画面遷移　
                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSouko = '<?php echo $selectedSouko; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';

                    // var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_unsouo_name=' + fukusuu_select_unsou_name + '&fukusuu_select=' + '200';
                    // var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + fukusuu_select_unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_select=' + '200';
                    var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + Fukusuu_Unsou_Name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_select=' + '200';

                    //       return false;
                    if (encodedValues != "") {
                        url += '&fukusuu_select_val=' + encodedValues; // 修正
                        window.location.href = url;

                    } else {
                        url_val = "";
                        url += '&fukusuu_select_val=' + encodeURIComponent(url_val); // 修正
                        window.location.href = url;
                    }

                });


                // 「クリア ボタン」がクリックされたときの処理
                $("#sendSelectedValues_clear").on('click', function() {

                    // ボタンの背景色を元に戻す
                    $('.dropdown_v button.color-changed').each(function() {
                        $(this).css('background-color', '#6d6666').removeClass('color-changed');
                    });

                    // color-changed-oya クラス 削除
                    $('.dropdown_v button.dropdown-toggle_v.color-changed-oya').removeClass('color-changed-oya');

                    // color-changed-sub クラス 削除
                    $('.dropdown_v button.color-changed-sub').each(function() {
                        $(this).removeClass('color-changed-sub');
                    });


                    // 選択された要素をリセット
                    $("#selectedValues_set_next_val_View_Tan p").text("");
                    $("#selectedValues_set_next_val_View_UNSOU .unsou_name_v").text("");
                    $("#selectedValues_set_next_val_View p").text("");
                    $("#selectedValues_set_next_val_View_UNSOU p").text("");

                    $("#selectedUnsouCode").text("");
                    $("#selectedUnsouName").text("");
                    $("#selectedToki_Code").text("");

                    // 隠しパラメータをクリア
                    $("#fukusuu_select").empty();
                    $("#fukusuu_select_unsou").empty();
                    $("#selectedValues_set_next_val").empty();
                    $(".select_data_box").empty();



                    // ボタンの状態を再評価
                    Button_Check();
                });





            }) // =============================== END


        })(jQuery);
    </script>


</body>

</html>