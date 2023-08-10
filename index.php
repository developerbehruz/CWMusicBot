<?php
    date_default_timezone_set('Asia/Tashkent');
    
    define('API_KEY', "5451897271:AAFYq5xS3zuonBlUzeC11YmXuOKj0ol6d-I");
    function bot($method, $datas=[]){
        $url = "https://api.telegram.org/bot".API_KEY."/".$method;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);

        $res = curl_exec($ch);

        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        }else{
            return json_decode($res);
        }
    }
    function html($tx){
        return str_replace(['<','>'],['&#60;','&#62;'],$tx);
    }
    include 'db.php';
    $update = json_decode(file_get_contents('php://input'));
    $message = $update->message;
    $chat_id = $message->chat->id;
    $type = $message->chat->type;
    $miid =$message->message_id;
    $name = $message->from->first_name;
    $lname = $message->from->last_name;
    $full_name = $name . " " . $lname;
    $full_name = rStr(html($full_name));
    $user = $message->from->username;
    $fromid = $message->from->id;
    $text = rStr(html($message->text));
    $title = $message->chat->title;
    $chatuser = $message->chat->username;
    $chatuser = $chatuser ? $chatuser : "Shaxsiy Guruh!";
    $caption = rStr($message->caption);
    $entities = $message->entities;
    $entities = $entities[0];
    $text_link = $entities->type;
    $left_chat_member = $message->left_chat_member;
    $new_chat_member = $message->new_chat_member;
    $photo = $message->photo;
    $video = $message->video;
    $audio = $message->audio;
    $reply = $message->reply_markup;
    $fchat_id = $message->forward_from_chat->id;
    $fid = $message->forward_from_message_id;
    //editmessage
    $callback = $update->callback_query;
    $qid = $callback->id;
    $mes = $callback->message;
    $mid = $mes->message_id;
    $cmtx = $mes->text;
    $cid = $callback->message->chat->id;
    $ctype = $callback->message->chat->type;
    $cbid = $callback->from->id;
    $cbuser = $callback->from->username;
    $data = $callback->data;

    $my_channel = "-1001590238869";

    $fallows = [
        [
            'text_btn' => "👌 Bizning kanal", 
            'link' => "https://t.me/cwmusic_channel", 
            'chat_id' => $my_channel,
            'required'=> true
        ],
    ];
    $fallow_time = 24;

    $share_btn = [
        'share_btn' => "Do'stlarni taklif qilish 👭",
        'share_text' => "🤩🥳 Salom, biz do'stlarimiz bilan yangi guruhda, sovg'alar o'yini tashkil etdik, omadingizni sinab ko'rmaysizmi (tekinga) ?!",
        'share_link' => "https://t.me/cwmusic_channel"
    ];

    function get_data($url){
        return json_decode(file_get_contents($url), true);
    };

    function get_fallows($params = []){
        global $fallows, $share_btn;
        $list_channels = [];
        foreach ($fallows as $channel) {
            $list_channels[][] = ['text' => $channel['text_btn'], 'url'=> $channel['link']];
        };
        if($params['test_btn']){
            array_push($list_channels, [
                [
                    'text' => "Obuna bo'ldim ✅",
                    'callback_data' => "followed"
                ]
            ]);
        }else if($params['share_btn']){
            array_push($list_channels, [
                [
                    'text' => $share_btn['share_btn'],
                    'url' => 'https://t.me/share/url?url='.$share_btn['share_link'].'&text='.$share_btn['share_text']
                ]
            ]);
        };
        return $list_channels;
    };

    function user_is_followed($user_id){
        global $chat_id, $fallow_time;
        $file = "datas/allow_".$chat_id."_".$user_id.".temp";
        if(file_exists($file) && filemtime($file) >= time()-($fallow_time * 3600)){
            return true;
        }else{
            global $fallows;
            $count = 0;
            $count_verf = 0;
            $stss = ['creator', 'administrator', 'member'];
            foreach ($fallows as $channel){
                if($channel["required"]){
                    $count++;
                    $res = get_data('https://api.telegram.org/bot'.API_KEY.'/getChatMember?chat_id='.$channel["chat_id"].'&user_id=' . $user_id)['result'];
                    if(in_array($res['status'], $stss)){
                        $count_verf++;
                    };
                };
            };
            return ($count_verf == $count) ? (file_put_contents($file, 1) != false ? true : false) : false;
        }
    };

    if($fromid != $admin){
        if($text == "/start"){
            if (user_is_followed($fromid)) {
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>"Salom 👋 ".$full_name.",\nBotga hush kelibsiz 😊."
                ]);
                $slt = "SELECT * FROM cwMusic_users WHERE fromid = '$fromid'";
                $query = mysqli_query($conn, $slt);
                if (mysqli_num_rows($query)>0) {
                    
                }else{
                    $ins = "INSERT INTO cwMusic_users (fromid,fullname,username) VALUES ('{$fromid}','{$full_name}','{$user}')" or die(mysqli_error($conn));
                    $query = mysqli_query($conn, $ins);
                    bot('sendMessage', [
                        'chat_id' => '-1001590238869',
                        'text' => "Yangi foydalanuvchi!\n\n👤 Ism: $full_name\n🆔 raqam: $fromid\n✳️ Username: @$user"
                    ]);
                }
            }else{
                bot('sendMessage', [
                    'chat_id' => $fromid,
                    'text' => "⚠️ Xatolik, botdan foydalanish uchun bizning kannallarga obuna bo'lishingiz shart!\n\nObuna bo'lib «/start» tugmachasini bosing ✅",
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => get_fallows(['test_btn' => false])
                    ])
                ]);
            }
        }
    }else if ($fromid == $admin) {
        if ($text == "/start") {
            bot('sendMessage',[
                'chat_id'=>$admin,
                'text'=>"👋 What's up admin!"
            ]);
        }
    }

    $commands = ['/start','help'];
    if (!in_array($text, $commands) && $text != $message->document) {
        $query = mysqli_query($conn,"SELECT * FROM cwMusic WHERE artist LIKE '%{$text}%' or title LIKE '%{$text}%' or music LIKE '%$text%'");
        if (mysqli_num_rows($query)>0) {
            $matn = "Natijalar:\n\n";
            $i = 0;
            foreach ($query as $key => $value) {
                $i++;
                $matn .= $i . ".  " . $value["artist"] . " - " . $value["title"] . "\n";
                $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                if ($i == 10) {
                    break;
                }
            }
            $keys = array_chunk($keyy, 5);
            bot('sendMessage',[
                'chat_id'=>$fromid,
                'text'=>$matn,
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>$keys
                ]),
            ]);
        }else{
            $exp = explode(" ", $text);
            $arr = [];
            foreach($exp as $key => $value) {
                $arr[] = $value;
            }
            $imp = implode("_", $arr);
            $api = file_get_contents("https://u1775.xvest4.ru/API/uzhits.uz/index.php?music=$imp");
            $jd = json_decode($api);

            $data = $jd->data;

            foreach ($data as $key => $value) {
                $artist = str_replace("'", "", $value->artist);
                $title = str_replace("'", "", $value->title);
                $track = $artist . " " . $title;
                if(($title != "" || $artist != "") && $value->download_url != ""){
                    $ins = "INSERT INTO cwMusic (title,artist,music,download_url) VALUES ('{$title}','{$artist}','{$track}','{$value->download_url}')" or die(mysqli_error($conn));
                    $query = mysqli_query($conn, $ins);
                }
            }

            $sltQuery = mysqli_query($conn,"SELECT * FROM cwMusic WHERE artist LIKE '%{$text}%' or title LIKE '%{$text}%' or music LIKE '%$text%'");
            if (mysqli_num_rows($sltQuery)>0) {
                $matn = "Natijalar:\n\n";
                $i = 0;
                foreach ($sltQuery as $key => $value) {
                    $i++;
                    $matn .= $i . ".  " . $value["artist"] . " - " . $value["title"] . "\n";
                    $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
                    if ($i == 10) {
                        break;
                    }
                }
                $keys = array_chunk($keyy, 5);
                bot('sendMessage',[
                    'chat_id'=>$fromid,
                    'text'=>$matn,
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$keys
                    ]),
                ]);
            }else{
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://shazam.p.rapidapi.com/search?term=$imp%20the%20rain&locale=en-US&offset=0&limit=8",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "X-RapidAPI-Host: shazam.p.rapidapi.com",
                        "X-RapidAPI-Key: 6ebbd5b41cmsh0cfcc39138e91b5p1e3d2fjsn34b9d6673cc5"
                    ],
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if ($err) {
                echo "cURL Error #:" . $err;
                } else {
                    $jd = json_decode($response);
                    foreach($jd->tracks->hits as $key => $value) {
                        $tracks = $value->track->title . " " . $value->track->subtitle;
                        $ins = "INSERT INTO cwMusic (title,artist,music,download_url) VALUES ('{$value->track->title}','{$value->track->subtitle}','{$tracks}','{$value->track->hub->actions[1]->uri}')" or die(mysqli_error($conn));
                        $query = mysqli_query($conn, $ins);
                        bot('sendAudio', [
                            'chat_id' => $fromid,
                            'audio' => $value->track->hub->actions[1]->uri
                        ]);
                    }
                }
            }
        }
    }
        
        // if ($text == "/random") {
        // 	$query = mysqli_query($conn, "SELECT * FROM book_usersCW WHERE id ORDER BY RAND() LIMIT 10");
        // 	if (mysqli_num_rows($query) > 0) {
        //         $matn = "Tasodiyif yuborilgan kitoblar:\n\n";
        //         $i = 0;
        //         foreach ($query as $key => $value) {
        //             $i++;
        //             $size = ($value["size"] / (1024 * 1024));
        //             $matn .= $i . ".  " . $value["name"] . "\n" . $size  . " MB Yuklab olngnalar soni: " . $value["down"] . " ta\n";
        //             $keyy[] = ['text'=>$i, 'callback_data'=> 'down_' . $value["id"]];
        //             if ($i == 10) {
        //                 break;
        //             }
        //         }
        //         $keys = array_chunk($keyy, 3);
        //         bot('sendMessage',[
        //             'chat_id'=>$fromid,
        //             'text'=>$matn,
        //             'reply_markup'=>json_encode([
        //                 'inline_keyboard'=>$keys
        //             ]),
        //         ]);
        //     }
        // }
    if ($callback) {
        if (mb_stripos($data, 'down_')!==false) {
            $exp = explode("down_", $data);
            $id = $exp[1];
            $query = mysqli_query($conn,"SELECT * FROM cwMusic WHERE id = '{$id}'");
            if (mysqli_num_rows($query)>0) {
                $row = mysqli_fetch_assoc($query);
                bot('sendAudio',[
                    'chat_id'=>$cbid,
                    'audio'=>$row["download_url"],
                ]);
                mysqli_query($conn,"UPDATE cwMusic SET down = down + '1' WHERE id = '{$id}'");
            }else{
                bot('answerCallbackQuery',[
                    'callback_query_id'=>$qid,
                    'text'=>"Avval kitob qidiring!",
                    'show_alert'=>true
                ]);
            }
        }
    }
?>
