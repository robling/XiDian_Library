<?php
/*
		$options = array('id' => '------', 'password' => '------');
		$lib = new Library_xidian($options);
		$list = $lib->getrentbook();
		$his = $lib->gethistorybook();
*/?>
<?php
	class Library_xidian{
		public $BASE_URL = "http://innopac.lib.xidian.edu.cn/patroninfo~S0*chx/";
		public $REFFER_URL = "http://opac.lib.xidian.edu.cn/patroninfo/";
		public $RENT_DIR = "/items";
		public $HISTORY_DIR = "/readinghistory";
		
		private $cookie_file;
		
		public $id;
		public $password;
		private $userdir;
		private $booklist;
		private $historybooklist;
		
		public function __construct($options){
			$this->id = $options['id'];
			$this->password = $options['password'];
			$this->authorize($this->id, $this->password);
		}
		private function authorize($id, $password){
			//login page
			$login_url = $this->REFFER_URL;
			//post:username & password
			$post_content = 'code='.$id.'&pin='.$password.'&pat_submit=xxx';
			//store cookie file temporary
			$this->cookie_file = tempnam('./tmp','cookie');
			//login and get cookie
			$ch = curl_init($login_url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);//302 redirection
			curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_content);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
			$file_contents = curl_exec($ch);
			//echo $file_contents;
			//echo "<hr/>";
			curl_close($ch);
			$this->redirection_render($file_contents);
		}
		private function redirection_render($respond_header){
			//匹配"/top"之前的7位数字
			if (preg_match("/\d{7}(?=\/top)/", $respond_header, $matches)) {
				$this->userdir = $matches[0];
			} else {
				$this->userdir = 'error';
			}
		}
		public function getrentbook(){
			//get rented books
			$get_url = $this->BASE_URL.$this->userdir.$this->RENT_DIR;
			$ch = curl_init($get_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
			$file_contents = curl_exec($ch);
			//echo $file_contents;
			//echo "<hr/>";
			curl_close($ch);
			return $this->booklist_render($file_contents);
		}
		private function booklist_render($file_contents){
			//(?<=chx"> ).+(?=</a></label>)
			preg_match_all("/(?<=chx\"> ).+(?=<\/a><\/label>)/", $file_contents, $titlelist, PREG_PATTERN_ORDER);
			//(?<=到期 )\d{2}-\d{2}-\d{2}
			preg_match_all("/(?<=到期 )\d{2}-\d{2}-\d{2}/", $file_contents, $timelist, PREG_PATTERN_ORDER);
			for($i=0;$i<sizeof($titlelist[0]);$i++){
				$title = $titlelist[0][$i];
				$time = $timelist[0][$i];
				$this->booklist[$i] = array('title'=>$title,'time'=>$time,);
			}
			return $this->booklist;
		}
		public function gethistorybook(){
			//get history  books
			$get_url = $this->BASE_URL.$this->userdir.$this->HISTORY_DIR;
			$ch = curl_init($get_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
			$file_contents = curl_exec($ch);
			//echo $file_contents;
			//echo "<hr/>";
			curl_close($ch);
			return $this->historybooklist_render($file_contents);
		}
		private function historybooklist_render($file_contents){
			//(?<=chx">).*(?=</a>)
			preg_match_all("/(?<=chx\">).*(?=<\/a>)/", $file_contents, $list, PREG_PATTERN_ORDER);
			$this->historybooklist = $list[0];
			return $this->historybooklist;
		}
		public function getuserdir(){
			return $this->userdir;
		}
	}
