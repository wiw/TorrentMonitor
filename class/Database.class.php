<?php
class Database
{
    public $dbh;
    private static $instance;

    private function __construct()
    {
		$db_type = Config::read('db.type');
		
		switch ($db_type)
		{
			case 'mysql':
				$dsn = "mysql:host=".Config::read('db.host').";dbname=".Config::read('db.basename');
				$username = Config::read('db.user');
				$password = Config::read('db.password');
				$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".Config::read('db.charset'));
				break;
			case 'sqlite':
				$dsn = "sqlite:".Config::read('db.basename');
				$options = array(PDO::ATTR_PERSISTENT => true);
				break;
			case 'sqlite2':
				$dsn = "sqlite2:".Config::read('db.basename');
				$options = array(PDO::ATTR_PERSISTENT => true);
				break;
			case 'sqlsrv':
				$dsn = "sqlsrv:Server=".Config::read('db.host').",".Config::read('db.port').";Database=".Config::read('db.basename');
				$username = Config::read('db.user');
				$password = Config::read('db.password');
				break;
			case 'pgsql':
				$dsn = "pgsql:host=".Config::read('db.host').";".Config::read('db.port').";dbname=".Config::read('db.basename').";user=".Config::read('db.user').";password=".Config::read('db.password');
				break;
		}
		
		try {
        	$this->dbh = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
		    print "Error!: " . $e->getMessage() . "<br/>";
		    die();
		}
    }

    public static function getInstance()
    {
        if ( ! isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
    
    public static function getSetting($param)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `val` FROM `settings` WHERE `key` = :param");
    	$stmt->bindParam(':param', $param);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				return $row['val'];
			}
			return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getAllSetting()
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT * FROM `settings`");
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				$resultArray[] = array("{$row['key']}" => "{$row['val']}");
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function updateSettings($setting, $val)
    {
		$stmt = Database::getInstance()->dbh->prepare("UPDATE `settings` SET `val` = :val WHERE `key` = :setting");
    	$stmt->bindParam(':setting', $setting);
    	$stmt->bindParam(':val', $val);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function getCredentials($tracker)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `log`, `pass` FROM `credentials` WHERE `tracker` = :tracker");
		$stmt->bindParam(':tracker', $tracker);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ($row['log'] != "" && $row['pass'] != "")
				{
					$resultArray['login'] = $row['log'];
					$resultArray['password'] = $row['pass'];
				}
				else
					return FALSE;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getAllCredentials()
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT * FROM `credentials`");
		if ($stmt->execute())
		{
			$i = 0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$resultArray[$i]['tracker'] = $row['tracker'];
				$resultArray[$i]['login'] = $row['log'];
				$resultArray[$i]['password'] = $row['pass'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }    
    
    public static function countCredentials($password)
    {
		$stmt = Database::getInstance()->dbh->prepare("SELECT COUNT(*) AS `count` FROM `settings` WHERE `key` = 'password' AND `val` = :password");
    	$stmt->bindParam(':password', $password);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ($row['count'] == '1')
					return TRUE;
				else
					return FALSE;
			}
		}
		$stmt = NULL;
    }
    
    public static function updateCredentials($password)
    {
		$stmt = Database::getInstance()->dbh->prepare("UPDATE `settings` SET `val` = :password WHERE `key` = 'password'");
    	$stmt->bindParam(':password', $password);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function setCredentials($id, $login, $password)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `credentials` SET `log` = :login, `pass` = :password WHERE `id` = :id");
    	$stmt->bindParam(':id', $id);
    	$stmt->bindParam(':login', $login);
    	$stmt->bindParam(':password', $password);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function checkTrackersCredentialsExist($tracker)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `log`, `pass` FROM `credentials` WHERE `tracker` = :tracker");
    	$stmt->bindParam(':tracker', $tracker);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ( ! empty($row['log']) && ! empty($row['pass']))
					return TRUE;
				else
					return FALSE;
			}
		}
		$stmt = NULL;
    }    
    
    public static function getTrackersList()
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker` FROM `credentials` ORDER BY `tracker`");
		if ($stmt->execute())
		{
			$i = 0;
			foreach ($stmt as $row)
			{
				$resultArray[$i] = $row['tracker'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getTorrentsList($order)
    {
    	if ($order == "name")
			$order = "`tracker`, `name`";
		if ($order == "date")
			$order = "`timestamp` ASC";
		
		$stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name`, `hd`, `torrent_id`, `ep`, `timestamp`, DATE_FORMAT(`timestamp`,'%d') AS `day`, DATE_FORMAT(`timestamp`,'%m') AS `month`, DATE_FORMAT(`timestamp`,'%Y') AS `year`, DATE_FORMAT(`timestamp`,'%T') AS `time` FROM `torrent` ORDER BY {$order}");
		if ($stmt->execute())
		{
			$i = 0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$resultArray[$i]['tracker'] = $row['tracker'];
				$resultArray[$i]['name'] = $row['name'];
				$resultArray[$i]['hd'] = $row['hd'];
				$resultArray[$i]['torrent_id'] = $row['torrent_id'];
				$resultArray[$i]['ep'] = $row['ep'];
				$resultArray[$i]['timestamp'] = $row['timestamp'];
				$resultArray[$i]['day'] = $row['day'];
				$resultArray[$i]['month'] = $row['month'];
				$resultArray[$i]['year'] = $row['year'];
				$resultArray[$i]['time'] = $row['time'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getTorrentsListByTracker($tracker)
    {
    	if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
    		$fields = '`hd`, `ep`';
    	if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.ru')
    		$fields = '`torrent_id`';
    		
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `name`, `timestamp`, ".$fields." FROM `torrent` WHERE `tracker` = :tracker ORDER BY `id`");
		$stmt->bindParam(':tracker', $tracker);
		if ($stmt->execute())
		{
			$i = 0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['name'] = $row['name'];
				$resultArray[$i]['timestamp'] = $row['timestamp'];
				if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
				{
					$resultArray[$i]['hd'] = $row['hd'];
					$resultArray[$i]['ep'] = $row['ep'];
				}
				if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.ru')
					$resultArray[$i]['torrent_id'] = $row['torrent_id'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getUserToWatch()
    {
	    $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name` FROM `watch` ORDER BY `tracker`");	
	    if ($stmt->execute())
	    {
	    	$i = 0;
		    foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$resultArray[$i]['tracker'] = $row['tracker'];
				$resultArray[$i]['name'] = $row['name'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
	    }
	    $stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function checkUserExist($tracker, $name)
    {
	    $stmt = Database::getInstance()->dbh->prepare("SELECT COUNT(*) AS `count` FROM `watch` WHERE `tracker` = :tracker AND `name` = :name");
    	$stmt->bindParam(':tracker', $tracker);
    	$stmt->bindParam(':name', $name);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ($row['count'] == 0)
					return TRUE;
				else
					return FALSE;
			}
		}
		$stmt = NULL;
    }
    
    public static function setUser($tracker, $name)
    {
		$stmt = Database::getInstance()->dbh->prepare("INSERT INTO `watch` (`tracker`, `name`) VALUES (:tracker, :name)");
		$stmt->bindParam(':tracker', $tracker);
		$stmt->bindParam(':name', $name);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function deletUser($id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("DELETE FROM `watch` WHERE `id` = :id");
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
		{
            $stmt = Database::getInstance()->dbh->prepare("DELETE FROM `buffer` WHERE `user_id` = :id");
            $stmt->bindParam(':id', $id);		
            if ($stmt->execute())
                return TRUE;
            else
                return FALSE;
			return TRUE;
        }
		else
			return FALSE;
		$stmt = NULL;
    }   
    
     
    public static function addThremeToBuffer($user_id, $section, $threme_id, $threme, $tracker)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT COUNT(*) AS `count` FROM `buffer` WHERE `user_id` = :user_id AND `threme_id` = :threme_id");
    	$stmt->bindParam(':user_id', $user_id);
    	$stmt->bindParam(':threme_id', $threme_id);
    	if ($stmt->execute())
    	{
    		foreach ($stmt as $row)
			{
				if ($row['count'] == 0)
				{
					$stmt = Database::getInstance()->dbh->prepare("INSERT INTO `buffer` (`user_id`, `section`, `threme_id`, `threme`, `tracker`) VALUES (:user_id, :section, :threme_id, :threme, :tracker)");
					$stmt->bindParam(':user_id', $user_id);
					$stmt->bindParam(':section', $section);
					$stmt->bindParam(':threme_id', $threme_id);
					$threme = preg_replace('/<wbr>/', '', $threme);
					$stmt->bindParam(':threme', $threme);
					$stmt->bindParam(':tracker', $tracker);
					if ($stmt->execute())
						return TRUE;
					else
						return FALSE;
					$stmt = NULL;
				}
	    	}
    	}
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function getThremesFromBuffer($user_id)
    {
	    $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `section`, `threme_id`, `threme` FROM `buffer` WHERE `user_id` = :user_id AND `new` = '1' ORDER BY `threme_id` DESC");
	    $stmt->bindParam(':user_id', $user_id);
		if ($stmt->execute())
		{
			$i=0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$resultArray[$i]['section'] = $row['section'];
				$resultArray[$i]['threme_id'] = $row['threme_id'];
				$resultArray[$i]['threme'] = $row['threme'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;	    
    }
    
    public static function transferFromBuffer($id)
    {
        $stmt = Database::getInstance()->dbh->prepare("SELECT `buffer`.`threme_id`, `buffer`.`threme`, `watch`.`tracker` FROM `buffer` LEFT JOIN `watch` ON `buffer`.`user_id` = `watch`.`id` WHERE `buffer`.`id` = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
		{
    		foreach ($stmt as $row)
			{
    			$threme = $row['threme_id'];
    			$name = $row['threme'];
    			$tracker = $row['tracker'];
    			Database::setThreme($tracker, $name, $threme);
    			Database::deleteFromBuffer($id);
			}
		}
    }
    
    public static function deleteFromBuffer($id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `buffer` SET `new` = 0 WHERE `id` = :id");
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function selectAllFromBuffer()
    {
	    $stmt = Database::getInstance()->dbh->prepare("SELECT `id` FROM `buffer` WHERE `accept` = '0' AND `new` = '1'");
		if ($stmt->execute())
		{
			$i=0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;	    
    }
    
    public static function updateThremesToDownload($id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `buffer` SET `accept` = 1, `new` = 0 WHERE `id` = :id");
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;	    
    }

    public static function takeToDownload($tracker)
    {
	    $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `threme_id`, `threme` FROM `buffer` WHERE `accept` = '1' AND `downloaded` = '0' AND `tracker` = :tracker");
	    $stmt->bindParam(':tracker', $tracker);
		if ($stmt->execute())
		{
			$i=0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['id'] = $row['id'];
				$resultArray[$i]['threme_id'] = $row['threme_id'];
				$resultArray[$i]['threme'] = $row['threme'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL; 
    }
    
    public static function setDownloaded($id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `buffer` SET `downloaded` = '1' WHERE `id` = :id");
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;        
    }
    
    public static function checkSerialExist($tracker, $name, $hd)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT COUNT(*) AS `count` FROM `torrent` WHERE `tracker` = :tracker AND `name` = :name AND `hd` = :hd");
    	$stmt->bindParam(':tracker', $tracker);
    	$stmt->bindParam(':name', $name);
    	$stmt->bindParam(':hd', $hd);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ($row['count'] == 0)
					return TRUE;
				else
					return FALSE;
			}
		}
		$stmt = NULL;    
    }
    
    public static function checkThremExist($tracker, $id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT COUNT(*) AS `count` FROM `torrent` WHERE `tracker` = :tracker AND `torrent_id` = :id");
    	$stmt->bindParam(':tracker', $tracker);
    	$stmt->bindParam(':id', $id);
		if ($stmt->execute())
		{
			foreach ($stmt as $row)
			{
				if ($row['count'] == 0)
					return TRUE;
				else
					return FALSE;
			}
		}
		$stmt = NULL;
    }
    
    public static function setSerial($tracker, $name, $hd=FALSE)
    {
		$stmt = Database::getInstance()->dbh->prepare("INSERT INTO `torrent` (`tracker`, `name`, `hd`) VALUES (:tracker, :name, :hd)");
		$stmt->bindParam(':tracker', $tracker);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':hd', $hd);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function setThreme($tracker, $name, $threme)
    {
		$stmt = Database::getInstance()->dbh->prepare("INSERT INTO `torrent` (`tracker`, `name`, `torrent_id`) VALUES (:tracker, :name, :threme)");
		$stmt->bindParam(':tracker', $tracker);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':threme', $threme);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function setNewDate($id, $date)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `torrent` SET `timestamp` = :date WHERE `id` = :id");
		$stmt->bindParam(':date', $date);
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function setNewEpisode($id, $ep)
    {
    	$stmt = Database::getInstance()->dbh->prepare("UPDATE `torrent` SET `ep` = :ep WHERE `id` = :id");
		$stmt->bindParam(':ep', $ep);
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function deletItem($id)
    {
    	$stmt = Database::getInstance()->dbh->prepare("DELETE FROM `torrent` WHERE `id` = :id");
		$stmt->bindParam(':id', $id);
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function getWarningsCount()
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `where`, COUNT(*) AS `count` FROM `warning` GROUP BY `where`");
		if ($stmt->execute())
		{
			$i=0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['where'] = $row['where'];
				$resultArray[$i]['count'] = $row['count'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }
    
    public static function getWarningsList($tracker)
    {
    	$stmt = Database::getInstance()->dbh->prepare("SELECT `time`, `reason`, `where`,
						DATE_FORMAT(`time`, '%d') as 'day',
						DATE_FORMAT(`time`, '%m') as 'month', 
						DATE_FORMAT(`time`, '%Y') as 'year', 
						DATE_FORMAT(`time`, '%H:%i') as 'time'
						FROM `warning` 
						WHERE `where` = :tracker
						ORDER BY `time` DESC");
		$stmt->bindParam(':tracker', $tracker);
		if ($stmt->execute())
		{
			$i=0;
			foreach ($stmt as $row)
			{
				$resultArray[$i]['time'] = $row['time'];
				$resultArray[$i]['reason'] = $row['reason'];
				$resultArray[$i]['where'] = $row['where'];
				$resultArray[$i]['day'] = $row['day'];
				$resultArray[$i]['month'] = $row['month'];
				$resultArray[$i]['year'] = $row['year'];
				$resultArray[$i]['time'] = $row['time'];
				$i++;
			}
			if ( ! empty($resultArray))
				return $resultArray;
		}
		$stmt = NULL;
		$resultArray = NULL;
    }        
    
    public static function setWarnings($date, $tracker, $message)
    {
    	$stmt = Database::getInstance()->dbh->prepare("INSERT INTO `warning` (`time`, `where`, `reason`) VALUES (:date, :tracker, :message)");
    	$stmt->bindParam(':date', $date);
    	$stmt->bindParam(':tracker', $tracker);
    	$stmt->bindParam(':message', $message);

		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;
    }
    
    public static function clearWarnings($tracker)
    {
    	$stmt = Database::getInstance()->dbh->prepare("DELETE FROM `warning` WHERE `where` = '{$tracker}'");
		if ($stmt->execute())
			return TRUE;
		else
			return FALSE;
		$stmt = NULL;    
    }
}
?>