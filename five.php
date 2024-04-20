<?php


$test_data = [
    "L-4",
    "1000",
    "ホースリール",
    "PRQC-30",
    "4971715",
    "123456",
    "4",
    "1",
    "1",
    "1",
    "取扱注意",
    "甲信越福山",
    "アヤハ × 1",
    "カインズ × 2",
    "ムサシ × 1"
];

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/forth.css">
    <link rel="stylesheet" href="./css/third.css">
    <link rel="stylesheet" href="./css/five.css">
    <link rel="stylesheet" href="./css/common.css">

    <link href="https://use.fontawesome.com/releases/v6.5.2/css/all.css" rel="stylesheet">

    <title>ピッキング 05（詳細）</title>
</head>

<body>



    <div class="head_box">
        <div class="head_content">
            <span class="home_icon_span">
                <a href="#"><i class="fa-solid fa-house"></i></a>
            </span>

            <span class="App_name">
                APP ピッキングアプリ
            </span>
        </div>
    </div> <!-- ===============  head_box END =============== -->


    <div class="head_box_02">
        <div class="head_content_02">
            <span class="home_sub_icon_span">
                <i class="fa-solid fa-thumbtack"></i>
            </span>

            <span class="page_title">
                ピッキング詳細画面
            </span>
        </div>
    </div> <!-- ===============  head_box_02 END =============== -->


    <div class="container_detail">
        <div class="content_detail">

            <div class="detail_item_01_box">
                <p><span class="detail_midashi">ロケ：</span><?php print $test_data[0]; ?></p>
                <p><span class="detail_midashi">在庫数：</span><?php print $test_data[1]; ?></p>
            </div>

            <p class="detail_item_02">
                <span class="detail_midashi">品名：</span><?php print $test_data[2]; ?>
            </p>

            <p class="detail_item_03">
                <span class="detail_midashi">品番：</span><?php print $test_data[3]; ?>
            </p>

            <p class="detail_item_04">
                <span class="detail_midashi">JAN：</span><?php print $test_data[4]; ?> <?php print $test_data[5]; ?>
            </p>

            <div class="detail_item_05_box">
                <p><span class="detail_midashi">数量：</span><?php print $test_data[6]; ?></p>
                <p><span class="detail_midashi">ケース：</span><?php print $test_data[7]; ?></p>
                <p><span class="detail_midashi">バラ：</span><?php print $test_data[8]; ?></p>
            </div>

            <p class="detail_item_06_count">
                <span class="detail_midashi">カウント：</span><?php print $test_data[9]; ?>
            </p>

            <p class="detail_item_07">
                <span class="detail_midashi">備考：</span>
            </p>

            <p class="detail_item_08">
                <span class="detail_midashi">特記：</span><?php print $test_data[10]; ?>
            </p>

            <p class="detail_item_09">
                <span class="detail_midashi">
                    運送便：
                </span><?php print $test_data[11]; ?><br />

            </p>

            <p class="detail_item_10">
                <span class="detail_midashi">得意先：</span>
                <?php print $test_data[12]; ?><br />
                <span class="di_tmp"><?php print $test_data[13]; ?></span><br />
                <span class="di_tmp"><?php print $test_data[14]; ?></span>
            </p>

        </div>
    </div> <!-- ===============  container_detail END =============== -->

    <!-- フッターメニュー -->
    <footer class="footer-menu_02">
        <ul>
            <li><a href="#">戻る</a></li>
            <li><a href="#">確定</a></li>
            <li><a href="#">全数完了</a></li>
        </ul>
    </footer>

</body>

</html>