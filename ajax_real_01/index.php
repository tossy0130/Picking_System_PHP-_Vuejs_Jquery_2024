<?php

ini_set('display_errors', 1);



?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <!-- jquery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <title>ajax リアルタイム 01</title>
</head>

<body>

    <div id="data">
    </div>

    <script>
        $(document).ready(function() {

            // 定期的に実行する関数
            function fetchData() {
                $.ajax({
                    ulr: './fetch_data.php',
                    type: 'GET',
                    success: function(response) {
                        $('#data').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', status, error);
                    }

                });
            }

            // === 初回実行
            fetchData();

            // === 〇秒毎に実行
            setInterval(fetchData, 10000); // 10秒

        });
    </script>

</body>

</html>