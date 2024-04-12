<?php

ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "./class/init_val.php");
require(dirname(__FILE__) . "./class/function.php");

// === 外部定数セット
$err_url = Init_Val::ERR_URL;
$top_url = Init_Val::TOP_URL;

// セッションスタート
session_start();

// セッションIDを取得
$session_id = session_id();

// session判定
if (empty($session_id)) {
    // *** セッションIDがないので、リダイレクト
    header("Location: $top_url");
} else {
    // ========= 通常処理 =========
    if (isset($_GET['selected_day'])) {

        // === 日付
        $selected_day = $_GET['selected_day'];
        print($selected_day);
    } else {
        // === トークンが無い場合
        header("Location: $err_url");
    }
}


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/second.css">

    <title>ピッキング 2</title>

    <style>
        /* クリックされたボタンのスタイル */
        .selected_souko {
            background-color: red;
            color: #fff;
        }
    </style>

</head>

<body>

    <div id="app">
        <div class="container">
            <div class="content_02">

                <h1>ピッキング倉庫</h1>

                <div class="souko_box">
                    <div>
                        <button type="button" value="1" @click="handleButtonClick(1)" :class="{'selected_souko' : selectedValue === 1}">
                            K倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="2" @click="handleButtonClick(2)" :class="{'selected_souko' : selectedValue === 2}">
                            N倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="3" @click="handleButtonClick(3)" :class="{'selected_souko' : selectedValue === 3}">
                            L倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="4" @click="handleButtonClick(4)" :class="{'selected_souko' : selectedValue === 4}">
                            P倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="5" @click="handleButtonClick(5)" :class="{'selected_souko' : selectedValue === 5}">
                            U倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="6" @click="handleButtonClick(6)" :class="{'selected_souko' : selectedValue === 6}">
                            W倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="7" @click="handleButtonClick(7)" :class="{'selected_souko' : selectedValue === 7}">
                            Q倉庫
                        </button>
                    </div>

                </div>

                <div id="next_btn">
                    <button @click="submitForm">次へ</button>
                </div>

                <div class="error_message" v-show="error">
                    倉庫を選択してください。
                </div>

            </div>
        </div> <!-- END container -->
    </div> <!-- END app -->

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

    <script>
        new Vue({
            el: '#app',
            data: {
                selectedValue: null, // 選択された値を保持
                error: false
            },
            methods: {
                // ボタンがクリックされたら
                handleButtonClick(value) {
                    this.selectedValue = value; // 選択した値を格納
                    console.log("選択した値:::" + this.selectedValue);
                },
                // フォームを送信する
                submitForm() {
                    const selectedSouko = this.selectedValue;
                    const selectedDay = '<?php echo $selected_day; ?>';
                    if (selectedSouko === null) { // 倉庫が選択されていない場合
                        this.error = true; // エラーメッセージを表示

                    } else {
                        this.error = false; // エラーメッセージを非表示に
                        // get送信
                        const url = `./third.php?selectedSouko=${selectedSouko}&selected_day=${selectedDay}`;
                        // リダイレクト
                        window.location.href = url;
                    }
                }
            }
        });
    </script>

</body>

</html>