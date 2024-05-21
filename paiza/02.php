<?php

// === 01
$num_01 = trim(fgets(STDIN));

if ($num_01 == "paiza") {
    print("YES");
} else {
    print("NO");
}

// === 02
/***
 
整数Nが与えられます。Nが 100 以下の場合はYESを、
そうではない場合はNOを出力してください。

 */

$num_02 = trim(fgets(STDIN));

if ($num_02 <= 100) {
    print("YES");
} else {
    print("NO");
}

// === 03
/***
 
N 個の整数 a_1, a_2, ..., a_N が与えられます。
この N 個の整数のうち、a_1 から順に奇数か偶数か判定し、奇数の場合のみ改行区切りで出力してください。
また、N 個の整数には奇数が少なくとも 1 つ含まれています。

 */
$num_03 = fgets(STDIN);
$num_03_2 = fgets(STDIN);

$arr_num_03 = explode(" ", $num_03_2);

for ($i = 0; $i < $num_03; $i++) {

    if ($arr_num_03[$i] % 2 != 0) {
        print($arr_num_03[$i] . "\n");
    }
}

// === 04
/***
 
入力例1
6
1 2 3 4 5 6

出力例1
3
6

 */

$num_04 = fgets(STDIN);
$arr_num_04 = fgets(STDIN);

$arr_04 = explode(" ", $arr_num_04);

for ($i = 0; $i < $num_04; $i++) {
    if ($arr_04[$i] % 3 == 0) {
        print($arr_04[$i] . "\n");
    }
}

// === 05
/***
 
入力例1
5
1 2 3 4 5

出力例1
odd
even
odd
even
odd

 */

$num_05 = fgets(STDIN);
$arr_num_05 = fgets(STDIN);

$arr_05 = explode(" ", $arr_num_05);

for ($i = 0; $i < $num_05; $i++) {
    if ($arr_05[$i] % 2 == 0) {
        print("even" . "\n");
    } else {
        print("odd" . "\n");
    }
}
