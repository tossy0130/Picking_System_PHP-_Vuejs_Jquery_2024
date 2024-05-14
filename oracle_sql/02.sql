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

---------------------
------- 複数選択
---------------------
SELECT
    SK.出荷日,
    SK.倉庫Ｃ,
    SO.倉庫名,
    SK.運送Ｃ,
    US.運送略称,
    SL.出荷元,
    SM.出荷元名,
    SK.特記事項
FROM
    SJTR SJ,
    SKTR SK,
    SOMF SO,
    SLTR SL,
    SMMF SM,
    USMF US
WHERE
    SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
    AND SK.倉庫Ｃ = SO.倉庫Ｃ
    AND SK.運送Ｃ = US.運送Ｃ
    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
    AND SL.出荷元 = SM.出荷元Ｃ(+)
 --                    AND (SK.運送Ｃ = '10' OR SK.運送Ｃ = '59' OR SK.運送Ｃ = '60')
 --    AND (SK.運送Ｃ = '10' OR SK.運送Ｃ = '59' OR SK.運送Ｃ = '60')
    AND (((SK.運送Ｃ = '60')
    AND (SL.出荷元 IS NULL)
    AND (SK.特記事項 = '関東'))
    OR ((SK.運送Ｃ = '60')
    AND (SL.出荷元 IS NULL)
    AND (SK.特記事項 IS NULL))
    OR (SK.運送Ｃ = '59') )
    AND SJ.出荷日 = '2023/01/30'
    AND SK.倉庫Ｃ = 'L'
GROUP BY
    SK.出荷日,
    SK.倉庫Ｃ,
    SO.倉庫名,
    SK.運送Ｃ,
    US.運送略称,
    SL.出荷元,
    SM.出荷元名,
    SK.特記事項
ORDER BY
    SK.倉庫Ｃ,
    SK.運送Ｃ,
    SL.出荷元,
    SM.出荷元名,
    SK.特記事項;

--------------------------------

SELECT
    SK.出荷日,
    SK.倉庫Ｃ,
    SO.倉庫名,
    SK.運送Ｃ,
    US.運送略称,
    SL.出荷元,
    SM.出荷元名,
    SK.商品Ｃ,
    SH.品名,
    RZ.棚番,
    SH.梱包入数,
    SUM(SK.出荷数量)    AS 数量,
    SUM(PK.ピッキング数量) AS ピッキング数量,
    PK.処理Ｆ,
    SJ.得意先名,
    SH.ＪＡＮ,
    SK.特記事項
FROM
    SJTR SJ,
    SKTR SK,
    SOMF SO,
    SLTR SL,
    SMMF SM,
    USMF US,
    SHMF SH,
    RZMF RZ,
    HTPK PK
WHERE
    SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
    AND SL.伝票番号 = PK.伝票番号(+)
    AND SL.伝票行番号 = PK.伝票行番号(+)
    AND SL.伝票行枝番 = PK.伝票行枝番(+)
    AND SK.倉庫Ｃ = SO.倉庫Ｃ
    AND SL.出荷元 = SM.出荷元Ｃ(+)
    AND SK.運送Ｃ = US.運送Ｃ
    AND SL.商品Ｃ = SH.商品Ｃ
    AND SK.倉庫Ｃ = RZ.倉庫Ｃ
    AND SK.商品Ｃ = RZ.商品Ｃ
    AND SJ.出荷日 = :SELECT_DATE
    AND SK.倉庫Ｃ = :SELECT_SOUKO
    AND ((SK.運送Ｃ = '62')
    OR (SK.運送Ｃ = '64')
    OR (SK.運送Ｃ = '78'))
GROUP BY
    SK.出荷日,
    SK.倉庫Ｃ,
    SO.倉庫名,
    SK.運送Ｃ,
    US.運送略称,
    SL.出荷元,
    SM.出荷元名,
    SK.商品Ｃ,
    SH.品名,
    PK.処理Ｆ,
    RZ.棚番,
    SH.梱包入数,
    SJ.得意先名,
    SH.ＪＡＮ,
    SK.特記事項
ORDER BY
    SK.倉庫Ｃ,
    SK.運送Ｃ,
    SM.出荷元名,
    SK.商品Ｃ,
    SL.出荷元,
    SK.特記事項