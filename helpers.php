<?php

// функция записи сообщения в лог
function write_to_log($str) {
	file_put_contents(__DIR__ . '/log.txt', date('Y-m-d H:i:s') . ' - '.$str . PHP_EOL, FILE_APPEND);
}

// вешаем обновление выгрузки на хуки
add_action( 'delete_post', 'update_yml' );
add_action( 'post_updated', 'update_yml' );
add_action( 'save_post', 'update_yml' );
add_action( 'saved_term', 'update_yml' );
function update_yml() {
	add_action( 'wp_loaded', static function() {
		$importer = new ProductExporter();
		$result = $importer->generate_yml();
	}); 
}