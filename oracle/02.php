<?php

ini_set('display_errors', 1);

// 接続情報
$host = '192.168.254.16';
$port = '1521';
$dbname = 'ORCL.WORLD';
$username = 'GL';
$password = 'GL';
$DB_CHARSET = 'AL32UTF8';

// Oracle データベース接続

// $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$dbname})))";

$con_string = $host . ':' . $port . '/' . $dbname;
$conn = oci_connect($username, $password, $con_string, $DB_CHARSET);

// === oci-8での接続方法 文字化け
// $conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$dbname})))");

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// SQLクエリを準備して実行
$sql = "SELECT * FROM CN01";
$statement = oci_parse($conn, $sql);
oci_execute($statement);

// 結果を取得して表示
$data = array();
while ($row = oci_fetch_assoc($statement)) {
    // カラム名を指定して値を取得
    $companyName = $row['掛率限度'];
    $address = $row['登録日'];

    // ここで取得した値を処理する（例えば、表示する）
    echo "掛率限度: " . $companyName . ", 登録日: " . $address . "<br/>";

    // 取得した値を配列に追加
    $data[] = array(
        '自社名' => $companyName,
        '自社住所１' => $address
    );
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oracle 接続 02</title>
</head>

<body>

</body>

</html>