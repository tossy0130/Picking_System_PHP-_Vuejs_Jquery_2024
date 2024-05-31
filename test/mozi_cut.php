<?php
$texts = [
    "これは非常に長いテキストです。省略表示のための例として使います。",
    "短いテキスト",
    "こちらも非常に長いテキストです。省略表示の例として使います。"
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>省略表示と全文表示</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .ellipsis {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div id="textContainer">
        <?php foreach ($texts as $index => $text) : ?>
            <div class="text-item" data-fulltext="<?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(mb_strimwidth($text, 0, 25, "...", "UTF-8"), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        $(document).ready(function() {
            $('.text-item').click(function() {
                var $this = $(this);
                var fullText = $this.data('fulltext');
                $this.text(fullText);
            });
        });
    </script>
</body>

</html>