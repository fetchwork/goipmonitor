<?php

$dev = array(
'goip01' => '172.23.151.39',
'goip02' => '172.23.151.36',
'goip03' => '172.23.151.37'
);

$goip_user = "admin";
$goip_password = "";


#Это нужно для авторизации на goip-е
$context = stream_context_create(array(
    'http' => array(
		'timeout' => 2,
        'header'  => "Authorization: Basic " . base64_encode("$goip_user:$goip_password")
    )
));

switch($_GET['format']){
	default:
	case "html":
		$goip_addr = 'http://'.$dev[$_GET['device']]; 
		#получаем сырые данные - html
		$data = file_get_contents($goip_addr."/default/en_US/status.html", false, $context);

		if($data!=''){
			//Получаем статус SIM
			preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_gsm_sim\">(.*)\<\/td\>/", $data, $sim);
			//Получаем имя оператора
			preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_gsm_cur_oper\">(.*)\<\/td\>/", $data, $oper);
			//Получаем статус линии
			preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_line_state\">(.*)\<\/td\>/", $data, $status);

			//Узнаём количество портов
			$ports_count = count($status[5]);

			print '<table align="center" width="500" border="1">
				<tr><td align="center"><b>Порт №</b></td><td align="center"><b>SIM</b></td><td align="center"><b>Оператор</b></td><td align="center"><b>Статус</b></td></tr>';

			for ($i=0;$i<=$ports_count-1;$i++){
				$line = $i+1;
				print '<tr><td align="center">'.$line.'</td><td align="center">'.$sim[5][$i].'</td><td align="center">'.$oper[5][$i].'</td><td align="center">'.$status[5][$i].'</td></tr>';
			}

			print '</table>';

		}else{
			print '<center><h1>GSM-шлюз: '.$_GET['device'].' по IP '.$dev[$_GET['device']].' недоступен</h1></center>';
		}
	break;
	case "json":
			print "[\n";
			$total = count($dev);
			$counter = 0;
			foreach($dev as $goip_id => $goip_ip){
				//берём адрес шлюза из массива
				$goip_addr = 'http://'.$goip_ip;
				
				//получаем сырые данные - html
				$data = file_get_contents($goip_addr."/default/en_US/status.html", false, $context);
				
				$counter++;
				//если перебор шлюзов закончен, то не ставим запятую в конце элемента
				if($counter == $total){
					$elem_split = '';
				}
				else{
					$elem_split = ',';
				}
				
				print "{\"dev\":";
				//если шлюз доступен
				if($data!=''){
					//Получаем статус SIM
					preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_gsm_sim\">(.*)\<\/td\>/", $data, $sim);
					//Получаем имя оператора
					preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_gsm_cur_oper\">(.*)\<\/td\>/", $data, $oper);
					//Получаем статус линии
					preg_match_all("/<td height=\"(\d*)\" class=\"(.*)\" align=\"(.*)\" id=\"l(\d*)_line_state\">(.*)\<\/td\>/", $data, $status);
					
					//Узнаём количество портов
					$ports_count = count($status[5]);
					
					print "\"".$goip_id."\",\"port\":{";
					
					//$i = 0;
					for ($i=0;$i<=$ports_count-1;$i++){
						
						$line = $i+1;
						if($line < 10){ $line = '0'.$line;}
						$split = '';
						if ($i != 0) { $split = ','; }
						
						//удаляем пробел в начале
						$operator = str_replace('&nbsp;','',$oper[5][$i]);
						//удаляем пробел в начале
						$state = str_replace('&nbsp;','',$status[5][$i]);
						//убираем номер
						$state_res = explode(":",$state);
						//удаляем html 
						$sim_active =  preg_replace("/<font color=\"#FF0000\">(.*)<\/font>/","N",$sim[5][$i]);
						print "".$split."\"".$line."\":{\"sim\":\"".$sim_active."\",\"operator\":\"".$operator."\",\"status\":\"".$state_res[0]."\"}";
					}
					print "},\"available\":\"yes\"}".$elem_split."\n";
				}else{
					print "\"".$goip_id."\",\"available\":\"no\"}".$elem_split."\n";
				}
				
			}
			print "]";
	
	break;
}
?>
