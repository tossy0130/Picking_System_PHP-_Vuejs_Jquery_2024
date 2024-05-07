-----------------------------
---------- 件数を数える
-----------------------------
SELECT
    COUNT(*) AS NUM_DUPLICATES
FROM
    HTPK
WHERE
    伝票番号 = 20243
    AND 伝票行番号 = 4;

---------------------
------- 最新の １件取得
---------------------
SELECT
    SL.伝票番号,
    SL.伝票行番号,
    SL.伝票行枝番,
    SL.商品Ｃ,
    SL.倉庫Ｃ,
    SL.出荷元,
    SL.数量
FROM
    SLTR SL
WHERE
    SL.商品Ｃ = 2315700
    AND SL.倉庫Ｃ = 'L'
ORDER BY
    SL.伝票ＳＥＱ DESC FETCH FIRST 1 ROW ONLY;