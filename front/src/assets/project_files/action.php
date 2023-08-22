<?php

function generateCode($type = 'pass', $length = 0)
{
    if (!$length) {
        $length = $this->modx->getOption('password_min_length');
    }

    $password = "";
    /* Массив со всеми возможными символами в пароле */
    switch ($type) {
        case 'pass':
            $arr = array(
                'a', 'b', 'c', 'd', 'e', 'f',
                'g', 'h', 'i', 'j', 'k', 'l',
                'm', 'n', 'o', 'p', 'q', 'r',
                's', 't', 'u', 'v', 'w', 'x',
                'y', 'z', 'A', 'B', 'C', 'D',
                'E', 'F', 'G', 'H', 'I', 'J',
                'K', 'L', 'M', 'N', 'O', 'P',
                'Q', 'R', 'S', 'T', 'U', 'V',
                'W', 'X', 'Y', 'Z', '1', '2',
                '3', '4', '5', '6', '7', '8',
                '9', '0', '#', '!', "?", "&"
            );
            break;
        case 'hash':
            $arr = array(
                'a', 'b', 'c', 'd', 'e', 'f',
                'g', 'h', 'i', 'j', 'k', 'l',
                'm', 'n', 'o', 'p', 'q', 'r',
                's', 't', 'u', 'v', 'w', 'x',
                'y', 'z', 'A', 'B', 'C', 'D',
                'E', 'F', 'G', 'H', 'I', 'J',
                'K', 'L', 'M', 'N', 'O', 'P',
                'Q', 'R', 'S', 'T', 'U', 'V',
                'W', 'X', 'Y', 'Z', '1', '2',
                '3', '4', '5', '6', '7', '8',
                '9', '0'
            );
            break;
        case 'code':
            $arr = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
            break;
    }

    for ($i = 0; $i < $length; $i++) {
        $password .= $arr[mt_rand(0, count($arr) - 1)]; // Берём случайный элемент из массива
    }

    return $password;
}
$res = ['success' => '', 'msg' => '', 'data' => []];
$action = $_POST['action'];
switch ($action) {
    case 'sendcode':
        $res['success'] = 1;
        $res['data']['code'] = generateCode('code', 4);
        $res['data']['target'] = '.js-auth-phone';
        break;
}

if (!empty($res)) {
    die(json_encode($res));
}