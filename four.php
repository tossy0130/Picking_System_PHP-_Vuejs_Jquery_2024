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

    // ================= 通常処理 =================

    if (isset($_GET['day'])) {
        $select_day = $_GET['day'];
        $select_souko_code = $_GET['souko'];
        $get_souko_name = $_GET['get_souko_name'];
        $select_unsou_code = $_GET['unsou_code'];
        $get_unsou_name = $_GET['unsou_name'];
    }

    // ソート用キー取得
    $sortKey = "";
    if (isset($_GET['sort_key'])) {
        $sortKey = $_GET['sort_key'];
        print($sortKey);
    }

    if (isset($_GET['back_flg'])) {
        $back_flg = $_GET['back_flg'];
        print($back_flg);
    }




    //  $k_kb = $_GET['k_kb']; // === 運送便 区分

    /*
    print($select_day);
    print($select_souko);
    print($select_unsou_code);
    */

    // ============================= DB 処理（テーブル存在チェック）=============================
    // === 接続準備

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
    }

    oci_free_statement($stmt);
    oci_close($conn);


    // ============================= DB 処理 =============================
    // === 接続準備
    $conn = oci_connect(DB_USER, DB_PASSWORD, DB_CONNECTION_STRING, DB_CHARSET);

    if (!$conn) {
        $e = oci_error();
    }

    switch ($sortKey) {
        case 'location_note':
            $sql =
                "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名,SUM(SK.出荷数量) AS 数量,SJ.得意先名
	  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
		 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
		   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
		   AND SK.倉庫Ｃ = SO.倉庫Ｃ
		   AND SL.出荷元 = SM.出荷元Ｃ(+)
		   AND SK.運送Ｃ = US.運送Ｃ
		   AND SL.商品Ｃ = SH.商品Ｃ
		   AND SJ.出荷日 = :SELECT_DATE
           AND SK.倉庫Ｃ = :SELECT_SOUKO
           AND SK.運送Ｃ = :SELECT_UNSOU
         GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,
			SK.商品Ｃ,SH.品名,SJ.得意先名
         ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";


            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

            oci_execute($stid);

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
                $Shouhin_num = $row['数量'];
                $Tokuisaki_name = $row['得意先名'];


                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,
                    'souko_code' => $souko_code,
                    'souko_name' => $souko_name,
                    'Unsou_code' => $Unsou_code,
                    'Unsou_name' => $Unsou_name,
                    'shipping_moto' => $shipping_moto,
                    'shipping_moto_name' => $shipping_moto_name,
                    'Shouhin_code' => $Shouhin_code,
                    'Shouhin_name' => $Shouhin_name,
                    'Shouhin_num' => $Shouhin_num,
                    'Tokuisaki_name' => $Tokuisaki_name
                );

                // var_dump($arr_Picking_DATA);
            }

            break;

        case 'num_note':

            $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名,SUM(SK.出荷数量) AS 数量,SJ.得意先名
	  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
		 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
		   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
		   AND SK.倉庫Ｃ = SO.倉庫Ｃ
		   AND SL.出荷元 = SM.出荷元Ｃ(+)
		   AND SK.運送Ｃ = US.運送Ｃ
		   AND SL.商品Ｃ = SH.商品Ｃ
		   AND SJ.出荷日 = :SELECT_DATE
           AND SK.倉庫Ｃ = :SELECT_SOUKO
           AND SK.運送Ｃ = :SELECT_UNSOU
         GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,
			SK.商品Ｃ,SH.品名,SJ.得意先名
         ORDER BY 数量 desc,SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

            print($select_day . "<br />");
            print($select_souko_code . "<br />");
            print($select_unsou_code . "<br />");

            oci_execute($stid);

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
                $Shouhin_num = $row['数量'];
                $Tokuisaki_name = $row['得意先名'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,
                    'souko_code' => $souko_code,
                    'souko_name' => $souko_name,
                    'Unsou_code' => $Unsou_code,
                    'Unsou_name' => $Unsou_name,
                    'shipping_moto' => $shipping_moto,
                    'shipping_moto_name' => $shipping_moto_name,
                    'Shouhin_code' => $Shouhin_code,
                    'Shouhin_name' => $Shouhin_name,
                    'Shouhin_num' => $Shouhin_num,
                    'Tokuisaki_name' => $Tokuisaki_name
                );

                // var_dump($arr_Picking_DATA);
            }

            break;
        case 'tokki_note':
            $sql = " ORDER BY SM.出荷元名";
            break;
        case 'bikou_note':
            $sql = " ORDER BY SK.商品Ｃ";
            break;
        default:
            // デフォルトの並べ替え
            $sql = "SELECT SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,SK.商品Ｃ,SH.品名,SUM(SK.出荷数量) AS 数量, SJ.得意先名
	  FROM SJTR SJ, SKTR SK, SOMF SO, SLTR SL, SMMF SM, USMF US,SHMF SH
		 WHERE SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
		   AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
		   AND SK.倉庫Ｃ = SO.倉庫Ｃ
		   AND SL.出荷元 = SM.出荷元Ｃ(+)
		   AND SK.運送Ｃ = US.運送Ｃ
		   AND SL.商品Ｃ = SH.商品Ｃ
		   AND SJ.出荷日 = :SELECT_DATE
           AND SK.倉庫Ｃ = :SELECT_SOUKO
           AND SK.運送Ｃ = :SELECT_UNSOU
         GROUP BY SK.出荷日,SK.倉庫Ｃ,SO.倉庫名,SK.運送Ｃ,US.運送略称,SL.出荷元,SM.出荷元名,
			SK.商品Ｃ,SH.品名,SJ.得意先名
         ORDER BY SK.倉庫Ｃ,SK.運送Ｃ,SM.出荷元名,SK.商品Ｃ,SL.出荷元";

            $stid = oci_parse($conn, $sql);
            if (!$stid) {
                $e = oci_error($stid);
            }

            oci_bind_by_name($stid, ":SELECT_DATE", $select_day);
            oci_bind_by_name($stid, ":SELECT_SOUKO", $select_souko_code);
            oci_bind_by_name($stid, ":SELECT_UNSOU", $select_unsou_code);

            oci_execute($stid);

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
                $Shouhin_num = $row['数量'];
                $Tokuisaki_name = $row['得意先名'];

                // 取得した値を配列に追加
                $arr_Picking_DATA[] = array(
                    'syuka_day' => $syuka_day,
                    'souko_code' => $souko_code,
                    'souko_name' => $souko_name,
                    'Unsou_code' => $Unsou_code,
                    'Unsou_name' => $Unsou_name,
                    'shipping_moto' => $shipping_moto,
                    'shipping_moto_name' => $shipping_moto_name,
                    'Shouhin_code' => $Shouhin_code,
                    'Shouhin_name' => $Shouhin_name,
                    'Shouhin_num' => $Shouhin_num,
                    'Tokuisaki_name' => $Tokuisaki_name
                );

                // var_dump($arr_Picking_DATA);
            }
    }
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

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <!-- jQuery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <title>ピッキング 04</title>

    <style>
        /* ドロップダウンリスト */
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
            left: -75%;
        }

        .dropdown-content button {
            color: black;
            padding: 10px 12px;
            text-decoration: none;
            display: block;
            cursor: pointer;
            width: 100%;
            text-align: left;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        /* テーブル要素　ホバー */
        tr:hover {
            background: gray;
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
                    ピッキング対象選択
                </span>
            </div>
        </div>

        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <i class="fa-solid fa-warehouse"></i>
                        <span class="souko_icon_box">
                            <?php echo h($get_souko_name); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_icon_box">
                            <i class="fa-solid fa-truck"></i>
                        </span>

                        <span class="unsou_text_box">
                            <?php echo h($get_unsou_name); ?>
                        </span>
                    </div>

                </div>

                <div class="four_flex">
                    <div>

                        <p id="login_id_view">出荷日：<?= $arr_Picking_DATA[0]["syuka_day"]; ?></p>

                    </div>
                    <div class="" id="menu_btn_box">

                        <div class="dropdown_02" @click="toggleDropdown(1)">
                            <button class="dropbtn" id="order_btn" value="並替">並替</button>
                            <div class="dropdown-content" :class="{show: isOpen[1]}">
                                <button type="button" @click="handleButtonClick('location_note')">ロケ順</button>
                                <button type="button" @click="handleButtonClick('num_note')">数量順</button>
                                <button type="button" @click="handleButtonClick('tokki_note')">特記順</button>
                                <button type="button" @click="handleButtonClick('bikou_note')">備考順</button>
                            </div>
                        </div>



                        <div>
                            <button type="button" id="all_select_btn">
                                全表示
                            </button>
                        </div>

                    </div>
                </div>




                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ==================================================== -->
            <!-- ============== テーブルレイアウト 開始 =============== -->
            <!-- ==================================================== -->
            <div class="">

                <table class="" border="1">

                    <thead>
                        <tr>
                            <th>ロケ</th>
                            <th>数量</th>
                            <th>ケース</th>
                            <th>バラ</th>
                            <th>品名・品番</th>
                            <th>特記・備考</th>
                        </tr>

                        <?php
                        foreach ($arr_Picking_DATA as $Picking_VAL) {

                            $shouhin_name_part1 = mb_substr($Picking_VAL['Shouhin_name'], 0, 20);
                            $shouhin_name_part2 = mb_substr($Picking_VAL['Shouhin_name'], 20);

                            echo '<tr data-href="./five.php?select_day=' . $select_day . '&souko_code=' . $select_souko_code . '&unsou_code=' . $select_unsou_code . '&unsou_name=' . $Picking_VAL['Unsou_name'] . '&shipping_moto=' . $Picking_VAL['shipping_moto'] . '&shipping_moto_name=' . $Picking_VAL['shipping_moto_name'] . '&Shouhin_code=' . $Picking_VAL['Shouhin_code'] . '&Shouhin_name=' . $Picking_VAL['Shouhin_name'] . '&Shouhin_num=' . $Picking_VAL['Shouhin_num'] . '&Tokuisaki=' . $Tokuisaki_name . '">';
                            echo '<td></td>';
                            echo '<td>' . $Picking_VAL['Shouhin_num'] . "</td>";
                            echo '<td></td>';
                            echo '<td></td>';
                            echo '<td>' . $shouhin_name_part1 . '<br />' . $shouhin_name_part2 . "</td>";
                            echo '<td>' . $Picking_VAL['shipping_moto_name'] . "</td>";
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
                <?php $url = "./third.php?selectedSouko=" . urlencode($select_souko_code) . "&selected_day=" . urlencode($select_day) . "&souko_name=" . urlencode($get_souko_name) . "&back_flg=" . $back_flg; ?>
                <li><a href="<?php echo $url; ?>">戻る</a></li>
                <li><a href="#">更新</a></li>
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

                    var selectedDay = '<?php echo $select_day; ?>';
                    var select_souko_code = '<?php echo $select_souko_code; ?>';
                    var get_unsou_name = '<?php echo $get_unsou_name; ?>';
                    var select_unsou_code = '<?php echo $select_unsou_code; ?>';
                    var get_souko_name = '<?php echo $get_souko_name; ?>';


                    window.location.href = window.location.pathname + '?unsou_code=' + select_unsou_code + '&unsou_name=' + get_unsou_name + '&day=' + selectedDay + '&souko=' + select_souko_code + '&get_souko_name=' + get_souko_name + '&sort_key=' + this.selectValue;
                    // window.location.href = window.location.pathname + '?sort_key=' + this.selectValue;
                    // window.location.href = './four.php?sort_key=' + this.selectedValue;
                }
            },

        });
    </script>

    <script>
        $(document).ready(function() {

            $('table tbody').on('click', 'tr', function() {
                var row = $(this).closest('tr');
                var Shouhin_num = row.find('td:eq(1)').text().trim();
                var Shouhin_name = row.find('td:eq(4)').text().trim();
                var shipping_moto_name = row.find('td:eq(5)').text().trim();

                console.log("Shouhin_num");
                console.log("Shouhin_name");
                console.log("shipping_moto_name");

                // 取得した値を詳細画面へ渡して遷移
                // window.location.href = 'detail.php?Shouhin_num=' + Shouhin_num + '&Shouhin_name=' + Shouhin_name + '&shipping_moto_name=' + shipping_moto_name;
            });
        });
    </script>

    <script>
        $('tr[data-href]').click(function() {

            var href = $(this).data('href');
            console.log("リンク値:::" + href);

            window.location.href = href;
        });
    </script>


</body>

</html>