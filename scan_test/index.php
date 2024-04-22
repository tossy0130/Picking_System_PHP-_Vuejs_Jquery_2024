<?php

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スキャナーテスト01</title>
</head>

<body>

    <div class="container">
        <div class="content">

            <p id="varcode_val">4971715240226</p>

            <div id="app">
                <input type="text" id="scan_val" name="scan_val" v-model="scan_val" @input="compareValues">
                <p>{{ result }}</p>
            </div>

        </div> <!-- container END -->

        <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script>
            new Vue({
                el: "#app",
                data: {
                    scan_val: '',
                    result: ''
                },
                computed: {
                    halfWidthScanVal: function() {
                        return this.scan_val;
                    }
                },
                methods: {
                    convertToHalfWidth: function() {
                        // 半角変換の処理を行う
                        this.scan_val = this.scan_val.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                            return String.fromCharCode(s.charCodeAt(0) - 65248);
                        });
                    },
                    compareValues: function() {

                        // 半角変換されているかの確認
                        this.convertToHalfWidth();

                        // 入力値チェック
                        if (this.scan_val === document.getElementById("varcode_val").textContent) {
                            this.result = "JAN 一致 OK";
                        } else {
                            this.result = "JAN 不一致 NG:" + this.scan_val;
                            this.scan_val = '';
                            document.getElementById("scan_val").focus();

                        }

                    }
                },
                mounted: function() {
                    document.getElementById("scan_val").focus();
                }

            })
        </script>

</body>

</html>