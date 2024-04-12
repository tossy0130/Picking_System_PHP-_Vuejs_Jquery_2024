<?php

ini_set('display_errors', 1);

require(dirname(__FILE__) . "./class/function.php");

// セッションスタート
session_start();

// セッションハイジャック対策 
session_regenerate_id(TRUE);
// ************ 二重送信防止用トークンの発行 ************
$token_jim = uniqid('', true);
//トークンをセッション変数にセット
$_SESSION['token_jim'] = $token_jim;

// セッションIDを取得
$sid = session_id();
print("session_id:::" . $sid . "<br />");

/*
$newid = session_create_id('myprefix-');
print("newid:::" . $newid . "<br />");

print("token_jim:::" . $token_jim);
*/

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/common.css">

    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.min.css">


    <!-- jQuery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <title>トップ</title>
</head>

<body>

    <div class="container" id="app">
        <div class="content_top">

            <h1>ピッキング</h1>
            <h2>出荷日</h2>

            <!-- POST 送信処理 
            <form name="day_search" id="day_search" method="post" action="./second.php" @submit.prevent="submitForm">
                <input name="day_val" type="text" id="datepicker" v-model="dayval">

                <div class="btn_01">
                    <button id="day_search_submit" type="submit">開始</button>
                </div>

                <input type="hidden" name="token_jim" value="">

            </form>
            -->

            <input name="day_val" type="text" id="datepicker" v-model="dayval">

            <div class="btn_01">
                <button id="day_search_submit" type="button" @click="submitForm">開始</button>
            </div>

            <div class="error-message" v-show="error">日付を入力してください。</div>

        </div>
    </div> <!-- ================ END container =============== -->



    <!-- jQuery UI -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <!-- Vue.js -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

    <script type="text/javascript">
        (function($) {

            $(document).ready(function() {

                $("#datepicker").datepicker({
                    buttonText: "日付を選択",
                    showOn: "both",
                    onSelect: function(selectedDate) {
                        app.dayval = selectedDate; // 選択された日付をVueのdataに反映
                    }
                });


                $.datepicker.regional['ja'] = {
                    closeText: '閉じる',
                    prevText: '<前',
                    nextText: '次>',
                    currentText: '今日',
                    monthNames: ['1月', '2月', '3月', '4月', '5月', '6月',
                        '7月', '8月', '9月', '10月', '11月', '12月'
                    ],
                    monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月',
                        '7月', '8月', '9月', '10月', '11月', '12月'
                    ],
                    dayNames: ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
                    dayNamesShort: ['日', '月', '火', '水', '木', '金', '土'],
                    dayNamesMin: ['日', '月', '火', '水', '木', '金', '土'],
                    weekHeader: '週',
                    dateFormat: 'yy年mm月dd日',
                    firstDay: 0,
                    isRTL: false,
                    showMonthAfterYear: true,
                    yearSuffix: '年'
                };
                $.datepicker.setDefaults($.datepicker.regional['ja']);

            });


            $("#day_search_submit").click(function() {
                var selectedDate = $("#datepicker").val();
                // 日付をYYYY/MM/DD形式に整形
                var formattedDate = selectedDate.replace(/年|月/g, '-').replace(/日/g, '');

                if (selectedDate.trim() === '') {
                    $(".error-message").show();
                } else {
                    $(".error-message").hide();
                    var url = "./second.php?selected_day=" + encodeURIComponent(formattedDate);
                    // リダイレクト
                    window.location.href = url;
                }
            });

        })(jQuery);
    </script>

    <!-- Vue.js
    <script>
        new Vue({
            el: '#app',
            data: {
                dayval: '',
                error: false
            },
            methods: {
                submitForm() {
                    if (this.dayval.trim() === '') {
                        console.log('空');
                        this.error = true;
                        return;
                    } else {
                        console.log(this.dayval);
                        this.error = false;
                        const url = `./second.php?selected_day=${encodeURIComponent(this.dayval)}`;
                        // リダイレクト
                        window.location.href = url;
                    }

                }
            }

        });
    </script>
    -->

</body>

</html>