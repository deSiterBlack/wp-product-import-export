<?php 

global $result;

if (empty($_POST)) return;

if ($_POST['form_name'] == 'import_from_url') {
	
	add_action( 'wp_loaded', static function() {	// выполняем на хуке wp_loaded, чтобы категории успели инициализироваться
		global $result;

		// подготовка для импорта
		if (empty($_POST['yml_url'])) {
			return $result = ['status' => 'error','error-message' => 'Не получен адрес yml-файла'];
		} else {
			$yml_url = $_POST['yml_url'];
		}

		// запуск импорта
		$importer = new ProductImporter();
		$result = $importer->import_from_url($yml_url);

	});

}

if ($_POST['form_name'] == 'import_from_file') {

	add_action( 'wp_loaded', static function() {	// выполняем на хуке wp_loaded, чтобы категории успели инициализироваться
		global $result;

		// подготовка для импорта
		if (empty($_FILES['yml_file'])) return $result = ['status' => 'error','error-message' => 'Файл не получен' ];
	    file_put_contents(__DIR__ . '/log.txt', date('Y-m-d H:i:s') . ' '.'Файл получен' . PHP_EOL, FILE_APPEND);

		if ( ! function_exists( 'wp_handle_upload' ) )	require_once( ABSPATH . 'wp-admin/includes/file.php' );

		add_filter('upload_mimes', 'cc_mime_types');  // разрешаем загрузку xml и yml файлов
	    function cc_mime_types($mimes) {
	        $mimes['xml'] = 'text/xml'; 
	        $mimes['yml'] = 'text/xml'; 
	        return $mimes;
	    }

		$file = &$_FILES['yml_file'];
		$overrides = [ 'test_form' => false ];
		$file_array = wp_handle_upload( $file, $overrides ); // обрабатывает и загружает файл в папку upload

		if ( !empty($file_array['error']) ) return $result = ['status' => 'error','error-message' => 'Ошибка загрузки файла: '. $file_array['error'] ];
		file_put_contents(__DIR__ . '/log.txt', date('Y-m-d H:i:s') . ' '.'Файл загружен' . PHP_EOL, FILE_APPEND);

		// запуск импорта
		$importer = new ProductImporter();
		$result = $importer->import_from_file($file_array['file']);

	});    

}

if ($_POST['form_name'] == 'import_from_string') {

	add_action( 'wp_loaded', static function() {	// выполняем на хуке wp_loaded, чтобы категории успели инициализироваться
		global $result;

		// подготовка для импорта
		if (empty($_POST['yml_string'])) {
			return $result = ['status' => 'error','error-message' => 'Не получены yml-данные'];
		} else {
			$yml_string = $_POST['yml_string'];
		}

		// запуск импорта
		$importer = new ProductImporter();
		$result = $importer->import_from_string($yml_string);

	}); 

}

if ($_POST['form_name'] == 'export') {

	add_action( 'wp_loaded', static function() {
		global $result;
		
		$importer = new ProductExporter();
		$result = $importer->generate_yml();
	}); 

}