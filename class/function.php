<?php

// === エスケープ処理
function h($var)
{
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
}


// === 文字分割（得意先名　用）
function SplitString_FUNC($inputString)
{
    $byte   = 6;            // 分割単位: byte
    $enc    = "SJIS";       // 文字コード
    //  $enc    = "UTF-8";       // 文字コード

    // 切り出し処理実行
    $result = mb_strcut($inputString, 0, $byte, $enc);

    if (mb_strlen($inputString) <= 40) {

        return array(
            mb_substr($inputString, 0, 20),
            mb_substr($inputString, 20)
        );
    } elseif (mb_strlen($inputString) <= 60) {

        return array(
            mb_substr($inputString, 0, 20),
            mb_substr($inputString, 20, 20),
            mb_substr($inputString, 40)
        );
    } elseif (mb_strlen($inputString) <= 80) {

        return array(
            mb_substr($inputString, 0, 20),
            mb_substr($inputString, 20, 20),
            mb_substr($inputString, 40, 60),
            mb_substr($inputString, 80)
        );
    } else {

        return array("Error: 文字列が長いです。");
    }
}

// === 運送便　単数 , 備考・特記あり　取得用
function getCondition($data)
{
    $pattern = '/AND\s+\(\([^()]+\)\s*(?:AND\s+\([^()]+\)\s*)*\)/';

    if (preg_match($pattern, $data, $matches)) {
        return $matches[0];
    } else {
        return "運送便（単数）No match found.";
    }
}

// === 運送便 複数 & 運送便、備考・特記あり　取得用
function getCondition_Multiple($data)
{
    // 正規表現パターンを修正
    $pattern = '/AND RZ\.倉庫Ｃ = :SELECT_SOUKO_02(.*?)AND NVL\(PK\.処理Ｆ,0\) <> 9 GROUP BY/s';

    if (preg_match($pattern, $data, $matches)) {
        $search_string_01 = "AND RZ.倉庫Ｃ = :SELECT_SOUKO_02";
        $search_string_02 = "AND NVL(PK.処理Ｆ,0) <> 9 GROUP BY";

        // マッチした部分から最初の検索文字列を取り除く
        if (strpos($matches[0], $search_string_01) !== false) {
            $tmp_multi_sql = str_replace($search_string_01, '', $matches[0]);
        } else {
            echo "RZ.倉庫Ｃ 含まれない" . "\n";
        }

        // マッチした部分から二つ目の検索文字列を取り除く
        if (strpos($matches[0], $search_string_02) !== false) {
            $result_multi_sql = str_replace($search_string_02, '', $tmp_multi_sql);
        } else {
            echo "NVL(PK.処理Ｆ,0) <> 9 GROUP BY 含まれない" . "\n";
        }

        // 結果を返します
        return trim($result_multi_sql);
    } else {
        return "運送便（複数）No match found.";
    }
}
// === デバッグ用プリント
function dprint($data)
{
    print($data);
}


// === PHP 8 対応 urlencode
function UrlEncode_Val_Check($data)
{
    if ($data != "") {
        $data_encode = urlencode($data);
        return $data_encode;
    } else {
        return $data = "";
    }
}
