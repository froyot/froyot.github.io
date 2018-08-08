<?php
$fromdir = str_replace("/",DIRECTORY_SEPARATOR,str_replace("\\", DIRECTORY_SEPARATOR, $argv[1]));
$todir = str_replace("/",DIRECTORY_SEPARATOR,str_replace("\\", DIRECTORY_SEPARATOR, $argv[2]));


function my_dir($dir) {
    $files = array();
    if(@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while(($file = readdir($handle)) !== false) {
            if($file != ".." && $file != ".") { //排除根目录；
                if(is_dir($dir."/".$file)) { //如果是子文件夹，就进行递归
                    $files[$file] = my_dir($dir."/".$file);
                } else { //不然就将文件的名字存入数组；
                    $files[] = rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
                }
 
            }
        }
        closedir($handle);
        return $files;
    }
}


$files = my_dir($fromdir);

foreach ($files as $key => $file) {
	$content = file_get_contents($file);
	$filename = substr($file,strrpos( $file,DIRECTORY_SEPARATOR )+1);

	preg_match("/(\d{4}\-\d{2}\-\d{2})\-.*\.md/", $filename,$match);
	if($match)
	{
		$filetime = $match[1];

	
		$content = preg_replace_callback('/\-\-\-(.*?)\-\-\-/s', function($match) use($filetime){
			$c = $match[1];

			if(strpos($c,'date')===false)
			{
				$match[0] = "---".$match[1]."date: ".$filetime.' '.date('H:i:s')."\r\n---";
			}
			return $match[0];
		}, $content);
		$content = preg_replace_callback('/```(.*?)```/', function($match) use($filetime){
			
			return '`'.$match[1].'`';
		}, $content);
		$content = preg_replace_callback("/\-\-\-.*?\-\-\-\r\n(\s+\r\n)?.*?\r\n\s*\r\n/s", function($match) use($filetime){
			
			return $match[0]."<!-- more --> \r\n\r\n";
		}, $content);
		if($content)
		{
			$path = $todir.DIRECTORY_SEPARATOR.$filename;
			file_put_contents($path, $content);
		}
	}
	
}