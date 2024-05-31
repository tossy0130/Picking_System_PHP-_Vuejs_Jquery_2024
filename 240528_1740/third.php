<?php

ini_set('display_errors', 1);

require __DIR__ . "./conf.php";
require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

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


        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }

        // 特記なし
        /*
        $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
	                FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US
	                WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                        AND SK.倉庫Ｃ = SO.倉庫Ｃ
                        AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                        AND SL.出荷元 = SM.出荷元Ｃ(+)
	                    AND SK.運送Ｃ = US.運送Ｃ
	                    AND SJ.出荷日 = :GET_DATE
                        AND SK.倉庫Ｃ = :GET_SOUKO
                    GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名
                    ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SL.出荷元,SM.出荷元名";
        */

        // 特記あり
        //24/05/24
        //        $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,
        //                    SM.出荷元名,SK.特記事項
        //                  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US
        //                  WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
        //                        AND SK.倉庫Ｃ = SO.倉庫Ｃ
        //                        AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
        //                        AND SL.出荷元 = SM.出荷元Ｃ(+)
        //                      AND SK.運送Ｃ = US.運送Ｃ
        //                      AND SJ.出荷日 = :GET_DATE
        //                        AND SK.倉庫Ｃ = :GET_SOUKO
        //                    GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.特記事項
        //                    ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SL.出荷元,SM.出荷元名 ,SK.特記事項";
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

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

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
                <a href="#"><i class="fa-solid fa-house"></i></a>
            </span>

            <span class="App_name">
                グリーンライフ ピッキング
            </span>
        </div>
    </div>


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <i class="fa-solid fa-thumbtack"></i>
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
            <div class="selected-value">
                選択した運送コード: <span id="selectedUnsouCode"></span><br>
                選択した運送会社: <span id="selectedUnsouName"></span><br>
                特記・備考: <span id="selectedToki_Code"></span><br>
            </div>
            <hr>

            <div>
                <span id="op_01">複数選択</span><span id="fukusuu_select">：</span><br />
                <span id="op_02">複数選択（特記）</span><span id="fukusuu_select_option_01">：</span><br />
                <span id="op_03">複数選択名</span><span id="fukusuu_select_name">：</span><br /><br />

                <span id="op_04">複数選択 持っていく値:::</span><span id="f_select"></span>
            </div>

            <!-- 複数選択 -->
            <div id="selectedValues_set_next_val">
                <span style="display:inline-block;">■次ページへ持っていく値（特記あり）</span>
            </div>

            <p id="err_text" style="color:red;text-align:center;"></p>

            <div class="third_btn_flex_box">
                <div id="sendSelectedValues_box">
                    <button id="sendSelectedValues">次へ</button>
                </div>

                <div id="sendSelectedValues_box">
                    <button id="sendSelectedValues_multiple">複数選択</button>
                </div>
            </div>

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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
        (function($) {
            $(document).ready(function() {

                // ドロップダウンメニューの表示を切り替える
                $('.dropdown_v').on('click', function() {
                    var menuId = $(this).data('menuid');
                    var dropdownContent = $('.dropdown-content_v[data-menuid=' + menuId + ']');

                    console.log("メニューID:" + menuId);
                    console.log("ドロップダウンコンテンツ:" + dropdownContent);
                    dropdownContent.toggleClass('show');

                    // 運送コード, 運送便名, 取得
                    var unsouCode = $(this).find('button').data('unsou-code');
                    var unsouName = $(this).find('button').data('unsou-name');

                    $('#selectedUnsouCode').text(unsouCode);
                    $('#selectedUnsouName').text(unsouName);

                    // 同じ値が存在する場合は処理を返す
                    // 運送コード表示
                    if ($('#fukusuu_select').text().includes(unsouCode)) {
                        return;
                    } else {
                        $('#fukusuu_select').append(unsouCode + ',');
                    }

                });

                // プルダウンメニューのボタンがクリックされたときの処理
                var selectedValues = [];

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

                    console.log("詳細コード:", selected_Detail_Code);
                    console.log("詳細名:", selectedUnsou_Detail_Name);
                    console.log("特記事項:", selectedUnsou_Detail_tokki);
                    console.log("運送コード:", unsouCode_m);
                    console.log("運送名:", unsouName_m);


                    $('#selectedToki_Code').text(unsouName_m + ":" + unsouCode_m + ":" +
                        selected_Detail_Code + ":" + selectedUnsou_Detail_tokki);


                    // 要素を追加
                    if (selectedValues.indexOf(data_value) === -1 && selectedValues.indexOf(unsouCode_m) === -1) {
                        selectedValues.push(unsouCode_m + data_value);
                        console.log("selectedValuesに追加:", data_value);
                        $("#selectedValues_set_next_val").append('<div class="set_next_val">' + unsouName_m + ':' +
                            unsouCode_m + ':' + selected_Detail_Code + ':' + selectedUnsou_Detail_tokki + ',' + '</div>');
                    } else {
                        console.log("selectedValuesに既に存在:", data_value);
                    }

                });

                // 「次へボタンを押した時の処理
                $('#sendSelectedValues').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();

                    // エラー処理
                    if (unsou_code === "") {
                        $('#err_text').text("運送便を選択してください。");
                        return false;
                    }

                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSouko = '<?php echo $selectedSouko; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';

                    var tokiCode = $('#selectedToki_Code').text();

                    if (tokiCode === "") {

                        var url = './four.php?unsou_code=' + encodeURIComponent(unsou_code) +
                            '&unsou_name=' + encodeURIComponent(unsou_name) +
                            '&day=' + encodeURIComponent(selectedDay) +
                            '&souko=' + encodeURIComponent(selectedSouko) +
                            '&get_souko_name=' + encodeURIComponent(get_souko_name);
                    } else {
                        // 特記・備考　あり
                        var url = './four.php?unsou_code=' + encodeURIComponent(unsou_code) +
                            '&unsou_name=' + encodeURIComponent(unsou_name) +
                            '&day=' + encodeURIComponent(selectedDay) +
                            '&souko=' + encodeURIComponent(selectedSouko) +
                            '&get_souko_name=' + encodeURIComponent(get_souko_name) +
                            '&selectedToki_Code=' + encodeURIComponent(tokiCode);

                    }

                    window.location.href = url;
                });

                // 「複数選択　ボタン」
                $('#sendSelectedValues_multiple').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();

                    // === エラー処理
                    if (unsou_code === "") {
                        $('#err_text').text("選択してください。");
                        return false;
                    }

                    var fukusuu_select_name = $('#fukusuu_select').text();
                    console.log("fukusuu_select_name:::" + fukusuu_select_name)

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

                    console.log("encodedValues:::" + encodedValues);


                    // === 画面遷移　
                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSouko = '<?php echo $selectedSouko; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';

                    var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko + '&get_souko_name=' + get_souko_name + '&fukusuu_unsouo_num=' + fukusuu_select_name + '&fukusuu_select=' + '200';

                    if (encodedValues != "") {
                        url += '&fukusuu_select_val=' + encodedValues; // 修正
                        window.location.href = url;

                    } else {
                        url_val = "";
                        url += '&fukusuu_select_val=' + encodeURIComponent(url_val); // 修正
                        window.location.href = url;
                    }

                });

            })
        })(jQuery);
    </script>


</body>

</html>