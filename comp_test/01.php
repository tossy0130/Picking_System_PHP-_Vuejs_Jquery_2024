<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>コンポーネントテスト</title>

    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            overflow: auto;
            border: 1px solid #ddd;
            z-index: 1;
        }

        .dropdown-content button {
            color: black;
            padding: 10px 12px;
            text-decoration: none;
            display: block;
            cursor: pointer;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }
    </style>

</head>

<body>

    <div class="dropdown" data-menuid="1">
        <button class="dropbtn" value="トナミ運輸">トナミ運輸</button>
        <div class="dropdown-content">
            <button type="button" data-company="5" data-value="シールあり">シールあり</button>
            <button type="button" data-company="0" data-value="ケース">ケース</button>
            <button type="button" data-company="トナミ運輸" data-value="取扱い注意">取扱い注意</button>
            <button type="button" data-company="トナミ運輸" data-value="千趣会">千趣会</button>
        </div>
    </div>

    <div class="dropdown" data-menuid="2">
        <button class="dropbtn" value="佐川">佐川</button>
        <div class="dropdown-content">
            <button type="button" data-company="5" data-value="シールあり">シールあり</button>
            <button type="button" data-company="0" data-value="ケース">ケース</button>
            <button type="button" data-company="トナミ運輸" data-value="取扱い注意">取扱い注意</button>
            <button type="button" data-company="トナミ運輸" data-value="千趣会">千趣会</button>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        (function($) {
            $(document).ready(function() {
                $('.dropdown').on('click', function() {
                    var menuId = $(this).data('menuid');
                    var dropdownContent = $('.dropdown-content[data-menuid=' + menuId + ']');

                    console.log("メニューID:" + menuId);
                    console.log("ドロップダウンコンテンツ:" + dropdownContent);
                    dropdownContent.toggleClass('show');
                    $(this).find('.dropdown-content').toggleClass('show');
                });


                $('.dropdown-content button').on('click', function() {
                    var selectedCompany = $(this).data('company');
                    var selectedValue = $(this).data('value');
                    // ここで選択した値を使用して次のページにリダイレクトなどの処理を実行
                });

            });
        })(jQuery);
    </script>


</body>

</html>