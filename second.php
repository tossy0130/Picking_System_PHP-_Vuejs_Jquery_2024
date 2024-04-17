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
                        <button type="button" value="K倉庫" @click="handleButtonClick('K倉庫')" :class="{'selected_souko' : selectedValue === 'K倉庫'}">
                            K倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="N倉庫" @click="handleButtonClick('N倉庫')" :class="{'selected_souko' : selectedValue === 'N倉庫'}">
                            N倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="L倉庫" @click="handleButtonClick('L倉庫')" :class="{'selected_souko' : selectedValue === 'L倉庫'}">
                            L倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="P倉庫" @click="handleButtonClick('P倉庫')" :class="{'selected_souko' : selectedValue === 'P倉庫'}">
                            P倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="U倉庫" @click="handleButtonClick('U倉庫')" :class="{'selected_souko' : selectedValue === 'U倉庫'}">
                            U倉庫
                        </button>
                    </div>

                    <div>
                        <button type="button" value="W倉庫" @click="handleButtonClick('W倉庫')" :class="{'selected_souko' : selectedValue === 'W倉庫'}">
                            W倉庫
                        </button>
                    </div>

                </div>

                <div class="souko_box">
                    <div>
                        <button type="button" value="Q倉庫" @click="handleButtonClick('Q倉庫')" :class="{'selected_souko' : selectedValue === 'Q倉庫'}">
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
                        const url = `./third.
php?selectedSouko=${selectedSouko}&selected_day=${selectedDay}`; // リダイレクト
                        window.location.href = url;
                    }
                }
            }
        });
    </script>

</body>

</html>