<?php
require_once('connect.php');
$textJSON = json_decode(file_get_contents('text.json'), true);
$bot_token = '5792512364:AAEVSBwEn6u_U03xmQLrYpDlMuMbOdp00HA';
$url = 'https://api.telegram.org/bot' . $bot_token;
$r = file_get_contents('php://input');
$r = json_decode($r, true);
file_put_contents('log.txt', print_r($r, true), FILE_APPEND | LOCK_EX);
//send_message($r['message']['chat']['id'], 'fsdfsfd');
//$text = $r['message']['chat']['text'];
if(isset($r['message']['chat']['id'])){
    $chat_id = $r['message']['chat']['id'];
}

if(isset($r['message']['entities'][0]['type'])){
    if($r['message']['entities'][0]['type'] = 'bot_command'){
        if($r['message']['text'] == '/start'){
            $user_info = get_user_info($chat_id, $mysqli);
            if($user_info['chat_id']==''){
            send_message($chat_id, 'Введите свой иин', true);
            }else{
                send_buttons($chat_id, $textJSON['main_menu'][$user_info['language']]['message'], $textJSON['main_menu'][$user_info['language']]['buttons']);
            }
        }elseif($r['message']['text'] == '/photo'){
            send_photo($chat_id);
        }elseif($r['message']['text'] == '/document'){
            send_document($chat_id);
        }elseif($r['message']['text'] == '/language'){
            send_buttons($chat_id, 'Выберите язык' , [[['text' => 'kz','callback_data' => 'kz'],['text' => 'ru','callback_data' => 'ru'],['text' => 'en','callback_data' => 'en']]]);
            //send_message($chat_id, 'Выбранный язык', true);
        }elseif($r['message']['text'] == '/menu'){
            $user_info = get_user_info($chat_id, $mysqli);
            send_buttons($chat_id, $textJSON['main_menu'][$user_info['language']]['message'], $textJSON['main_menu'][$user_info['language']]['buttons']);
        }
    }
    //send_message($chat_id, 'gigig');
};


//send_buttons($chat_id);


if(isset($r['message']['text'])){
    $raw_message = $r['message']['text'];
    $chat_id = $r['message']['chat']['id'];
    if($raw_message == 'kz' || $raw_message == 'ru' || $raw_message == 'en'){
        if(!set_language($chat_id, $raw_message, $mysqli)){
            send_message($chat_id, "неудалось установить язык '{$raw_message}'");
        }else{
            send_message($chat_id, "Язык установлен");
            send_buttons($chat_id, $textJSON['main_menu'][$raw_message]['message'], $textJSON['main_menu'][$raw_message]['buttons']);
        };
        //answerCallBackquery($r['callback_query']['id'], '');
        return;
    }

    //send_message($chat_id, get_message_info($raw_message, $textJSON));
    $message_info = get_message_info($raw_message, $textJSON);
    $split = strpos($message_info['callback'], '@');
    $text_of_message = substr($message_info['callback'], 0, $split);
    $lang = substr($message_info['callback'], $split+1, 2);
    $answer = $textJSON[$text_of_message];
    if(isset($answer[$lang]['buttons'])){
        send_buttons($chat_id, $answer[$lang]['message'], $answer[$lang]['buttons']);
    }else{
        send_message($chat_id, $answer[$lang]['message']);
    }


    
};

if(isset($r['message']['reply_to_message'])){
    $reply = $r['message']['reply_to_message'];
    if($reply['username'] = 'business_irtis_test_bot'){
        if($reply['text'] = 'Введите свой иин'){
            $iin = $r['message']['text'];
            if (check_iin($iin)){
                if(add_user_to_db($iin, $chat_id, $mysqli)){
                send_message($chat_id, 'ИИН добавлен');
                send_buttons($chat_id, 'Выберите язык' , [[['text' => 'kz','callback_data' => 'kz'],['text' => 'ru','callback_data' => 'ru'],['text' => 'en','callback_data' => 'en']]]);
                }else{
                    send_message($chat_id, 'Такой ИИН существует, либо к этому аккаунту привязан другой ИИН');
                    send_message($chat_id, 'Введите свой иин', true);

                }
            }else{
            send_message($chat_id, 'ИИН введен неправильно');
            send_message($chat_id, 'Введите свой иин', true);
            }
        }
    }
}



function send_message($chat_id, $text, $forcereply=''){
    global $url;
    if ($forcereply == true){
        $forcereply = json_encode(['force_reply'=>$forcereply]);
    }
    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => $forcereply,
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
};

function send_buttons($chat_id, $text, $buttons, $forcereply = False){
    global $url;
    $buttonData = array();
    $buttonData = [
        'keyboard' => $buttons
        , 'one_time_keyboard' => true,
        'force_reply' => $forcereply
    ];



    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => json_encode($buttonData),
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
    
};

function send_inline_buttons($chat_id, $text, $buttons, $forcereply = False){
    global $url;
    $buttonData = array();
    $buttonData = [
        'inline_keyboard' => $buttons
    ];



    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => json_encode($buttonData),
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
    
};

function send_photo($chat_id){
    global $url;
    $photo = new CURLFile(realpath("team3.png"));



    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/sendPhoto',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => 'dsada',
            'photo' => $photo,
            'reply_markup' => '',
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
    
};


function send_document($chat_id){
    global $url;
    $document = new CURLFile(realpath("team3.png"));



    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/sendDocument',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => 'dsada',
            'document' => $document,
            'reply_markup' => '',
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
    
};

function answerCallBackquery($chat_id, $text){
    global $url;
    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => $url . '/answerCallbackQuery',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'callback_query_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'show_alert' => true,
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
}

function check_iin($iin){
    $iin_split = str_split($iin);
    if(count($iin_split) == 12){
        if(ctype_digit($iin)){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function add_user_to_db($iin, $chat_id, $mysqli){
    if(!check_user_exist($iin, $chat_id, $mysqli) && !check_chat_id_exist($chat_id, $mysqli)){
        $query = "INSERT INTO users (iin, chat_id) VALUES('{$iin}', '{$chat_id}');";
        $result = mysqli_query($mysqli, $query);
        return $result;
    }else{
        return false;
    }
    
}

function check_user_exist($iin, $chat_id, $mysqli){
    $query = "SELECT * FROM users WHERE iin = '{$iin}';";
    $result = mysqli_query($mysqli, $query);
    if(mysqli_fetch_assoc($result) == null){
        return false;
    }else{
        return true;
    }
    
}

function check_chat_id_exist($chat_id, $mysqli){
    $query = "SELECT * FROM users WHERE chat_id = '{$chat_id}';";
    $result = mysqli_query($mysqli, $query);
    if(mysqli_fetch_assoc($result) == null){
        return false;
    }else{
        return true;
    }   
}

function set_language($chat_id, $language, $mysqli){
    $query = "UPDATE users SET language = '{$language}' WHERE chat_id = '{$chat_id}';";
    $result = mysqli_query($mysqli, $query);
    return $result;
}

function get_user_info($chat_id, $mysqli){
    $query = "SELECT * FROM users WHERE chat_id = '{$chat_id}';";
    $result = mysqli_query($mysqli, $query);
    $result = $result -> fetch_array(MYSQLI_ASSOC);
    return $result;
}

function get_message_info($text, $textJSON){
    foreach($textJSON as $type => $lang){
        foreach($lang as $language => $message){
            foreach($message['buttons'] as $row){
                foreach($row as $column){
                    if($column['text'] == $text){
                        file_put_contents('logkk.txt', print_r($column['callback_data'], true), FILE_APPEND | LOCK_EX);
                        return ['language'=>$language, 'callback'=>$column['callback_data']];
                    }
                }
                
            }
            
        }
    }
}

?>