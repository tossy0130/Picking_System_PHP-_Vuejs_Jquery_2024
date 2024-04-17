<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>複数のプルダウンメニュー</title>
    <style>
        /* プルダウンメニューのスタイル */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content button {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            border: none;
            background: none;
            text-align: left;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        /* 選択した値の表示スタイル */
        .selected-value {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div id="app">

        <div class="dropdown" @click="toggleDropdown(1)">
            <button class="dropbtn" value="1">トナミ運輸</button>
            <div class="dropdown-content" :class="{show: isOpen[1]}">
                <button type="button" @click="handleButtonClick('トナミ運輸', 'シールあり')">シールあり</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', 'ケース')">ケース</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', '取扱い注意')">取扱い注意</button>
                <button type="button" @click="handleButtonClick('トナミ運輸', '千趣会')">千趣会</button>
            </div>
        </div>

        <div class="dropdown" @click="toggleDropdown(2)">
            <button class="dropbtn" value="2">佐川</button>
            <div class="dropdown-content" :class="{show: isOpen[2]}">
                <button type="button" @click="handleButtonClick('佐川', 'シールあり')">シールあり</button>
                <button type="button" @click="handleButtonClick('佐川', 'ケース')">ケース</button>
                <button type="button" @click="handleButtonClick('佐川', '取扱い注意')">取扱い注意</button>
                <button type="button" @click="handleButtonClick('佐川', '千趣会')">千趣会</button>
            </div>
        </div>

        <div class="dropdown" @click="toggleDropdown(3)">
            <button class="dropbtn" value="3">西濃運輸</button>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
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
                }
            }
        });
    </script>
</body>

</html>