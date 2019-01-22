<?php
class StringChange{
	//字符转大写
	protected function toupper($c){
		$ord = ord($c);
		return $ord>=97 && $ord<=122 ?chr($ord-32):$c;


	}
	//字符转小写
	protected function tolower($c){
		$ord = ord($c);
		return $ord>=65 && $ord<=90 ?chr($ord+32):$c;
	}
	protected function checkempty($str){
		if($str === "")
		{
			return true;
		}
		return false;
	}

	public function strtoupper($str){
		$len = strlen($str);
		for($i=0;$i<$len;$i++){
			$str[$i] = $this->toupper($str[$i]);
		}
		return $str;
	}


	public function strtolower($str){
		if($this->checkempty($str))
		{
			return "";
		}
		$len = strlen($str);
		for($i=0;$i<$len;$i++){
			$str[$i] = $this->tolower($str[$i]);
		}
		return $str;
	}


	public function ucfirst($str){
		if($this->checkempty($str))
		{
			return "";
		}
		$str[0] = $this->toupper($str[0]);
		return $str;
	}

	public function lcfirst($str){
		if($this->checkempty($str))
		{
			return "";
		}
		$str[0] = $this->tolower($str[0]);
		return $str;
	}

	public function ucwords($str){
		if($this->checkempty($str))
		{
			return "";
		}
		$splitchar = [' ',"\n","\r","\f","\v"];
		$len = strlen($str);
		for($i=0;$i<$len;$i++){
			if(in_array($str[$i], $splitchar))
			{
				$i++;
				if($i>=$len)
				{
					break;
				}
				$str[$i] = $this->toupper($str[$i]);
			}
		}
		return $str;
	}

	public function lcwords($str){
		if($this->checkempty($str))
		{
			return "";
		}
		$splitchar = [' ',"\n","\r","\f","\v"];
		$len = strlen($str);
		for($i=0;$i<$len;$i++){
			if(in_array($str[$i], $splitchar))
			{
				$i++;
				if($i>=$len)
				{
					break;
				}
				$str[$i] = $this->tolower($str[$i]);
			}
		}
		return $str;
	}
}


$test = new StringChange();

var_dump($test->strtolower('HelloWorld'));
var_dump($test->strtoupper('HelloWorld'));
var_dump($test->ucfirst('helloWorld'));
var_dump($test->lcfirst('HelloWorld'));
var_dump($test->ucwords('hello world'));
var_dump($test->lcwords('Hello World'));

var_dump($test->lcwords('Hello World '));