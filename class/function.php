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

        return array("Error: Too long string");
    }
}
