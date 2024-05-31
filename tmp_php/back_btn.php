<?php

?>

<script>
    //====================== start ============================
    // ******** [戻る ボタン] **********


    $('#five_back_btn').on('click', function(event) {

        event.preventDefault();

        var suuryou_num = $('#suuryou_num').text();
        var count_num = $('#count_num').val();

        if (parseInt(count_num) === 0) {

            showModal_back();
        } else {
            // === 運送便 単数 , 備考・特記
            if ($("#back_sql_one_tokki").val() !== "") {

                var back_url = "./four.php?" +
                    "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                    "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                    "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                    "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                    "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                    "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                    "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                    "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                    "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                    "&five_back=111" +
                    "&back_one_condition=" + encodeURIComponent(<?php echo json_encode($one_condition); ?>);

                window.location.href = back_url;

            }

            // === 運送便（複数）, 特記・備考 あり（複数）
            if ($('#now_sql_multiple_kakutei').val() !== "") {

                var back_url = "./four.php?" +
                    "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                    "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                    "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                    "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                    "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                    "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                    "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                    "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                    "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                    "&five_back=111" +
                    "&back_now_sql_multiple=" + encodeURIComponent(<?php echo json_encode($sql_multiple_condition); ?>);

                window.location.href = back_url;

            }

            var one_now_sql_kakutei = $("#one_now_sql_kakutei").val();
            var now_sql_multiple_kakutei = $("#now_sql_multiple_kakutei").val();

            if ((one_now_sql_kakutei === undefined || one_now_sql_kakutei === "") &&
                (now_sql_multiple_kakutei === undefined || now_sql_multiple_kakutei === "")) {

                var back_url = "./four.php?" +
                    "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                    "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                    "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                    "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                    "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                    "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                    "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                    "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                    "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                    "&five_back=333";

                window.location.href = back_url;
            }

        }

    });

    // モーダル　「戻る」
    $('#send_back').on('click', function() {

        // === 運送便 単数 , 備考・特記
        if ($("#back_sql_one_tokki").val() !== "") {

            var back_url = "./four.php?" +
                "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                "&five_back=111" +
                "&back_one_condition=" + encodeURIComponent(<?php echo json_encode($one_condition); ?>);

            window.location.href = back_url;

        } else {

            console.log("ここ 「戻る」");

        }

        // === 運送便（複数）, 特記・備考 あり（複数）
        if ($('#now_sql_multiple_kakutei').val() !== "") {

            var back_url = "./four.php?" +
                "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                "&five_back=111" +
                "&back_now_sql_multiple=" + encodeURIComponent(<?php echo json_encode($sql_multiple_condition); ?>);

            window.location.href = back_url;

        }

        var one_now_sql_kakutei = $("#one_now_sql_kakutei").val();
        var now_sql_multiple_kakutei = $("#now_sql_multiple_kakutei").val();

        if ((one_now_sql_kakutei === undefined || one_now_sql_kakutei === "") &&
            (now_sql_multiple_kakutei === undefined || now_sql_multiple_kakutei === "")) {

            var back_url = "./four.php?" +
                "unsou_code=" + encodeURIComponent(<?php echo json_encode($unsou_code); ?>) +
                "&unsou_name=" + encodeURIComponent(<?php echo json_encode($unsou_name); ?>) +
                "&day=" + encodeURIComponent(<?php echo json_encode($select_day); ?>) +
                "&souko=" + encodeURIComponent(<?php echo json_encode($souko_code); ?>) +
                "&get_souko_name=" + encodeURIComponent(<?php echo json_encode($souko_name); ?>) +
                "&shouhin_code=" + encodeURIComponent(<?php echo json_encode($Shouhin_code); ?>) +
                "&shouhin_name=" + encodeURIComponent(<?php echo json_encode($Shouhin_name); ?>) +
                "&denpyou_num=<?php echo $IN_Dennpyou_num; ?>" +
                "&denpyou_Gyou_num=<?php echo $IN_Dennpyou_Gyou_num; ?>" +
                "&five_back=333";

            window.location.href = back_url;
        }

    });

    $('#cancel_back').on('click', function() {
        hideModal_back();
    });

    $('.close_02').on('click', function() {
        hideModal_back();
    });


    // ******** [戻る ボタン] **********
    //====================== END ============================
</script>