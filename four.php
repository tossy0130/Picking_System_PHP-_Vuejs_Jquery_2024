<?php

ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

session_start();

// セッションIDを取得
$session_id = session_id();

// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {

    // ================= 通常処理 =================

    $day = $_GET['day'];
    $souko = $_GET['souko'];
    $company = $_GET['company'];
    $k_kb = $_GET['k_kb']; // === 運送便 区分

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

    <title>ピッキング 04</title>
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

    <div id="app">
        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <i class="fa-solid fa-warehouse"></i>
                        <span class="souko_icon_box">
                            <?php echo h($souko); ?>
                        </span>
                    </div>

                    <div>
                        <span class="unsou_icon_box">
                            <i class="fa-solid fa-truck"></i>
                        </span>

                        <span class="unsou_text_box">
                            <?php echo h($company); ?>
                        </span>
                    </div>

                </div>

                <div class=" head_02">

                    <div>

                        <div class="dropdown_02" @click="toggleDropdown(1)">
                            <button class="dropbtn" id="order_btn" value="並替">並替</button>
                            <div class="dropdown-content" :class="{show: isOpen[1]}">
                                <button type="button" @click="handleButtonClick('ロケ順')">ロケ順</button>
                                <button type="button" @click="handleButtonClick('数量順')">数量順</button>
                                <button type="button" @click="handleButtonClick('特記順')">特記順</button>
                                <button type="button" @click="handleButtonClick('備考順')">備考順</button>
                            </div>
                        </div>

                    </div>

                    <div>
                        <button type="button" id="all_select_btn">
                            全表示
                        </button>
                    </div>

                </div>

                <hr class="hr_01">

            </div> <!-- head_01 END -->

            <!-- ============== テーブルレイアウト 開始 =============== -->
            <div class="head_02">

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

                        <tr style="background-color: bisque;">
                            <td class="font_tmp_01">L-1</td>
                            <td class="bold">3 <br />
                                3</td>
                            <td class="bold">1<br />
                                1</td>
                            <td></td>
                            <td>スチールホースリールセット２５ｍ(ﾌﾞﾗｳﾝ)<br />
                                ＨＲ－Ｌ２５Ｄ（ＢＲ）－ＡＺ
                            </td>
                            <td>ケース</td>
                        </tr>

                        <tr>
                            <td class="font_tmp_01">L-2</td>
                            <td class="bold">12</td>
                            <td class="bold">1</td>
                            <td class="bold">2</td>
                            <td>ステンレスポスト<br />
                                PS-30H
                            </td>
                            <td>シール</td>
                        </tr>

                        <tr style="background-color: bisque;">
                            <td class="font_tmp_01">L-3</td>
                            <td class="bold">1 <br />
                                1</td>
                            <td class="bold">1<br />
                                1</td>
                            <td></td>
                            <td>ジェニアス30<br />
                                GR30GNF
                            </td>
                            <td></td>
                        </tr>

                        <tr style="background-color: yellow;">
                            <td class="font_tmp_01">L-4</td>
                            <td class="bold">4 <br />
                                4</td>
                            <td class="bold">1<br />
                                1</td>
                            <td class="bold">1 <br />
                                1
                            </td>
                            <td>ホースリール<br />
                                PRQC30
                            </td>
                            <td>取扱い</td>
                        </tr>

                        <tr>
                            <td class="font_tmp_01">L-5</td>
                            <td class="bold">3</td>
                            <td class="bold">1</td>
                            <td></td>
                            <td>コールマン<br />
                                20000x x x x
                            </td>
                            <td>関東</td>
                        </tr>

                        <tr>
                            <td class="font_tmp_01">L-6</td>
                            <td class="bold">3</td>
                            <td class="bold">1</td>
                            <td></td>
                            <td>コールマン<br />
                                20000x x x x
                            </td>
                            <td>近畿</td>
                        </tr>

                        <tr>
                            <td class="font_tmp_01">L-7</td>
                            <td class="bold">3</td>
                            <td class="bold">1</td>
                            <td></td>
                            <td>コールマン<br />
                                20000x x x x
                            </td>
                            <td>静岡</td>
                        </tr>

                    </thead>

                </table>


            </div> <!-- head_02 -->

        </div> <!-- ======== END container ========= -->



        <!-- フッターメニュー -->
        <footer class="footer-menu">
            <ul>
                <li><a href="#">戻る</a></li>
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
                }
            },

        });
    </script>

</body>

</html>