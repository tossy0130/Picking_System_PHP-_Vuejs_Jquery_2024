<?php

ini_set('display_errors', 1);



?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プルダウン テスト</title>
</head>

<body>

    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>プルダウンメニュー</title>
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
            }

            .dropdown-content button:hover {
                background-color: #f1f1f1;
            }

            .show {
                display: block;
            }
        </style>
    </head>

    <body>
        <div id="app">

            <div class="dropdown" @click="toggleDropdown">
                <button class="dropbtn" value="1">トナミ運輸</button>
                <div class="dropdown-content" :class="{show: isOpen}">
                    <button type="button" value="1-1">シールあり</button>
                    <button type="button" value="1-2">ケース</button>
                    <button type="button" value="1-3">取扱い注意</button>
                    <button type="button" value="1-4">千趣会</button>
                </div>
            </div>

            <div class="dropdown" @click="toggleDropdown_02">
                <button class="dropbtn" value="2">佐川</button>
                <div class="dropdown-content" :class="{show: isOpen}">
                    <button type="button" value="2-1">シールあり</button>
                    <button type="button" value="2-2">ケース</button>
                    <button type="button" value="2-3">取扱い注意</button>
                    <button type="button" value="2-4">千趣会</button>
                </div>
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script>
            new Vue({
                el: '#app',
                data: {
                    isOpen: false
                },
                methods: {
                    toggleDropdown() {
                        this.isOpen = !this.isOpen;
                    },
                 methods: {
                        toggleDropdown_02() {
                            this.isOpen = !this.isOpen;
                        },
                    }
                }
            });
        </script>
    </body>

    </html>



</body>

</html>