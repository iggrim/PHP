<?php
$handle = fopen("../bin/init.txt", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        //echo $buffer;
		preg_match('/(")([^"]+)/', $buffer, $res);
		// ("|')([^"']+)
		//var_dump($res[2]);
		$init_arr[] = $res[2];
			
    }
    if (!feof($handle)) {
        echo "Ошибка: fgets() потерпел неудачу\n";
    }
    fclose($handle);
	//var_dump($init_arr);
}

$server_name = $init_arr[0]; 

$subject = $init_arr[1];

$message = $init_arr[2]; 

$from = $init_arr[3];

$file = $init_arr[4]; 

$mailTo = $init_arr[5];

$smtp_username = $init_arr[6]; 

$smtp_password = $init_arr[7]; 

$smtp_host = 'ssl://smtp.' . $server_name ; 

$smtp_port = 465;
             
$separator = "----------A4D921C2D10D7DB"; //@@ 10 дефисов // разделитель в письме //@@ Разделитель может быть любым, главное правило, чтобы разделитель(метка) начинался с "--" и чтобы такая последовательность символов =====не встречалась в тексте письма=====. 

// заголовок письма 
//$headers = "MIME-Version: 1.0\r\n"; 
// кодировка письма 
//$headers .= "Content-type: text/html; " . "charset=utf-8\r\n";
                   
$headers = "Date: " . date("D, d M Y H:i:s") . " UT\r\n"; 
$headers .= "From: $from \r\n";
$headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "=?=\r\n"; 
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$separator\"\r\n"; //@@ 12 дефисов // в заголовке указываем разделитель //@@ Этот тип используется, если один или более различных наборов данных заключены в одном письме. Каждая часть тела должна иметь синтакс письма RFC 822 (то есть, иметь заголовок, пустую строку и тело), но должна иметь открывающую и закрывающую границы. 

/*Windows предлагает флаг режима текстовой трансляции ('t'), который автоматически переведёт \n в \r\n во время работы с файлом. И наоборот - вы также можете использовать 'b', чтобы принудительно включить бинарный режим, в котором ваши данные не будут преобразовываться. Чтобы использовать эти режимы, укажите 'b' или 't' последней буквой параметра mode*/
$fileRead = fopen($file, "rb"); // открываем файл
        $contentFile = fread($fileRead, filesize($file)); // считываем его до конца

//chunk_split -- Функция используется для разбиения строки на фрагменты, например, для приведения результата функции base64_encode() в соответствие с требованиями RFC 2045. Она вставляет строку end (по умолчанию "\r\n") после каждых chunklen символов (по умолчанию 76). Возвращает преобразованную строку без изменения исходной. 
$code_file1 = chunk_split(base64_encode($contentFile));
fclose($fileRead); // закрываем файл

$bodyMail = "--" . $separator . "\r\n"; //@@ 12 дефисов
$bodyMail .= "Content-Type: text/html; charset=\"utf-8\"\r\n"; // кодировка письма
$bodyMail .= "Content-Transfer-Encoding: 8bit\n\n"; // задаем конвертацию письма //@@ полсе заголовков перед телом письма обязательно ====вставить пустую строку

$bodyMail .= $message."\n\n"; // добавляем текст письма  и пустую строку

$bodyMail .= "--" . "$separator\r\n";
       
//$bodyMail .= "Content-Type: application/octet-stream; name=\"$file\"\r\n"; 
$bodyMail .= "Content-Type: application/octet-stream; \r\n"; 
//$bodyMail .="Content-Type: application/pdf";//pdf не читается

//@@ Основной подтип 'Application/Octet-Stream'Используется для обозначения того, что тело содержит бинарные данные. Набор возможных параметров включает следующие (но не ограничивается ими):TYPE -- обобщенный тип или категория двоичных данных, эта информация больше предназначена для получателя, чем для автоматической обработки.PADDING -- число заполняющих битов, добавленных к битовому потоку, содержащему данные, для формирования байтно-ориентированных данных. Полезно при заключении в тело битового потока, когда общее число битов не кратно восьми, то есть, размеру байта. 

$bodyMail .= "Content-Transfer-Encoding: base64\r\n"; // кодировка файла
//$bodyMail .= "Content-Disposition: attachment; filename==?utf-8?B?".base64_encode(basename($file))."?=\n\n";

$fname = basename($file);

$bodyMail .= "Content-Disposition: attachment; filename=\"$fname\"\n\n";// имя файла  на для получателя 
		
$bodyMail .= $code_file1 ."\r\n";
//$bodyMail .= $separator."--"."\r\n";
$bodyMail .= "--" . $separator."\r\n";

print_r($headers."\r\n".$bodyMail."\r\n.\r\n");

// соединение с почтовым сервером через сокет 
if(!$socket = @fsockopen( 
							$smtp_host, 
                            $smtp_port, 
                            $errorNumber, 
                            $errorDescription, 
                            30) 
){ 
   // если произошла ошибка 
   die($errorNumber.".".$errorDescription); 
} 

// проверяем ответ сервера, если код 220 значит все прошло успешно  
if (!parseSocketAnswer($socket, "220")){ 
   die('Ошибка соединения'); 
} else{
	echo "All is good! <br />";
}

// представляемся почтовому серверу, передаем ему адрес своего хоста 
// $server_name = $_SERVER["SERVER_NAME"]; //имя локального сервера на котором запускается скрипт
fputs($socket, "HELO $server_name\r\n"); //@@ если скрипт запускается из консоли
// проверяем ответ сервера, если код 250 значит все прошло успешно  
if (!parseSocketAnswer($socket, "250")){ 
   fclose($socket); 
   die('Ошибка при приветствии'); 
} else{
	echo "HELO! <br />";
}

// начинаем авторизацию на почтовом сервере 
fputs($socket, "AUTH LOGIN\r\n"); 
// проверяем ответ сервера, если код 334 значит все прошло успешно  
if (!parseSocketAnswer($socket, "334")){ 
   fclose($socket); 
   die('Ошибка авторизации'); 
} else{
	echo "AUTH LOGIN <br />";
}

// отправляем почтовому серверу логин, через который будем авторизовываться 
fputs($socket, base64_encode($smtp_username)."\r\n"); 
// проверяем ответ сервера, если код 334 значит все прошло успешно  
if (!parseSocketAnswer($socket, "334")){ 
   fclose($socket); 
}else{
	echo  " ALL OK! <br />";
}

// отправляем почтовому серверу пароль 
fputs($socket, base64_encode($smtp_password)."\r\n"); 
// проверяем ответ сервера, если код 235 значит все прошло успешно  
if (!parseSocketAnswer($socket, "235")){ 
   fclose($socket); 
   die('Ошибка авторизации'); 
} 

// сообщаем почтовому серверу e-mail отправителя 
fputs($socket, "MAIL FROM: <".$smtp_username.">\r\n"); 
// проверяем ответ сервера, если код 250 значит все прошло успешно  
if (!parseSocketAnswer($socket, "250")){ 
   fclose($socket); 
   die('Ошибка установки отправителя!!!!'); 
} else{
	echo $smtp_username. " ALL OK!!!! <br />";
}

// сообщаем почтовому серверу e-mail получателя 
fputs($socket, "RCPT TO: <" . $mailTo . ">\r\n"); 
// проверяем ответ сервера, если код 250 значит все прошло успешно  
if (!parseSocketAnswer($socket, "250")){ 
   fclose($socket); 
   die('Ошибка установки получателя'); 
} else{
	echo $mailTo. " ALL OK! <br />";
}

// сообщаем почтовому серверу, что сейчас начнем передавать данные письма  
fputs($socket, "DATA\r\n"); 

// проверяем ответ сервера, если код 354 значит все прошло успешно  
if (!parseSocketAnswer($socket, "354")){ 
   fclose($socket); 
   die('Ошибка при передачи данных письма'); 
} else{
	echo " DATA OK! <br />";
}


// передаем почтовому серверу данные письма 
fputs($socket, $headers."\r\n".$bodyMail."\r\n.\r\n"); //@@ пустая строка, затем точка и снова пустая строка

// проверяем ответ сервера, если код 250 значит все прошло успешно  
     if (!parseSocketAnswer($socket, "250")){ 
        fclose($socket); 
        die("Ошибка при передачи данных письма"); 
     } else{
	echo " TRANSFER OK! <br />";
}

     // сообщаем почтовому серверу, что закрываем соединение 
     fputs($socket, "QUIT\r\n"); 
     // закрываем соединение 
     fclose($socket); 

     // результат отправки 
     echo "Письмо успешно отправлено"; 

     // функция, которая будет анализировать ответ почтового сервера 
     // Ищет в ответе сервера необходимый код 
     function parseSocketAnswer($socket, $response) { 
        while  (@substr($responseServer, 3, 1) != ' ') { // читаем первые три символа из ответа сервера (напр. - 235 Authentication succeeded )
        // string substr ( string $string , int $start [, int $length ] )
        // Возвращает подстроку строки string, начинающейся с start символа по счету и длиной length символов. 
                if (!($responseServer = fgets($socket, 256))){ 
                // string fgets ( resource handle [, int length] )
                // Возвращает строку размером в length - 1 байт, прочитанную из дескриптора файла, на который указывает параметр handle. Чтение заканчивается, когда количество прочитанных байтов достигает length - 1				
				
                        return false; 
                } 
				echo $responseServer . "<br />"; // например - 235 Authentication succeeded				                                 // или  - 250 Accepted
        } 
        if (!(substr($responseServer, 0, 3) == $response)) { 
		echo "OUT";
                return false; 
        } 
		
        return true; 
     } 
	
//@@ справка PHP // Список опций командной строки, предоставляемых PHP, могут быть получены в любой момент, запустив PHP с ключом -h	
// -f 	--file 	Парсит и исполняет файл, указанный в опции -f . Этот параметр необязателен и может быть опущен - достаточно просто указать имя запускаемого файла.  



	 