<?php

// === エスケープ処理
function h($var)
{
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
}

