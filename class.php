<?php
if(!class_exists('Hametuha_CSV_Bulk_Importer')){
	
	
	/**
	 * CSVをAjaxで動的に読み込むクラスファイル
	 * 
	 * @version 1.0
	 * @author Takahashi Fumiki
	 * 
	 * @property-write string $title ページタイトル
	 * @property-write strign $menu_title メニュータイトル
	 * @property-write string $description 設定されていれば出力される
	 * @property-write int $step 一回あたりの処理行数。メモリなどが足りない場合は、この数を減らす。初期値100。
	 */
	class Hametuha_CSV_Bulk_Importer{



		/**
		 * @var string
		 */
		private $version = '1.0';



		/**
		 * 生成されたインスタンスの名前
		 * @var array
		 */
		private static $instance = array();



		/**
		 * 複数使用する場合は異なる名称にすること。
		 * @var string 
		 */
		private $name;



		/**
		 * ページタイトル
		 * @var string
		 */
		private $title = 'CSV一括インポート';



		/**
		 * メニュータイトル
		 * @var string
		 */
		private $menu_title = 'CSVインポート';
		
		
		
		/**
		 * 説明文（設定されていれば出力される）
		 * @var string
		 */
		private $description = '';
		



		/**
		 * 1回あたりに処理される件数
		 * @var int
		 */
		private $step = 100;



		/**
		 * CSVファイルの総行数
		 * @var int
		 */
		private $total = 0;



		/**
		 * CSVファイルへのパス
		 * @var string
		 */
		private $csv_files = array();



		/**
		 * 1回の処理を担当するコールバック関数
		 * 
		 * @var callback 引数は$row（CSVの配列）と$count（現在の行数）
		 */
		private $callback;



		/**
		 * このライブラリのルートURL
		 * @var string
		 */
		private $root_url;
		
		
		/**
		 * CSVの選択方法
		 * 
		 * @var string
		 */
		private $mode = 'media';
		
		
		
		/**
		 * CSVファイルの配列
		 * @var array
		 */
		private $csv = array();

		

		/**
		 * コンストラクタ
		 * 
		 * @param string $root_url このライブラリのルートディレクトリURL
		 * @param string $csv CSVファイルへのパス
		 * @param string $name プラグインの名前。インスタンスごとにユニークでなくてはならない。
		 */
		public function __construct($name = 'hbi'){
			if(false !== array_search($name, self::$instance)){
				// 名前の同じインスタンスは生成させない。
				trigger_error('複数のインスタンスを生成する場合は、名前を変更してください。', E_USER_WARNING);
			}else{
				// ルートディレクトリのURLを設定
				$this->root_url = $this->detect_url();
				// 名前を設定
				$this->name = $name;
				// アクションを登録
				add_action('admin_menu', array($this, 'admin_menu'));
				add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
				add_action('wp_ajax_'.$this->get_action_name(), array($this, 'ajax'));
			}
		}
		
		
		
		/**
		 * セッター
		 * 
		 * @param string $name
		 * @param mixed $value
		 */
		public function __set($name, $value) {
			switch ($name) {
				case 'title':
				case 'menu_title':
				case 'description':
					$this->{$name} = strval($value);
					break;
				case 'step':
					$this->step = intval($value);
					break;
				default:
					break;
			};
		}
		



		/**
		 * 管理画面を登録
		 */
		public function admin_menu(){
			add_submenu_page('tools.php', $this->title, $this->menu_title, apply_filters('csv_bulk_upload_cap', 'manage_options', $this->name), $this->name, array($this, 'admin_screen'));
		}
		
		
		
		
		/**
		 * 現在のフォルダからルートのURLを推測する
		 * 
		 * @return string
		 */
		private function detect_url(){
			$path = dirname(__FILE__);
			if(false !== strpos($path, 'wp-content/plugins')){
				return untrailingslashit(plugin_dir_url(__FILE__));
			}elseif(false !== strpos($path, 'wp-content/themes')){
				$path = explode('wp-content/themes', $path);
				return dirname(get_template_directory_uri()).$path[1];
			}else{
				return false;
			}
		}



		/**
		 * 管理画面にJSを読み込み
		 */
		public function enqueue_scripts(){
			if(isset($_GET['page']) && $_GET['page'] == $this->name){
				if(function_exists('wp_enqueue_media')){
					// 3.5以上ならメディアスクリプト読み込み
					wp_enqueue_media();
				}
				// 同梱のJS
				wp_enqueue_script(
					$this->name.'-helper',
					$this->root_url.'/ajax.js',
					array('jquery-form', 'jquery-effects-highlight'),
					$this->version
				);
				// CSS
				wp_enqueue_style($this->name.'-style', $this->root_url.'/style.css', null, $this->version);
			}
		}



		/**
		 * 管理画面を表示
		 */
		public function admin_screen(){
			include dirname(__FILE__).'/admin-screen.php';
		}
		
		
		
		/**
		 * CSVファイルを設定する。しない場合、メディアから選ぶ
		 * @param array $csvs
		 */
		public function set_csv($csvs){
			$this->csv_files = (array)$csvs;
		}



		/**
		 * コールバックを登録する
		 * 
		 * @param callback $callback
		 */
		public function register_callback($callback){
			if(is_callable($callback)){
				$this->callback = $callback;
			}else{
				trigger_error('コールバックとして有効なオブジェクトを登録してください。', E_USER_WARNING);
			}
		}
		
		
		
		/**
		 * Ajaxリクエストで分割処理
		 * 
		 * @global wpdb $wpdb
		 */
		public function ajax(){
			$json = array(
				'success' => false,
				'message' => '不正なアクセスです',
			);
			$res = array();
			if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_action_name())){
				$prepared = (boolean)$_REQUEST['prepared'];
				if($prepared){
					$res = $this->import($_REQUEST['csv']);
				}else{
					$res = $this->prepare($_REQUEST['csv'], ('utf-8' == $_REQUEST['encoding']) );
				}
			}
			header('Content-Type: application/json');
			echo json_encode(array_merge($json, $res));
			exit;
		}
		
		
		/**
		 * インポート可能かチェックする
		 * 
		 * @param int $index CSVのインデックスまたはアタッチメントID
		 * @return array
		 */
		private function prepare($index){
			$csv_lines = $this->get_total($index);
			if($csv_lines > 1){
				return array(
					'success' => true,
					'total' => $this->total,
					'message' => sprintf('%s行のCSVファイルをインポートします。よろしいですか？', number_format($this->total)), 
				);
			}else{
				return array('message' => '指定されたCSVは存在しないか、行数が1行以下です。');
			}
		}
		
		
		/**
		 * インポート処理を行う
		 * 
		 * @param int $index CSVのインデックスまたはアタッチメントID
		 * @param boolean $is_utf8 UTF8であるか否か
		 * @return array
		 */
		private function import($index, $is_utf8 = false){
			// 初期値
			$json = array(
				'success' => false,
				'message' => 'インポートに失敗しました。CSVファイルの形式を見直してください。',
				'imported' => 0,
			);
			// 処理開始
			$csv_path = $this->get_csv_path($index);
			if($csv_path){
				$counter = 0;
				$processed = 0;
				$total = $this->get_total($index);
				// 行の読み込みを自動判定（CRLF対策）
				ini_set("auto_detect_line_endings", "1");
				// オフセット（処理済みの行数）を取得
				$offset = isset($_REQUEST['imported']) ? intval($_REQUEST['imported']) : 0;
				$handle = fopen($csv_path, 'r');
				$header = array();
				while( false !== ($data = $this->fgetcsv_reg($handle)) ){
					// 1行目の場合はヘッダーとして登録してすぐ抜ける
					if(empty($header) && $counter == 0){
						$header = $is_utf8 ? $data : array_map(array($this, 'convert'), $data);
						$counter++;
						if($offset < $this->step){
							$processed++;
						}
						continue;
					}
					// 2行目以降は該当する場合だけ処理を行う
					if( ($counter >= $offset) && ($counter < $offset + $this->step) ){
						if(is_callable($this->callback)){
							$rows = array();
							foreach($header as $index => $name){
								$rows[$name] = $is_utf8 ? $data[$index] : $this->convert($data[$index]);
							}
							call_user_func_array($this->callback, array($rows, ($counter + 1), $total));
						}
						$processed++;
					}
					$counter++;
				}
				$json['success'] = true;
				$json['imported'] = $offset + $processed;
			}
			
			return $json;
		}
		
		
		
		



		/**
		 * actionの名称を返す
		 * @return string
		 */
		private function get_action_name(){
			return 'csvbulk_'.$this->name;
		}
		
		
		
		/**
		 * 指定されたインデックスのファイルが存在する場合、そのパスを返す
		 * 
		 * @param int $index
		 * @return string|false
		 */
		private function get_csv_path($index){
			if(empty($this->csv_files)){
				$attachment = get_post($index);
				$path = get_attached_file(intval($index));
				if($attachment && $path && 'text/csv' == $attachment->post_mime_type && file_exists($path)){
					return $path;
				}else{
					return false;
				}
			}elseif(isset($this->csv_files[$index])){
				$path = $this->csv_files[$index];
				if(file_exists($path)){
					return $path;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		


		/**
		 * 指定されたCSVファイルの総行数を返す
		 * 
		 * @param int $index CSVファイルのインデックスもしくはアタッチメントID
		 * @return int
		 */
		private function get_total($index){
			ini_set("auto_detect_line_endings", "1");
			$path = $this->get_csv_path($index);
			if($path && $this->total < 1){
				$handle = fopen($path, 'r');
				while(!feof($handle)){
					if(fgets($handle)){
						$this->total++;
					}
				}
			}
			return $this->total;
		}



		/**
		 * 配列の要素をShift_JISからUTF-8に変更
		 * 
		 * @param string $var
		 * @return string
		 */
		private function convert($var){
			return mb_convert_encoding($var, 'utf-8', 'sjis-win');
		}



		/**
		 * ファイルポインタから行を取得し、CSVフィールドを処理する
		 * 
		 * @see http://yossy.iimp.jp/wp/?p=56
		 * @author yossy
		 * @param resource handle
		 * @param int length
		 * @param string delimiter
		 * @param string enclosure
		 * @return ファイルの終端に達した場合を含み、エラー時にFALSEを返します。
		 */
		private function fgetcsv_reg (&$handle, $length = null, $d = ',', $e = '"') {
			$eof = false;
			$d = preg_quote($d);
			$e = preg_quote($e);
			$_line = "";
			while ( ($eof != true) and (!feof($handle)) ) {
				$dummy = array();
				$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
				$itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
				if ($itemcnt % 2 == 0) $eof = true;
			}
			$_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
			$_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
			$_csv_matches = array();
			preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
			$_csv_data = $_csv_matches[1];
			for($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++){
				$_csv_data[$_csv_i] = preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
				$_csv_data[$_csv_i] = str_replace($e.$e, $e, $_csv_data[$_csv_i]);
			}
			return empty($_line) ? false : $_csv_data;
		}
	}
}