<?php

ini_set('display_errors', 1);

require(dirname(__FILE__) . "../class/init_val.php");

// 接続情報
$host = Init_Val::GLhost;
$port = Init_Val::GLport;
$dbname = Init_Val::GLdbname;
$username = Init_Val::GLusername;
$password = Init_Val::GLpassword;

// Oracle データベース接続
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$dbname})))";

try {
    // Oracleデータベースに接続
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // データを取得するSELECT文
    $selectQuery = "SELECT * FROM HCTR";

    // SELECT文を実行してデータを取得
    $stmt = $pdo->query($selectQuery);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 取得したデータを表示用のHTMLに変換して出力
    echo "<table>";
    echo "<tr><th>伝票ＳＥＱ</th><th>伝票番号</th></tr>";
    foreach ($result as $row) {
        echo "<tr>";
        echo "<td>" . $row['伝票ＳＥＱ'] . "</td>";
        echo "<td>" . $row['伝票番号'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ajax リアルタイム 02</title>
</head>

<body>

</body>

</html>