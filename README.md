csv-bulk-importer
=================

WordPressでCSVのインポート処理を行うクラス。ステップ数とコールバックを指定できるので、多い日も安心。

## 使用方法
* テーマやプラグインフォルダにフォルダごと配置する
	* テーマやプラグインをGitで管理している場合はサブモジュールとして登録しておくと便利です。
	* JSやCSSなどが同梱されていますが、自分がテーマなのかプラグインなのかを判断してURLは取得できるようになっています。
	* あまりないとは思いますが、複数のプラグインやテーマにバンドルされていた場合、読み込む順番はプラグインやテーマ次第なので、バージョンは一番最初に読み込まれたクラス次第となります。
* クラスファイルを読み込み、インスタンス化する。
	* インスタンス化するタイミングは、プラグインなら`plugins_loaded`フック、テーマなら`after_setup_theme`フックがよいと思います。
	* CSVを指定しない場合はメディアアップローダーを利用し、アップロード済みのCSVから選択します。セキュリティなどの理由から別のフォルダを使用したい場合は、パスを指定することでプルダウンから選択することができます。

```php
// プラグインが読み込まれたら、CSVクラスを初期化するフックを登録  
add_action('plugins_loaded', '_my_init_csv');  

/**  
 * CSVクラスを初期化する関数  
 */  
function _my_init_csv(){  
    include 'path/to/class.php';  
    // 初期化時にユニークな名称（半角英数）を設定  
    $importer = new Hametuha_CSV_Bulk_Importer('my_importer');  
    // セッターでメニュー名などを変更できます。  
    $importer->menu_title = '商品情報登録';  
    // 自分で用意したCSVを使いたい場合は、  
    // パスの配列を渡します。  
    $importer->set_csv($csv_file_path);  
    // コールバック関数を登録ます。  
    // しないと何も起きません。  
    $importer->register_callback('_my_func');  
}
  
  
/**  
 * CSVの各行を処理するコールバック関数  
 *   
 * @param array $row CSVの行が連想配列で渡ります。キーはヘッダー名です。  
 * @param int $index CSVの何行目か。ヘッダーが1行目です。  
 */  
function _my_func($row, $index){  
	// 何か登録したりする  
	wp_insert_post(array(  
		'post_title' => $row['title'],  
		'post_content' => $row['content'],  
		'post_type' => 'post',  
		'post_status' => 'publish',  
	));  
}  
```

## CSVの仕様

* 1行目はヘッダーにしてください。このヘッダーがコールバック関数に渡される配列のキー名となります。
* Excelで作成したCSVの場合はShift_JISを、それ以外の場合はUTF-8をエンコーディングとして使用します。
* エンコーディングは最終的にUTF-8に変換されます。UTF-16のCSVなどは利用できません。
* 区切り文字はいまのところ、半角カンマだけになっています。


