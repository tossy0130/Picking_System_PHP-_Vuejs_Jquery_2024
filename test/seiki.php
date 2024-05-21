<?php
$test_strings = [
    "AND ((SK.運送Ｃ = '10') AND (SL.出荷元 = '!') AND (SK.特記事項 IS NULL))",
    "AND ((SK.運送Ｃ = '10') AND (SL.出荷元 = '+') AND (SK.特記事項 IS NULL))"
];

$pattern = '/AND\s+\(\([^()]+\)\s*(?:AND\s+\([^()]+\)\s*)*\)/';

foreach ($test_strings as $string) {
    if (preg_match($pattern, $string, $matches)) {
        echo "Match found: " . $matches[0] . "\n";
    } else {
        echo "No match found.\n";
    }
}
