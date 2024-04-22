<?php


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/login.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


    <title>ajax 01</title>
</head>

<body>

    <div class="app_name_box">
        <h1 class="app_name">
            APP アプリ名
        </h1>
    </div>

    <div class="page_title_box">
        <h2 class="page_title">
            ログイン
        </h2>
    </div>



    <div>
        <label for="selectedDate">日付選択:</label>
        <input type="date" id="selectedDate" name="selectedDate">
        <button id="sendDateBtn">開始</button>
    </div>

    <div id="result"></div>

    <!-- jQuery UI -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sendDateBtn').click(function() {
                var selectedDate = $('#selectedDate').val(); // 日付を取得
                $.ajax({
                    url: './second.php', // PHPファイルのパス
                    type: 'GET',
                    data: {
                        date: selectedDate
                    }, // 選択した日付をパラメーターとして送信
                    success: function(response) {
                        $('#result').html(response); // 結果を表示
                    }
                });
            });
        });
    </script>
</body>

</html>