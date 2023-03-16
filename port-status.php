<?php

$dev = array(
'goip01' => '172.23.151.39',
'goip02' => '172.23.151.36',
'goip03' => '172.23.151.37',
'goip04' => '172.23.151.38',
'goip05' => '172.23.151.40',
'goip06' => '172.23.151.41',
'goip07' => '172.23.151.42',
'goip08' => '172.23.151.43',
'goip09' => '172.23.151.44',
'goip10' => '172.23.151.45',
'goip11' => '172.23.151.35',
'goip12' => '172.23.151.46',
'goip13' => '172.23.151.47',
'goip14' => '172.23.151.48',
'goip15' => '172.23.151.49',
'goip16' => '172.22.169.226',
'goip17' => '172.22.169.227',
'goip18' => '172.22.169.228',
'goip19' => '172.22.169.229',
'goip20' => '172.22.169.230',
'goip21' => '172.22.169.231',
'goip22' => '172.22.169.232',
'goip23' => '172.22.169.233',
'goip24' => '172.22.169.234',
'goip25' => '172.22.169.235',
'goip26' => '172.22.169.236',
'goip27' => '172.22.169.237',
'goip28' => '172.22.169.238',
);

$goip_user = "admin";
$goip_password = "vjqujbg";


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
