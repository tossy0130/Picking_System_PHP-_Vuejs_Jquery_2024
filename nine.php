<?php

ini_set('display_errors', 1);

require __DIR__ . "\conf.php";

require_once(dirname(__FILE__) . "\class/init_val.php");
require(dirname(__FILE__) . "\class/function.php");


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
    /* if (isset($_GET['day'])) {

    } */
    $selected_day = isset($_GET['selected_day']) ? $_GET['selected_day'] : '';
    $selected_shippingname = isset($_GET['selected_shippingname']) ? $_GET['selected_shippingname'] : '';
    $selected_shippingcode = isset($_GET['selected_shippingcode']) ? $_GET['selected_shippingcode'] : '';
    $selected_soukocode = isset($_GET['selected_soukocode']) ? $_GET['selected_soukocode'] : '';
    $selected_soukoname = isset($_GET['selected_soukoname']) ? $_GET['selected_soukoname'] : '';
    
    /* echo "出荷日：$selected_day <br>";
    echo "運送便：$selected_shippingname <br>";
    echo "運送コード：$selected_shippingcode <br>";
    echo "倉庫コード：$selected_soukocode <br>";
    echo "倉庫名：$selected_soukoname <br>"; */


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
    }

        $sql_select = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SK.商品Ｃ,SH.品名 
                    ,RZ.棚番
                    ,SH.梱包入数
                    ,SUM(SK.出荷数量) AS 数量
                    ,SUM(PK.ピッキング数量) AS ピッキング数量
                    ,SH.ＪＡＮ
                    FROM SJTR SJ, SKTR SK, SOMF SO, USMF US,SHMF SH
                        ,RZMF RZ,HTPK PK
                        ,SLTR SL
                    WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
                    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
                    AND SL.伝票番号   = PK.伝票番号(+)
                    AND SL.伝票行番号 = PK.伝票行番号(+)
                    AND SL.伝票行枝番 = PK.伝票行枝番(+)
                    AND SK.倉庫Ｃ = SO.倉庫Ｃ
                    AND SK.運送Ｃ = US.運送Ｃ
                    AND SL.商品Ｃ = SH.商品Ｃ
                    AND SK.倉庫Ｃ = RZ.倉庫Ｃ
                    AND SK.商品Ｃ = RZ.商品Ｃ
                    AND SJ.出荷日 = :SELECT_DATE
                    AND US.運送略称 = :SELECT_UNSOUNAME   
                    AND SK.倉庫Ｃ = :SELECT_SOUKO
                    AND SK.運送Ｃ = :SELECT_UNSOU
                    AND SO.倉庫名 = :SELECT_SOUKONAME
                    AND PK.処理Ｆ = 9
                    GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SK.商品Ｃ,SH.品名,RZ.棚番,SH.梱包入数,SH.ＪＡＮ
                    ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SK.商品Ｃ";
        $stid_select = oci_parse($conn, $sql_select);
        if (!$stid_select) {
            $e = oci_error($stid_select);
        }

        oci_bind_by_name($stid_select, ":SELECT_DATE", $selected_day);
        oci_bind_by_name($stid_select, ":SELECT_UNSOUNAME", $selected_shippingname);
        oci_bind_by_name($stid_select, ":SELECT_SOUKO", $selected_soukocode);
        oci_bind_by_name($stid_select, ":SELECT_UNSOU", $selected_shippingcode);
        oci_bind_by_name($stid_select, ":SELECT_SOUKONAME", $selected_soukoname);

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
            $Shouhin_name_part1 = mb_substr($Shouhin_name, 0, 20);
            $Shouhin_name_part2 = mb_substr($Shouhin_name,20);
            $Tana_num = $row['棚番'];
            $Konpou_num = $row['梱包入数'];
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
                //'Shouhin_name' => $Shouhin_name,            // SH.品名
                'Shouhin_name_part1' => $Shouhin_name_part1,
                'Shouhin_name_part2' => $Shouhin_name_part2,
                'Tana_num' => $Tana_num,                    // RZ.棚番
                'Konpou_num' => $Konpou_num,                // SH.梱包入数
                'Shouhin_num' => $Shouhin_num,              // SUM(SK.出荷数量) AS 数量
                'Picking_num' => $Picking_num,              // SUM(PK.ピッキング数量) AS ピッキング数量
                'shouhin_JAN' => $shouhin_JAN               // JANコード
            );

            
            
        }
        
    }

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/nine.css">
    <!-- <link rel="stylesheet" href="./css/third.css"> -->
    <link rel="stylesheet" href="./css/common.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <!-- jQuery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <title>ピッキング実績照会照会画面</title>

    <style>
        .dropdown_02 {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            overflow: auto;
            border: 1px solid #ddd;
            z-index: 1;
        }

        .dropdown-content button {
            color: black;
            padding: 10px 12px;
            text-decoration: none;
            display: block;
            cursor: pointer;
            width: 100%;
            /* ボタンの幅を100%にする */
            text-align: left;
            /* ボタンのテキストを左寄せにする */
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        /* テーブル要素　ホバー */
        /* tr:hover {
            background: gray;
        } */

    </style>

</head>

<body>

    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="#"><i class="fa-solid fa-house"></i></a>
            </span>

            <span class="App_name">
                APP ピッキングアプリ
            </span>
        </div>
    </div>

    <div id="app">
        <div class="head_box_02">
            <div class="head_content_02">
                <span class="home_sub_icon_span">
                    <i class="fa-solid fa-thumbtack"></i>
                </span>

                <span class="page_title">
                    ピッキング実績照会
                </span>
            </div>
        </div>

        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <i class="fa-solid fa-warehouse"></i>
                        <span class="souko_icon_box">
                            <?php echo h($selected_soukoname); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_icon_box">
                            <i class="fa-solid fa-truck"></i>
                        </span>

                        <span class="unsou_text_box">
                            <?php echo h($selected_shippingname); ?>
                        </span>
                    </div>

                    

                </div>

                <div class="head_02">
                    <div>

                        <div  @click="toggleDropdown(1)">
                            <button class="dropbtn" id="order_btn" value="備考・特記">備考・特記</button>
                            <div class="dropdown-content" :class="{show: isOpen[1]}">
                                <button type="button" @click="handleButtonClick('ロケ順')">ロケ順</button>
                                <button type="button" @click="handleButtonClick('数量順')">数量順</button>
                                <button type="button" @click="handleButtonClick('特記順')">特記順</button>
                                <button type="button" @click="handleButtonClick('備考順')">備考順</button>
                            </div>
                        </div>

                    </div>
                    

                </div>

                <div>
                    <input type="number" id="get_JAN" name="get_JAN">
                    <p id="err_JAN" style="color:red;"></p>
                </div>

                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ==================================================== -->
            <!-- ============== テーブルレイアウト 開始 =============== -->
            <!-- ==================================================== -->
            <div class="">

                <table class="">

                    <thead>

                        <?php
                        foreach ($arr_Picking_DATA as $Picking_VAL) {
                            // 選択された項目の情報と比較して、背景色を設定

                            /* echo '<tr>';
                            echo '<td>品番: ' . $Picking_VAL['Shouhin_name_part2'] . '<br>JAN: ' . $Picking_VAL['shouhin_JAN'] . '</td>';
                            echo '<td style="text-align: right;">総数: ' . $Picking_VAL['Shouhin_num'] . '</td>';
                            echo '</tr>'; */
                            echo '<tr>';
                            echo '<td>品番: ' . $Picking_VAL['Shouhin_name_part2'] . '<span style="float: right;"> 総数:' . $Picking_VAL['Picking_num'] . '</span>' . '<br>' . '<span>JAN:</span>' . '<span class="shouhin_JAN">' . $Picking_VAL['shouhin_JAN'] . '</span>' .
                                '</td>';
                            echo '</tr>';

                        }
                        ?>

                    </thead>

                </table>


            </div> <!-- head_02 -->

        </div> <!-- ======== END container ========= -->

        <!-- フッターメニュー -->
        <footer class="footer-menu">
            <ul>
                <?php $back_flg = 1; ?>
                <?php $url = "./eight.php?selected_day=" . urlencode($selected_day) . "&selected_shippingname=" . urlencode($selected_shippingname) . "&selected_shippingcode="  . urlencode($selected_shippingcode); ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
            </ul>
        </footer>


    </div> <!-- ======== END app ========= -->

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
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
        $(document).ready(function() {

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

            // 現在のJANコードにフォーカスを当てたテーブル行のHTML要素を保持する変数
            var focusedTableRow = null;

            // JAN 判定
            $('#get_JAN').change(function() {
                //  現在のフォーカスされているテーブル行のフォーカスを外す
                if (focusedTableRow !== null) {
                    focusedTableRow.css("background-color", "");
                }

                var input_JAN = $('#get_JAN').val();
                var convertedValue_JAN = convertToHalfWidth(input_JAN);
                console.log("読み込んだ値：" + convertedValue_JAN);
                $(this).val(convertedValue_JAN);

                // JAN コード判定
                $(".shouhin_JAN").each(function() {
                    console.log("item:::" + $(this).text() + "\n");
                    var shouhin_JAN = $(this).text();
                    console.log(shouhin_JAN);

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
                        return false;

                    }

                });

                // JAN エラーメッセージ
                if (Jan_Flg === 0) {
                    $('#err_JAN').html("JAN コードに一致する商品がありません。<br>値：(" + $('#get_JAN').val() + ")");
                    $('#get_JAN').val("");
                    $('#get_JAN').focus();
                } else if (Jan_Flg === 1) {
                    $('#err_JAN').html("JAN コードと一致している商品があります。<br>値:(" + $('#get_JAN').val() + ")");
                    $('#get_JAN').val("");
                    //$(this).focus();
                }
            });

        });
    </script>


</body>

</html>