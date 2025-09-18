<?php
add_action( 'admin_menu', 'add_page_import' );
function add_page_import() {
	add_menu_page( 'Импорт товаров', 'Импорт', 'manage_options', 'products_import', 'page_import_callback' );
}
function page_import_callback() { ?>

<div class="wrap">
	<h2>Импорт</h2>
</div>

<?php
global $result;

if (isset($result['status'])) {
	if ($result['status'] == 'error') {
		if (!empty($result['error-message'])) $message = $result['error-message'];
		else $message = 'Во время импорта произошла ошибка.';
		$message_class = 'error';
	}
	else if ($result['status'] == 'success') {
		if (!empty($result['success-message'])) $message = $result['success-message'];
		else $message = 'Операция успешно завершена.';
		$message_class = 'notice notice-success';
	}
}

if (!empty($message)) $render_message = '<div class="wrap"><div class="'.$message_class.'"><p><strong>'.$message.'</strong></p></div></div>';

if (!empty($render_message)) echo $render_message;

if (!empty($result['results'])) {
	echo '<p><b>Ошибки и сообщения:</b><p>';
	echo '<p><textarea rows="5" cols="65" readonly>';
		foreach ($result['results'] as $res) {
			if (!empty($res['error-message'])) echo $res['error-message']."\n";
		}
	echo '</textarea></p>';
	echo '<a href="'.get_template_directory_uri().'/product-import-export/log.txt" download>Скачать лог</a>';
}
?>

<form method="post" action="">
	<input type="hidden" name="form_name" value="import_from_url">

	<div class="card">
		<h2 class="title">Импорт по URL</h2>
		<table class="form-table">
			<tr>
				<th>Введите URL:</th>
				<td><input type="text" name="yml_url" value="" placeholder="Ссылка на yml файл" size="40" /></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" class="button-primary" value="Импортировать" /></td>
			</tr>
		</table>
	</div>
		
</form>

<form method="post" action="" enctype="multipart/form-data">
	<input type="hidden" name="form_name" value="import_from_file">

	<div class="card">
		<h2 class="title">Импорт из файла</h2>
		<table class="form-table">
			<tr>
				<th>Выберите yml-файл:</th>
				<td><input type="file" name="yml_file" accept=".yml, .xml"/></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" class="button-primary" value="Импортировать" /></td>
			</tr>
		</table>
	</div>

</form>

<form method="post" action="">
	<input type="hidden" name="form_name" value="import_from_string">
	
	<div class="card">
		<h2 class="title">Импорт из текста</h2>
		<table class="form-table">
			<tr>
				<th>Введите корректный yml:</th>
			</tr>
			<tr>
				<td><textarea name="yml_string" rows="5" cols="50"></textarea></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" class="button-primary" value="Импортировать" /></td>
			</tr>
		</table>
	</div>
		
</form>

<? } ?>