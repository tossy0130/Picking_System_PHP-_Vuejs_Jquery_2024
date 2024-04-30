------------------------------------------------------
--------------　表示デフォルト SQL
------------------------------------------------------
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
    SUM(SK.出荷数量) AS 数量
FROM
    SJTR SJ,
    SKTR SK,
    SOMF SO,
    SLTR SL,
    SMMF SM,
    USMF US,
    SHMF SH
WHERE
    SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
    AND SK.倉庫Ｃ = SO.倉庫Ｃ
    AND SL.出荷元 = SM.出荷元Ｃ(+)
    AND SK.運送Ｃ = US.運送Ｃ
    AND SL.商品Ｃ = SH.商品Ｃ
    AND SJ.出荷日 = :SELECT_DATE
    AND SK.倉庫Ｃ = :SELECT_SOUKO
    AND SK.運送Ｃ = :SELECT_UNSOU
GROUP BY
    SK.出荷日,
    SK.倉庫Ｃ,
    SO.倉庫名,
    SK.運送Ｃ,
    US.運送略称,
    SL.出荷元,
    SM.出荷元名,
    SK.商品Ｃ,
    SH.品名
ORDER BY
    SK.倉庫Ｃ,
    SK.運送Ｃ,
    SM.出荷元名,
    SK.商品Ｃ,
    SL.出荷元;

------------------------------------------------------
------------- picking　判定あり
------------------------------------------------------
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
    PK.処理Ｆ
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
    AND SJ.出荷日 = '2022/04/04'
    AND SK.倉庫Ｃ = 'L'
    AND SK.運送Ｃ = 2
    AND DECODE(NULL, PK.処理Ｆ, 0) <> 9 -- 完了は除く
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
    SH.梱包入数
ORDER BY
    SK.倉庫Ｃ,
    SK.運送Ｃ,
    SM.出荷元名,
    SK.商品Ｃ,
    SL.出荷元;

------------------------------------------------------
--------------------------------------- 得意先名　追加
------------------------------------------------------
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
    SUM(SK.出荷数量) AS 数量,
    SJ.得意先名
FROM
    SJTR SJ,
    SKTR SK,
    SOMF SO,
    SLTR SL,
    SMMF SM,
    USMF US,
    SHMF SH
WHERE
    SJ.伝票ＳＥＱ = SK.出荷ＳＥＱ
    AND SK.伝票ＳＥＱ = SL.伝票ＳＥＱ
    AND SK.倉庫Ｃ = SO.倉庫Ｃ
    AND SL.出荷元 = SM.出荷元Ｃ(+)
    AND SK.運送Ｃ = US.運送Ｃ
    AND SL.商品Ｃ = SH.商品Ｃ
    AND SJ.出荷日 = '2021/05/05'
    AND SK.倉庫Ｃ = 'L'
    AND SK.運送Ｃ = 56
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
    SJ.得意先名
ORDER BY
    数量 DESC,
    SK.倉庫Ｃ,
    SK.運送Ｃ,
    SM.出荷元名,
    SK.商品Ｃ,
    SL.出荷元;

--------------------------------------------------

----------------------------- インサート取得
SELECT
    SL.伝票番号,
    SL.伝票行番号,
    SL.伝票行枝番号,
    SL.商品Ｃ,
    SL.倉庫Ｃ,
    SL.運送Ｃ,
    SL.出荷元,
    SL.数量
FROM
    SLTR SL;

SELECT
    SL.伝票番号,
    SL.伝票行番号,
    SL.伝票行枝番,
    SL.商品Ｃ,
    SL.倉庫Ｃ,
    SL.出荷元,
    SL.数量,
    SL.登録日
FROM
    SLTR SL
WHERE
    SL.商品Ｃ = 2316600
    AND SL.倉庫Ｃ = 'L'
ORDER BY
    登録日 DESC;