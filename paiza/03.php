<?php

// ========= 01
/***
 
10 進数で表された整数 N が与えられます。
整数 N の各桁の和を計算し、出力してください。

入力例1
12345

出力例1
15

 */

$num_01 = trim(fgets(STDIN));
$arr_01 = str_split($num_01);

// 値を加算して、追加していく
$arr_sum = 0;
foreach ($arr_01 as $arr_01_val) {
    $arr_sum += $arr_01_val;
}

// 出力
print($arr_sum);

// ========= 02
/***
 
N 個の整数 M_1, M_2, ..., M_N があります。
i 番目の M を M_i とするとき、M_i * i を改行区切りで出力してください。
例えば、M_5 が 3 の場合、3 * 5 = 15 となります。

入力例1
5
1 2 3 4 5

出力例1
1
4
9
16
25

 */

$num_02 = trim(fgets(STDIN));
$num_arr_02 = trim(fgets(STDIN));
$arr_02 = explode(" ", $num_arr_02);

// 1, 2, 3, 4, 5 をかけて出力
$idx = 1;
foreach ($arr_02 as $arr_02_val) {
    print($arr_02_val * $idx . "\n");
    $idx = $idx + 1;
}

// ========= 03

/***
 
整数 N が与えられます。
N が何回 2 で割れるかを求め、出力してください。

入力例2
16

出力例2
4
---------------
入力例3
10

出力例3
1

 */


$num_03 = trim(fgets(STDIN));
$ans_03 = 0;

// === ループ開始
while (true) {
    // ２で割り切れなかったら、ループを抜ける
    if ($num_03 % 2 == 1) {
        print($ans_03);
        return false;
    } else {
        $num_03 = $num_03 / 2;
    }
    $ans_03 += 1;
}
