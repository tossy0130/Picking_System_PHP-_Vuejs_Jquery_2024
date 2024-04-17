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

    <title>ピッキング 04</title>
</head>

<body>

    <div id="app">
        <div class="container">
            <div class="content_04">
                <div class="head_01">

                    <div>
                        <?php echo h($souko); ?>
                    </div>

                    <div>
                        <?php echo h($company); ?>
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
                            <td>L-1</td>
                            <td>3 <br />
                                3</td>
                            <td>1<br />
                                1</td>
                            <td></td>
                            <td>スチールホースリール<br />
                                HR-L
                            </td>
                            <td>ケース</td>
                        </tr>

                        <tr>
                            <td>L-2</td>
                            <td>12</td>
                            <td>1</td>
                            <td>2</td>
                            <td>ステンレスポスト<br />
                                PS-30H
                            </td>
                            <td>シール</td>
                        </tr>

                        <tr style="background-color: bisque;">
                            <td>L-3</td>
                            <td>1 <br />
                                1</td>
                            <td>1<br />
                                1</td>
                            <td></td>
                            <td>ジェニアス30<br />
                                GR30GNF
                            </td>
                            <td></td>
                        </tr>

                        <tr style="background-color: yellow;">
                            <td>L-4</td>
                            <td>4 <br />
                                4</td>
                            <td>1<br />
                                1</td>
                            <td>1 <br />
                                1
                            </td>
                            <td>ホースリール<br />
                                PRQC30
                            </td>
                            <td>取扱い</td>
                        </tr>

                        <tr>
                            <td>L-5</td>
                            <td>3</td>
                            <td>1</td>
                            <td></td>
                            <td>コールマン<br />
                                20000x x x x
                            </td>
                            <td>関東</td>
                        </tr>

                        <tr>
                            <td>L-6</td>
                            <td>3</td>
                            <td>1</td>
                            <td></td>
                            <td>コールマン<br />
                                20000x x x x
                            </td>
                            <td>近畿</td>
                        </tr>

                        <tr>
                            <td>L-7</td>
                            <td>3</td>
                            <td>1</td>
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