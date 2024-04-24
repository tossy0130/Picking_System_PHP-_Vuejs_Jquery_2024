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
        // print($selected_day . "<br />");

        // ============================= DB 処理 =============================
        // === 接続準備
        $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

        if (!$conn) {
            $e = oci_error();
        }


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
                if ($detail['shipping_moto'] == $shipping_moto && $detail['shipping_moto_name'] == $shipping_moto_name) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $arr_Unsou_data[$key]['details'][] = array(
                    'shipping_moto' => $shipping_moto,
                    'shipping_moto_name' => $shipping_moto_name
                );
            }
        }

        //  var_dump($arr_Unsou_data);
        // print("配列個数:::" . count($arr_Unsou_data, 1));
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

    <title>ピッキング 03</title>


    <style>
        .show {
            display: block;
        }
    </style>

</head>

<body>
    <div id="app">
        <div class="souko_box_v">
            <?php
            $idx = 1;
            foreach ($arr_Unsou_data as $row) {
                echo '<div class="dropdown_v" data-menuid="' . $idx . '">';
                echo '<button class="dropbtn_v" value="' . $row["Unsou_code"] . '" data-unsou-code="' . $row["Unsou_code"] . '" data-unsou-name="' . $row["Unsou_name"] . '">' . $row["Unsou_name"] . '</button>';
                echo '<div class="dropdown-content_v" data-menuid="' . $idx . '">';
                foreach ($row["details"] as $detail) {
                    echo '<button type="button" data-company="' . $detail["shipping_moto_name"] . '" data-value="' . $detail["shipping_moto"] . '">' . $detail["shipping_moto_name"] . '</button>';
                }
                echo '</div></div>';
                $idx++;
            }
            ?>
        </div>

    </div>
    <!-- 選択した値を表示する部分 -->
    <div class="selected-value">
        複数選択: <span id="multiple_val"></span><br>
        選択した運送コード: <span id="selectedUnsouCode"></span><br>
        選択した運送会社: <span id="selectedUnsouName"></span><br>
        特記code: <span id="selectedToki_Code"></span><br>
        特記名: <span id="selectedToki_Name"></span>
    </div>
    <button id="sendSelectedValues">次へ</button>
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
                    var unsouCode = $(this).find('button').data('unsou-code');;
                    var unsouName = $(this).find('button').data('unsou-name');

                    $('#selectedUnsouCode').text(unsouCode);
                    $('#selectedUnsouName').text(unsouName);

                    console.log("運送コード:" + unsouCode);
                    console.log("運送名:" + unsouName);

                });

                // プルダウンメニューのボタンがクリックされたときの処理
                $('.dropdown-content_v button').on('click', function() {
                    selected_Detail_Code = $(this).data('value');
                    selectedUnsou_Detail_Name = $(this).data('company');

                    console.log("詳細データ01 コード:::" + selected_Detail_Code);
                    console.log("詳細データ02 名前:::" + selectedUnsou_Detail_Name);

                    $('#selectedToki_Code').text(selected_Detail_Code);
                    $('#selectedToki_Name').text(selectedUnsou_Detail_Name);

                });


                $('#sendSelectedValues').on('click', function() {

                    var unsou_code = $('#selectedUnsouCode').text();
                    var unsou_name = $('#selectedUnsouName').text();

                    var selectedDay = '<?php echo $selected_day; ?>';
                    var selectedSouko = '<?php echo $selectedSouko; ?>';
                    var url = './four.php?unsou_code=' + unsou_code + '&unsou_name=' + unsou_name + '&day=' + selectedDay + '&souko=' + selectedSouko;

                    console.log(url);

                    window.location.href = url;
                });

            })
        })(jQuery);
    </script>


</body>

</html>