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
        // print($selected_day . "<br />");

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
                    foreach ($arr_Unsou_data as $row) {
                        echo '<div class="dropdown_v" data-menuid="' . $idx . '">';
                        echo '<button class="dropbtn_v" value="' . $row["Unsou_code"] . '" data-unsou-code="' . $row["Unsou_code"] . '" data-unsou-name="' . $row["Unsou_name"] . '">' . $row["Unsou_name"] . '</button>';
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
                    ?>
                </div>

            </div>

            <!-- 選択した値を表示する部分 -->


            <!--
            <div class="selected-value" style="opacity: 0;">
                -->
            <div class="selected-value">
                選択した運送コード: <span id="selectedUnsouCode"></span><br>
                選択した運送会社: <span id="selectedUnsouName"></span><br>
                特記・備考: <span id="selectedToki_Code"></span><br>
            </div>


            <hr>

            <!-- 選択した値表示 （備考・特記あり） -->

            <div id="selectedValues_set_next_val_View_Tan" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi_01" style="display:block;">■選択(単数選択)</span>
                <div id="singleSelectionContent"></div>
            </div>

            <!--
            <div id="selectedValues_set_next_val_View_Tan" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi" style="display:block;">■選択(単数便)</span>
            </div>
                -->

            <!-- 選択した値表示 運送便　複数 -->
            <div id="selectedValues_set_next_val_View_UNSOU" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi" style="display:block;">■選択(複数条件, 運送便 )</span>
            </div>


            <!-- 選択した値表示 （備考・特記あり） -->
            <div id="selectedValues_set_next_val_View" style="margin: 15px 0 25px 0;">
                <span class="third_view_midashi" style="display:block;">■選択(複数条件, 備考・特記)</span>
            </div>

            <!--
            <div style="opacity: 0;">
                -->
            <div style="opacity: 0;">
                <span id="op_01" style="display: inline-block;">複数選択</span>
                <span id="fukusuu_select">：</span><br />
                <span id="fukusuu_select_unsou"></span><br />
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
            <div id="selectedValues_set_next_val">
                <span style="display:inline-block;">■選択(複数条件)</span>
            </div>

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

                // === ボタンの状態判別
                function Button_Check() {
                    var text = $('#fukusuu_select').text().trim();
                    // 末尾のカンマを削除して分割
                    var values = text.replace(/,$/, '').split(',').filter(Boolean);
                    // 配列の長さを取得
                    var count = values.length;
                    // console.log("ボタンチェック:::" + count);

                    // 同じ運送コードで複数対応
                    var count_set_next_val = $('#selectedValues_set_next_val .set_next_val').length;
                    // console.log("個数を出力:::" + count_set_next_val); // コンソールに数を出力

                    if (count > 1 || count_set_next_val > 1) {
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
                        $('#selectedValues_set_next_val_View').show();
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
                        $('#selectedValues_set_next_val_View').hide();

                    }


                    // =================================================
                    // ========== 色が変え残った、ボタン対策  & 初期化==============
                    // ==================================================
                    // === 運送便が選択されていない状態の時は、初期化

                    /*
                    if ($("#selectedValues_set_next_val_View_UNSOU p").length == 0 && $('#selectedValues_set_next_val_View p').length == 0) {

                        // 隠しパラメータをクリア
                        $("#fukusuu_select").empty();
                        $("#fukusuu_select_unsou").empty();

                        $(".select_data_box").empty();

                        $("#fukusuu_select").text("");
                        $("#selectedUnsouCode").text("");
                        $("#selectedUnsouName").text("");

                        // 親のボタンの色を戻す
                        $('.dropdown_v').find('button.dropbtn_v').each(function() {
                            $(this).css('background-color', '#6d6666').removeClass('color-changed');
                        });

                    }
                        */
                    /*else if ($("#selectedValues_set_next_val_View_UNSOU p").length == 0 && $('#selectedValues_set_next_val_View p').length != 0) {

                                           // === ボックスに残っている　値
                                           $("#selectedValues_set_next_val_View > div > p > span.view_item_02_val").each(function() {
                                               var unsouCode = $(this).text().trim(); // 運送便コードを取得

                                               // 対応する運送便コードのボタンの色を変える
                                               $('.dropdown_v[data-menuid]').each(function() {
                                                   var menuId = $(this).data('menuid');
                                                   if (menuId == unsouCode) {
                                                       $(this).find('button.dropbtn_v').css('background-color', '#45a049').addClass('color-changed');
                                                   }
                                               });
                                           });
                                       } else {
                                        

                                       }
                                       */

                } // =============================== END function


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
                    console.log("複数セレクト 備考・特記*****" + arr_unsou_Name_bikou_tokki);


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



                // === ボタンの状態を分岐
                Button_Check();

                // ================================================
                // ドロップダウンメニューの表示を切り替える
                // ================================================
                $('.dropdown_v').on('click', function() {
                    var menuId = $(this).data('menuid');
                    var dropdownContent = $('.dropdown-content_v[data-menuid=' + menuId + ']');

                    /*
                    console.log("メニューID:" + menuId);
                    console.log("ドロップダウンコンテンツ:" + dropdownContent);
                    */

                    dropdownContent.toggleClass('show');

                    // 他のドロップダウンメニューを閉じる
                    $('.dropdown-content_v').not(dropdownContent).removeClass('show');

                    // 運送コード, 運送便名, 取得
                    var unsouCode = $(this).find('button').data('unsou-code');
                    var unsouName = $(this).find('button').data('unsou-name');

                    // 運送名表示   2024/06/07
                    // 運送コードの表示と重複削除

                    if ($('#fukusuu_select').text().includes(unsouCode)) {
                        // 既に選択されている場合は削除
                        var fukusuuText = $('#fukusuu_select').text();
                        var newText = fukusuuText.replace(new RegExp(unsouCode + ',', 'g'), '');
                        $('#fukusuu_select').text(newText);
                        //    $(this).css('background-color', '#6d6666').removeClass('color-changed');
                    } else {
                        // 新しい選択を追加
                        $('#fukusuu_select').append(unsouCode + ',');
                    }

                    // 運送名の表示と重複削除
                    if ($('#fukusuu_select_unsou').text().includes(unsouName)) {
                        // 既に選択されている場合は削除
                        var unsouNameText = $('#fukusuu_select_unsou').text();
                        var newUnsouText = unsouNameText.replace(new RegExp(unsouName + ' ', 'g'), '');
                        $('#fukusuu_select_unsou').text(newUnsouText);
                    } else {
                        // 新しい選択を追加
                        $('#fukusuu_select_unsou').append(unsouName + ' ');
                    }



                    // === ボタンの状態を分岐
                    Button_Check();

                    // === プルダウンボタン、親ボタンとかぶっていたら削除
                    //      removeOldValues_Button();
                });

                // プルダウンメニューのボタンがクリックされたときの処理
                var selectedValues = [];
                var selectedValues_view = [];

                // ===============================================
                // プルダウン タップ
                // ===============================================
                $('.dropdown-content_v button').on('click', function() {

                    var data_value = $(this).attr("data-value");
                    var data_tokki = $(this).attr("data-tokki");

                    // 詳細データ取得
                    selected_Detail_Code = $(this).data('value');
                    selectedUnsou_Detail_Name = $(this).data('company');
                    // 特記事項　取得
                    selectedUnsou_Detail_tokki = $(this).data('tokki');

                    // 親要素の、運送コード, 運送名を取得
                    var unsouCode_m = $(this).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-code');
                    var unsouName_m = $(this).closest('.dropdown_v').find('button.dropbtn_v').data('unsou-name');


                    // === 単数、特記・備考
                    $('#selectedToki_Code').text(unsouName_m + ":" + unsouCode_m + ":" +
                        selected_Detail_Code + ":" + selectedUnsou_Detail_tokki);

                    // console.log("テスト：：：特記コード取得：：：" + $('#selectedToki_Code').text());

                    // === 上の階層の、ボタンを押した時の リセット
                    // 倉庫コードを空にする
                    $("#selectedUnsouCode").empty();
                    $("#selectedUnsouName").empty();


                    var newValue = unsouName_m + ':' + unsouCode_m + ':' + selected_Detail_Code + ':' + selectedUnsou_Detail_tokki + ',';
                    var valueFound = false;


                    // === 表示用
                    var newValue_VIEW = '<p><span class="view_item_01">運送便名</span><span class="view_item_01_val">' + unsouName_m + '</span>' +
                        '<span class="view_item_02">運送便コード</span><span class="view_item_02_val">' + unsouCode_m + '</span>' +
                        '<span class="view_item_03">備考</span><span class="view_item_03_val">' + selected_Detail_Code + '</span>' +
                        '<span class="view_item_04">特記</span><span class="view_item_04_val">' + selectedUnsou_Detail_tokki + '</span></p>';

                    //  console.log("selectedValues_set_next_valへ値を入れる");


                    // 重複を削除
                    var arr_fukusu_cut_hantei_val = [];
                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();
                        if (currentText === newValue) {
                            $(this).remove();
                            selectedValues = selectedValues.filter(function(value) {
                                return value !== newValue;
                            });
                            valueFound = true;
                        }
                    });

                    if (!valueFound) {
                        // 新しい値を追加
                        selectedValues.push(newValue);
                        //     console.log("selectedValuesに追加:", data_value);
                        $("#selectedValues_set_next_val").append('<div class="set_next_val">' + newValue + '</div>');
                    } else {
                        //  console.log("selectedValuesに既に存在:", newValue);
                    }


                    // *********「複数用」 データ処理 　単層便 削除処理　追加 24_0614 *********
                    // 最初の:から次の:までの文字列を取得する
                    $("#selectedValues_set_next_val .set_next_val").each(function() {
                        var currentText = $(this).text().trim();
                        var startIndex = currentText.indexOf(':');
                        if (startIndex !== -1) {
                            startIndex++; // :の次の文字の位置
                            var endIndex = currentText.indexOf(':', startIndex); // 次の:の位置
                            if (endIndex !== -1) {
                                var extractedString = currentText.substring(startIndex, endIndex);
                                arr_fukusu_cut_hantei_val.push(extractedString);
                                console.log("最初の:から次の:までの文字列:", arr_fukusu_cut_hantei_val);
                            }
                        }
                    });

                    // *************************** 複数セレクト　用　 処理　追加 24_0614
                    // === 24, 33, 56, データ形式
                    var fukusuu_values = $('#fukusuu_select').text().trim().split(',').filter(Boolean);

                    arr_fukusu_cut_hantei_val.forEach(function(code) {
                        code = code.trim();

                        // fukusuu_values 配列内の各値でループ処理して一致する値を削除
                        fukusuu_values = fukusuu_values.filter(function(fukusuu_val) {
                            return fukusuu_val.trim() !== code;
                        });
                    });

                    // 削除後の値を再度fukusuu_selectにセット
                    $('#fukusuu_select').text(fukusuu_values.join(',') + (fukusuu_values.length > 0 ? ',' : ''));

                    //   console.log("追加 削除処理後:::" + $('#fukusuu_select').text());
                    // *************************** 複数セレクト　用　 処理　追加 24_0614 END


                    // *********「複数用」 データ処理 　単層便 削除処理　追加 24_0614 END *********
                    // 最初の:から次の:までの文字列を取得する

                    // === 表示用 （重複を削除）
                    var valueFound_02 = false;
                    $('#selectedValues_set_next_val_View .set_next_val_view').each(function() {
                        var currentText = $(this).html();
                        if (currentText === newValue_VIEW) {
                            $(this).remove();
                            selectedValues_view = selectedValues_view.filter(function(value) {
                                return value !== newValue_VIEW;
                            });
                            valueFound_02 = true;
                        }
                    });

                    if (!valueFound_02) {
                        // === 表示用 （新しい値を追加）
                        selectedValues_view.push(newValue_VIEW);
                        //     console.log("selectedValues_viewに追加:", newValue_VIEW);
                        $("#selectedValues_set_next_val_View").append('<div class="set_next_val_view">' + newValue_VIEW + '</div>');
                    } else {
                        //     console.log("selectedValues_viewに既に存在:", newValue_VIEW);
                    }


                    // ********** 表示用　削除処理 追加 24_0614 **********
                    $('#selectedValues_set_next_val_View .view_item_01_val').each(function() {

                        var itemName = $(this).text().trim();

                        $('#selectedValues_set_next_val_View_UNSOU p').each(function() {
                            var tansouName = $(this).find('.tansou_select_VAL').text().trim();
                            //       console.log("表示用、複数 tansouName:::" + tansouName);

                            // 一致した場合
                            if (itemName === tansouName) {
                                $('#selectedValues_set_next_val_View_UNSOU').find('p:contains(' + itemName + ')').remove();
                            }
                        });
                        //   console.log("表示用、複数 view 01:::" + itemName);
                    });


                    // ************************ 単数用　データを、表示 追加 24_0614 *********************

                    var selectedUnsouCode = $('#selectedUnsouCode').text();
                    var selectedUnsouName = $('#selectedUnsouName').text();
                    var selectedTokiCode = $('#selectedToki_Code').text();

                    // console.log("値更新前、値確認：：：" + selectedTokiCode);


                    // 複数、特記ありの最後のデータ <p> エレメントを 取得
                    var lastPElement = $('#selectedValues_set_next_val_View').find('.set_next_val_view:last').children('p');
                    // テキスト内容をコンソールに表示する例
                    // console.log("最後の要素:::" + lastPElement.text());



                    // #singleSelectionContent に表示する文字列を作成

                    // ******************* 単層便　処理　追加 24_0615 
                    // === 単層便 処理 
                    $('#selectedValues_set_next_val_View_Tan').find('p:contains(' + unsouName_m + ')').remove();
                    $('#selectedUnsouCode').empty(); // 運送コード 
                    $('#selectedUnsouName').empty(); // 運送名

                    if ($('#selectedValues_set_next_val_View_Tan').children('p').length == 0) {
                        // 子要素がない場合、追加
                        $('#selectedValues_set_next_val_View_Tan').append('<p><span class="tansou_select">運送コード、特記・備考</span>' +
                            '<span class="tansou_select_VAL">' + selectedTokiCode + '</span>' + '</p>');

                        $('#selectedUnsouCode').empty(); // 運送コード 
                        $('#selectedUnsouName').empty(); // 運送名

                    } else {
                        // 子要素がある場合、クリアしてから追加
                        $('#selectedValues_set_next_val_View_Tan p').empty().append('<p><span class="tansou_select">運送コード、特記・備考</span>' +
                            '<span class="tansou_select_VAL">' + selectedTokiCode + '</span>' + '</p>');

                        $('#selectedUnsouCode').empty(); // 運送コード 
                        $('#selectedUnsouName').empty(); // 運送名

                    }

                    // ************************ 単数用　データを、表示 追加 24_0614 *********************


                    // ********** 一旦　コメントアウト *********
                    // 親のボタンの色を変える  
                    //    $(this).closest('.dropdown_v').find('button.dropbtn_v').css('background-color', '#45a049').addClass('color-changed');

                    // *********************************************************************************************
                    // ********************************　　プルダウンボタン　に色をつける *****************************
                    // *********************************************************************************************

                    if ($(this).hasClass('color-changed-sub')) {
                        // 色を元に戻す
                        $(this).closest('.dropdown_v').find('button.dropbtn_v').css('background-color', '#6d6666').removeClass('color-changed-sub');
                        //   $(this).css('background-color', '#6d6666').removeClass('color-changed-sub');
                    } else {
                        // 色を変更
                        // 親のボタンの色を変える  
                        $(this).closest('.dropdown_v').find('button.dropbtn_v').css('background-color', '#ccc').addClass('color-changed-sub');
                        //        $(this).css('background-color', '#ccc').addClass('color-changed-sub');

                    }


                    // === ボタンの状態を分岐
                    Button_Check();

                    // === １階層　上のボタンと選択が被っていたら、階層上のボタンを削除
                    //    removeOldValues_SUB_Button();


                });

                // ===============================================
                // ボタンがクリックされたときに色を変える
                // ===============================================
                $('.dropdown_v button').on('click', function() {

                    var unsouCode = $(this).data('unsou-code');
                    var unsouName = $(this).data('unsou-name'); // 運送名を取得

                    // unsouCode または unsouName が undefined の場合は処理を中断
                    if (typeof unsouCode === 'undefined' || typeof unsouName === 'undefined') {
                        return;
                    }

                    // === データ　複数セレクト　運送便のみ
                    var fukusuu_select = $('#fukusuu_select');
                    var fukusuu_select_unsou = $('#fukusuu_select_unsou');

                    if ($(this).hasClass('color-changed')) {
                        // 色を元に戻す
                        $(this).css('background-color', '#6d6666').removeClass('color-changed');

                        // 既に選択されている場合は削除して色を元に戻す *** コメントアウト
                        /*
                        var fukusuuText = fukusuu_select.text();
                        var newText = fukusuuText.replace(new RegExp(unsouCode + ',', 'g'), '');
                        fukusuu_select.text(newText);

                        var unsouNameText = fukusuu_select_unsou.text();
                        var newUnsouText = unsouNameText.replace(new RegExp(unsouName + ' ', 'g'), '');
                        fukusuu_select_unsou.text(newUnsouText);
                        */


                        $('#selectedValues_set_next_val_View_UNSOU').find('p:contains(' + unsouName + ')').remove();

                        // ******************* 単層便　処理　追加 24_0615 
                        // === 単層便 処理 
                        $('#selectedValues_set_next_val_View_Tan').find('p:contains(' + unsouName + ')').remove();
                        $('#selectedUnsouCode').empty(); // 運送コード 
                        $('#selectedUnsouName').empty(); // 運送名

                    } else {
                        // 色を変更
                        $(this).css('background-color', '#45a049').addClass('color-changed');

                        // 新しい選択を追加
                        $('#selectedValues_set_next_val_View_UNSOU').append('<p><span class="tansou_select">運送便名</span>' +
                            '<span class="tansou_select_VAL">' + unsouName + '</span>' + '</p>');


                        // ******************* 単層便　処理　追加 24_0615 
                        if ($('#selectedValues_set_next_val_View_Tan p').length == 0) {

                            // === 単層便 処理
                            // === 単層便 処理 - 最新の1件のみ表示
                            $('#selectedValues_set_next_val_View_Tan').append('<p><span class="tansou_select">運送便名</span>' +
                                '<span class="tansou_select_VAL">' + unsouName + '</span>' + '</p>');
                            $('#selectedUnsouCode').text(unsouCode); // 運送コード 
                            $('#selectedUnsouName').text(unsouName); // 運送名
                            // 備考・特記　クリア
                            $('#selectedToki_Code').empty();

                        } else {

                            // === 単層便 処理
                            // === 単層便 処理 - 最新の1件のみ表示
                            $('#selectedValues_set_next_val_View_Tan p').empty().append('<p><span class="tansou_select">運送便名</span>' +
                                '<span class="tansou_select_VAL">' + unsouName + '</span>' + '</p>');
                            $('#selectedUnsouCode').text(unsouCode); // 運送コード 
                            $('#selectedUnsouName').text(unsouName); // 運送名
                            // 備考・特記　クリア
                            $('#selectedToki_Code').empty();

                        }

                    }


                    // === ボタンの状態を分岐
                    Button_Check();

                });


                // ==== ドロップダウン　以外　なにもない箇所の、タップ対応 =====
                // ドロップダウン以外 クリックされたら ドロップダウンメニューを閉じる
                $(document).on('click', function(event) {
                    if (!$(event.target).closest('.dropdown_v').length) {
                        $('.dropdown-content_v').removeClass('show');
                    }

                    //           Color_All();

                });

                // **************************************************
                // 「次へボタンを押した時の処理
                // **************************************************
                $('#sendSelectedValues').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();

                    // === 追記
                    var selectedToki_Code = $('#selectedToki_Code').text();

                    // ======== データクリア　処理
                    if ($("#selectedValues_set_next_val_View_Tan p").length == 0) {
                        $('#selectedUnsouCode').text(""); // 運送コード　クリア
                        $('#selectedToki_Code').text(""); // 特記コード クリア

                        //     console.log("単数ボタン:::データクリアー");
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
                        var url = './four.php?unsou_code=' + encodeURIComponent(unsou_code) +
                            '&unsou_name=' + encodeURIComponent(unsou_name) +
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

                    //var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_unsouo_name=' + fukusuu_select_unsou_name + '&fukusuu_select=' + '200';
                    var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + fukusuu_select_unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_select=' + '200';

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

                    // 選択された要素をリセット
                    $("#selectedValues_set_next_val_View_Tan p").text("");
                    $("#selectedValues_set_next_val_View_UNSOU .unsou_name_v").text("");
                    $("#selectedValues_set_next_val_View p").text("");
                    $("#selectedValues_set_next_val_View_UNSOU p").text("");

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