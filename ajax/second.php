<?php

if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
    echo "日付: " . $selected_date;
} else {
    echo "データ取得できません。";
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ajax 02</title>
</head>

<body>

</body>

</html>