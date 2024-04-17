<?php


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


    <title>ajax 01</title>
</head>

<body>

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