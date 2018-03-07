<?php
header("Content-Type: text/html;charset=utf-8");

if($_POST)
{

    $to_Email = "info@domain.com"; // Куда слать письма

    $config['smtp_username'] = 'no-reply@domain.com';  //Смените на адрес своего почтового ящика.
    $config['smtp_port'] = '25'; // Порт работы.
    $config['smtp_host'] =  'mail.yandex.ru';  //сервер для отправки почты
    $config['smtp_password'] = 'password';  //Измените пароль
    $config['smtp_debug'] = false;  //Если Вы хотите видеть сообщения ошибок, укажите true вместо false
    $config['smtp_charset'] = 'utf-8';	//кодировка сообщений. (windows-1251 или utf-8, итд)
    $config['smtp_from'] = 'Запрос с сайта'; //Ваше имя - или имя Вашего сайта. Будет показывать при прочтении в поле "От кого"


    //check $_POST vars are set, exit if any missing
    if(!isset($_POST["userName"]) || !isset($_POST["userPhone"]))
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Мы не сможем связаться с Вами, так как Вы не заполнили поля Имя и Телефон!'));
        die($output);
    }

    //Sanitize input data using PHP filter_var().
    $user_Name        = filter_var($_POST["userName"], FILTER_SANITIZE_STRING);
    $user_Phone       = filter_var($_POST["userPhone"], FILTER_SANITIZE_STRING);
    // $user_Text       = filter_var($_POST["userText"], FILTER_SANITIZE_STRING);
    $subject        = filter_var($_POST["userSubject"], FILTER_SANITIZE_STRING);

    $sentMail = smtpmail($config['smtp_from'], 
                $to_Email, 
                $subject, 
                "Имя: ". $user_Name 
                ."<br>Телефон: ". $user_Phone
                ."<br><br>\n(".date("d F Y").' - '.date("H:i:s").')'
            );

    if(!$sentMail)
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Невозможно отправить запрос. Пожалуйста, позвоните нам!'));
        die($output);
    }else{
        $output = json_encode(array(
            'type'=>'message', 
            'text' => '<em>' . $user_Name .'</em>, спасибо за ваше обращение!<br /> Наш специалист свяжется с Вами в ближайшее время.',
            'status' => $sentMail
        ));
        die($output);
    }
}

function smtpmail($to='', $mail_to, $subject, $message, $headers='') {
    global $config;
    $SEND =	"Date: ".date("D, d M Y H:i:s") . " UT\r\n";
    $SEND .= 'Subject: =?'.$config['smtp_charset'].'?B?'.base64_encode($subject)."=?=\r\n";
    if ($headers) $SEND .= $headers."\r\n\r\n";
    else
    {
            $SEND .= "Reply-To: ".$config['smtp_username']."\r\n";
            $SEND .= "To: \"=?".$config['smtp_charset']."?B?".base64_encode($to)."=?=\" <$mail_to>\r\n";
            $SEND .= "MIME-Version: 1.0\r\n";
            $SEND .= "Content-Type: text/html; charset=\"".$config['smtp_charset']."\"\r\n";
            $SEND .= "Content-Transfer-Encoding: 8bit\r\n";
            $SEND .= "From: \"=?".$config['smtp_charset']."?B?".base64_encode($config['smtp_from'])."=?=\" <".$config['smtp_username'].">\r\n";
            $SEND .= "X-Priority: 3\r\n\r\n";
    }
    $SEND .=  $message."\r\n";
     if( !$socket = fsockopen($config['smtp_host'], $config['smtp_port'], $errno, $errstr, 30) ) {
        if ($config['smtp_debug']) echo $errno."<br>".$errstr;
        return false;
     }
 
    if (!server_parse($socket, "220", __LINE__)) return false;
 
    fputs($socket, "HELO " . $config['smtp_host'] . "\r\n");
    if (!server_parse($socket, "250", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не могу отправить HELO!</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, "AUTH LOGIN\r\n");
    if (!server_parse($socket, "334", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не могу найти ответ на запрос авторизаци.</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
    if (!server_parse($socket, "334", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Логин авторизации не был принят сервером!</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
    if (!server_parse($socket, "235", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Пароль не был принят сервером как верный! Ошибка авторизации!</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, "MAIL FROM: <".$config['smtp_username'].">\r\n");
    if (!server_parse($socket, "250", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не могу отправить комманду MAIL FROM: </p>';
        fclose($socket);
        return false;
    }
    fputs($socket, "RCPT TO: <" . $mail_to . ">\r\n");
 
    if (!server_parse($socket, "250", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не могу отправить комманду RCPT TO: </p>';
        fclose($socket);
        return false;
    }
    fputs($socket, "DATA\r\n");
 
    if (!server_parse($socket, "354", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не могу отправить комманду DATA</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, $SEND."\r\n.\r\n");
 
    if (!server_parse($socket, "250", __LINE__)) {
        if ($config['smtp_debug']) echo '<p>Не смог отправить тело письма. Письмо не было отправленно!</p>';
        fclose($socket);
        return false;
    }
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    return TRUE;
}
 
function server_parse($socket, $response, $line = __LINE__) {
    global $config;
    while (@substr($server_response, 3, 1) != ' ') {
        if (!($server_response = fgets($socket, 256))) {
            if ($config['smtp_debug']) echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
             return false;
         }
    }
    if (!(substr($server_response, 0, 3) == $response)) {
        if ($config['smtp_debug']) echo "<p>Проблемы с отправкой почты!</p>$response<br>$line<br>";
        return false;
    }
    return true;
}


?>