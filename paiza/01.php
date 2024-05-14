<?php

/*

長さ N の数列 a (a_1, a_2, ..., a_N) が与えられます。
この数列の全ての要素を 2 倍し、改行区切りで出力してください。

入力例1
5
1 2 3 4 5

出力例1
2
4
6
8
10

*/

$loop_num_01 = fgets(STDIN);
$arr_num_01 = fgets(STDIN);

// 配列へ分割
$arr_01 = explode(" ", $arr_num_01);

$idx = 0;
// 値を２倍にする
for ($i = 0; $i < count($arr_01); $i++) {
    $arr_01[$i] = $arr_01[$i] * 2;
}

// 結果出力
foreach ($arr_01 as $arr_01_val) {
    print($arr_01_val . "\n");
}



/***
 
長さ N の数列 a (a_1, a_2, ..., a_N) と b (b_1, b_2, ..., b_N) が与えられます。
a の各要素から b の各要素を引いた結果 (a_1 - b_1, a_2 - b_2, ..., a_N - b_N) を、改行区切りで出力してください。

入力例1
5
1 2 3 4 5
5 4 3 2 1

出力例1
-4
-2
0
2
4

 */

$num_02 = fgets(STDIN);
$arr_num_001 = fgets(STDIN);
$arr_num_002 = fgets(STDIN);

$arr_001 = explode(" ", $arr_num_001);
$arr_002 = explode(" ", $arr_num_002);

// 引き算
$arr_02_sum = [];
for ($i = 0; $i < $num_02; $i++) {
    $arr_02_sum[$i] = $arr_001[$i] - $arr_002[$i];
}

// 結果出力
foreach ($arr_02_sum as $arr_02_sum_VAL) {
    print($arr_02_sum_VAL . "\n");
}


/***
 
長さ N の数列 a (a_1, a_2, ..., a_N) が与えられます。
この数列の要素を逆順に、改行区切りで出力してください。
 
入力例1
5
1 2 3 4 5

出力例1
5
4
3
2
1

 */

$num_03 = fgets(STDIN);
$num_03_a = trim(fgets(STDIN));

$arr_03_num = explode(" ", $num_03_a);
//    $arr_ans_03 = [];
for ($i = $num_03 - 1; 0 <= $i; $i--) {
    print($arr_03_num[$i] . "\n");
}


/***

九九の 8 の段を半角スペース区切りで出力してください。


 */

$val_04 = 8;

for ($i = 1; $i <= 9; $i++) {
    if ($i == 9) {
        // 最後の出力
        print($val_04 * $i);
    } else {
        print($val_04 * $i . " ");
    }
}


/***
 


 */

$num_05 = fgets(STDIN);

for ($i = 1; $i <= 9; $i++) {
    if ($i == 9) {
        print($num_05 * $i);
    } else {
        print($num_05 * $i . " ");
    }
}
