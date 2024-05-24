<?php

ini_set('display_errors', 1);

require(dirname(__FILE__) . "\class/function.php");

// セッションスタート
session_start();

// セッションIDが一致しない場合はログインページにリダイレクト
if (!isset($_SESSION["sid"])) {
    header("Location: index.php");
    exit;
}

//出荷指示日時があるかどうかの判定
$GET_souko_Flg = 200;
if (isset($_GET['souko_Flg'])) {

    $souko_Flg = $_GET['souko_Flg'];

    if ($souko_Flg == 0) {
        $GET_souko_Flg = 0;
    }
}

// セッションハイジャック対策 
session_regenerate_id(TRUE);
// ************ 二重送信防止用トークンの発行 ************
$token_jim = uniqid('', true);
//トークンをセッション変数にセット
$_SESSION['token_jim'] = $token_jim;



?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- css -->
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.min.css">


    <!-- jQuery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <title>ピッキング実績照会出荷日指定</title>
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
                出荷日選択
            </span>
        </div>
    </div>

    <div class="container" id="app">
        <div class="content_top">


            <input name="day_val" type="text" id="datepicker" class="text_box_tpl_01" v-model="dayval">

            <!-- <div class="btn_01">
                <button id="day_search_submit" class="button_01" type="button" @click="submitForm">開始</button>
            </div> -->

            <div class="error-message" v-show="error">日付を入力してください。</div>

            <?php if ($GET_souko_Flg == 0) : ?>
                <p style="color:red;">
                    出荷指示されていません。
                </p>
            <?php endif; ?>
            <!-- フッターメニュー -->
            <footer class="footer-menu_fixed">
                <ul>
                    <li><a href="./top_menu.php">戻る</a></li>
                    <li><button id="day_search_submit" class="button_01" type="button" @click="submitForm">次へ</button></li>
                </ul>
            </footer>

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
                //var soukoFlg = <?php echo $GET_souko_Flg ?>;
                // 日付をYYYY/MM/DD形式に整形
                var formattedDate = selectedDate.replace(/年|月/g, '-').replace(/日/g, '');

                if (selectedDate.trim() === '') {
                    $(".error-message").show();
                } else {
                    $(".error-message").hide();
                    var url = "./seven.php?selected_day=" + encodeURIComponent(formattedDate);
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