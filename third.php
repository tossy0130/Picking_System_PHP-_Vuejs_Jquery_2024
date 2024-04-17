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
    // ========= 通常処理 =========
    if (isset($_GET['selectedSouko'])) {
        $selectedSouko = $_GET['selectedSouko'];
        print($selectedSouko . "<br />");

        $selected_day = $_GET['selected_day'];
        // print($selected_day . "<br />");
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

    <title>ピッキング 03</title>
</head>

<body>

    <div id="app">

        <div class="dropdown" @click="toggleDropdown(1)">
            <button class="dropbtn" value="トナミ運輸">トナミ運輸</button>
            <div class="dropdown-content" :class="{show: isOpen[1]}">
                <button type="button" @click="handleButtonClick('トナミ運輸', 'シールあり')">シールあり</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', 'ケース')">ケース</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', '取扱い注意')">取扱い注意</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', '千趣会')">千趣会</button>
            </div>
        </div>

        <div class="dropdown" @click="toggleDropdown(2)">
            <button class="dropbtn" value="佐川">佐川</button>
            <div class="dropdown-content" :class="{show: isOpen[2]}">
                <button type="button" @click="handleButtonClick('佐川', 'シールあり')">シールあり</button>
                <button type="button" @click="handleButtonClick('佐川', 'ケース')">ケース</button>
                <button type="button" @click="handleButtonClick('佐川', '取扱い注意')">取扱い注意</button>
                <button type="button" @click="handleButtonClick('佐川', '千趣会')">千趣会</button>
            </div>
        </div>

        <div class="dropdown" @click="toggleDropdown(3)">
            <button class="dropbtn" value="西濃運輸">西濃運輸</button>
            <div class="dropdown-content" :class="{show: isOpen[3]}">
                <button type="button" @click="handleButtonClick('西濃運輸', 'シールあり')">シールあり</button>
                <button type="button" @click="handleButtonClick('西濃運輸', 'ケース')">ケース</button>
                <button type="button" @click="handleButtonClick('西濃運輸', '取扱い注意')">取扱い注意</button>
                <button type="button" @click="handleButtonClick('西濃運輸', '千趣会')">千趣会</button>
            </div>
        </div>

        <!-- 選択した値を表示する部分 -->
        <div class="selected-value">
            選択した会社: {{ selectedCompany }}
            <br>
            選択した値: {{ selectedValue }}
        </div>

        <button @click="sendSelectedValues">次へ</button>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data: {
                isOpen: {
                    1: false, // トナミ運輸
                    2: false, // 佐川
                    3: false, // 西濃運輸
                },
                selectedCompany: '', // 選択した会社を保持する変数
                selectedValue: '' // 選択した値を保持する変数
            },
            methods: {
                toggleDropdown(menuId) {
                    this.isOpen[menuId] = !this.isOpen[menuId];
                },
                // プルダウンメニューのボタンがクリックされたときに実行されるメソッド
                handleButtonClick(company, value) {
                    this.selectedCompany = company; // 選択した会社を表示する変数にセット
                    this.selectedValue = value; // 選択した値を表示する変数にセット
                },
                // GET で送信
                sendSelectedValues() {
                    var selectedCompany = this.selectedCompany;
                    var selectedValue = this.selectedValue;

                    const selectedDay = '<?php echo $selected_day; ?>';
                    const selectedSouko = '<?php echo $selectedSouko; ?>';

                    var url = './four.php?' + "day=" + selectedDay + '&souko=' + selectedSouko + '&company=' + selectedCompany + '&k_kb=' + selectedValue;
                    window.location.href = url;
                }
            }
        });
    </script>

</body>

</html>