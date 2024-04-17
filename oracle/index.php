<?php

ini_set('display_errors', 1);

// mb_internal_encoding("Shift-JIS");

// header('Content-Type: text/html; charset=Shift-JIS');

// require(dirname(__FILE__) . "/class/init_val.php");

// 接続情報
$host = '192.168.254.16';
$port = '1521';
$dbname = 'ORCL.WORLD';
$username = 'GL';
$password = 'GL';

// Oracle データベース接続
// $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$dbname})));charset=UTF8";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$dbname})))";
try {
    // Oracleデータベースに接続
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // データを取得するSELECT文
    // $selectQuery = "SELECT * FROM (SELECT * FROM HCTR) WHERE ROWNUM <= 100";

    // SQLクエリを準備して実行
    $stmt = $conn->prepare("SELECT * FROM CN01");
    $stmt->execute();

    // 結果を取得して表示
    /*
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "自社名: " . $row['自社名'] . ", 自社住所１: " . $row['自社住所１'] . "<br/>";
    }
    */

    // 取得した結果を表示
    // 出力OK header('Content-Type: text/html; charset=Shift-JIS');を使うと

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        // echo "自社名: " . mb_convert_encoding($row['自社名'], 'UTF-8', 'Shift_JIS') . ", 自社住所１: " . mb_convert_encoding($row['自社住所１'], 'UTF-8', 'Shift_JIS') . "<br/>";
        echo "自社名: " . mb_convert_encoding($row['自社名'], 'UTF-8', 'Shift_JIS') . ", 自社住所１: " . mb_convert_encoding($row['自社住所１'], 'UTF-8', 'Shift_JIS') . "<br/>";
    }



    // 結果を取得して表示
    // 出力OK （）
    /*
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($result); // または print_r($result
    */

    // 接続を閉じる
    $conn = null;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>

    <meta charset="UTF-8">

    <!-- 
    <meta charset="Shift-JIS">
        -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>oracle 接続テスト</title>
</head>

<body>

</body>

</html>