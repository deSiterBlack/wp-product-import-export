<?php
add_action( 'admin_menu', 'add_page_export' );
function add_page_export() {
	add_menu_page( 'Экспорт товаров', 'Экспорт', 'manage_options', 'products_export', 'page_export_callback' );
}
function page_export_callback() { ?>

<div class="wrap">
	<h2>Экспорт</h2>
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
	echo '<textarea rows="5" cols="65" readonly>';
		foreach ($result['results'] as $res) {
			if (!empty($res['error-message'])) echo $res['error-message']."\n";
		}
	echo '</textarea>';
	echo '<a href="'.get_template_directory_uri().'/product-import-export/log.txt" download>Скачать лог</a>';
}
?>

<p>Срабатывает также при обновлении, удалении и добавлении товаров.</p>

<form method="post" action="">
	<input type="hidden" name="form_name" value="export">

	<div class="card">
		<table class="form-table">
			<tr>
				<td><input type="submit" class="button-primary" value="Экспортировать" /></td>
				<?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/yml-export.xml')) { ?>
					<td><a class="button-primary" href="/yml-export.xml" download>Скачать файл</a></td>
				<?php }?>
			</tr>
		</table>
	</div>
		
</form>

<? } ?>