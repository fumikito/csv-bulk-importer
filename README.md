csv-bulk-importer
=================

WordPressでCSVのインポート処理を行うクラス。サーバにアップロードしたファイルを扱う。ステップ数を指定できるので、多い日も安心。

# 使用方法
* クラスファイルを読み込む。
* インスタンス化する。

```php
include 'path/to/calss.php';
// 初期化時にプロパティを設定
$importer = new Hametuha_CSV_Bulk_Importer('http://path/to/root/diretcoty',
	'path/to/csv', 'my_importer');
// セッターでメニュー名などを変更できます。
$importer->menu_title = '商品情報登録';
// コールバック関数を登録しないと何も起きません。
$importer->register_callback('_my_func');
/**
 * コールバック関数は以下の通りです。
 * 
 * @param array $row CSVの行が連想配列で渡ります。キーはヘッダー名です。
 * @param int
 */
function _my_func($row, $index){
	// 何か登録したりする
	wp_insert_post(array(
		'post_title' => $row['title'],
		'post_content' => $row['content'],
		'post_type' => 'post_type',
	));
}
```

