<?php

class ProductImporter {
    
    private $yml_file;
    private $yml_url;
    private $yml_string;
    private $processed_terms; // обработанные категории
    private $product_cat_terms; // все категории товаров после обработки категорий
    private $processed_products; // обработанные товары
    private $product_images; // изображения товаров
    
    public function __construct() {

        $this->processed_products = [];
        $this->processed_terms = [];
        $this->product_cat_terms = [];
        $this->product_images = [];

    }

    // получение файла выгрузки
    public function import_from_file($yml_file) {
        $this->yml_file = $yml_file;
        write_to_log('Запущен импорт из файла');

        // Обработка возможных ошибок
        if (!file_exists($this->yml_file)) return ['status' => 'error', 'error-message' => 'YML-файл не найден'];
        if (!function_exists('simplexml_load_file')) return ['status' => 'error', 'error-message' => 'Функция simplexml_load_file не найдена'];

        write_to_log('Файл отправлен на распарсинг');
        $data = simplexml_load_file($this->yml_file); // парсим файл

        if (!$data) return ['status' => 'error', 'error-message' => 'Ошибка парсинга'];
        
        $results = $this->import($data);

        return ['status' => 'success', 'results' => $results];

    }

    // получение данных выгрузки по URL
    public function import_from_url($yml_url) {
        $this->yml_url = $yml_url;
        write_to_log('Запущен импорт по URL');

        // Обработка возможных ошибок
        $file_headers = get_headers($this->yml_url);
        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') return ['status' => 'error', 'error-message' => '404: Страница не найдена'];
        if (!function_exists('simplexml_load_string')) return ['status' => 'error', 'error-message' => 'Функция simplexml_load_string не найдена'];

        write_to_log('Строка отправлена на распарсинг');
        $data = simplexml_load_string(file_get_contents($this->yml_url));

        if (!$data) return ['status' => 'error', 'error-message' => 'Ошибка парсинга'];        

        $results = $this->import($data);

        return ['status' => 'success', 'results' => $results];

    }

    // получение данных выгрузки из текстового поля
    public function import_from_string($yml_string) {
        $this->yml_string = $yml_string;
        write_to_log('Запущен импорт строки из поля');

        // Обработка возможных ошибок
        if (empty($this->yml_string)) return ['status' => 'error', 'error-message' => 'YML данные не получены'];
        if (!function_exists('simplexml_load_string')) return ['status' => 'error', 'error-message' => 'Функция simplexml_load_string не найдена'];

        $this->yml_string = stripslashes($this->yml_string);
        $data = simplexml_load_string($this->yml_string);

        if (!$data) return ['status' => 'error', 'error-message' => 'Ошибка парсинга строки'];        

        $results = $this->import($data);

        return ['status' => 'success', 'results' => $results];

    }
    
    // основная обработка YML
    public function import($data) {
    
        $results = [];

        // добавим категории из выгрузки в цикле
        write_to_log('Импорт категорий');
        foreach ($data->shop->categories->category as $cat) {
            $result = $this->import_category($cat);
            $results[] = $result;
        }

        // получим список категорий, чтобы не получать их в дальнейшем цикле
        $args = array(
            'post_types' => 'product',
            'taxonomy' => 'product_cat',
            'hide_empty' => false, 
        );
        $terms = get_terms( $args );
        if ( $terms ) {
            foreach ( $terms as $term ) {
                $this->product_cat_terms[] = ['term' => $term->term_id, 'cat_id' => get_term_meta( $term->term_id, 'id', 1 )];
            }
        }

        // получим все прикреплённые изображения, чтобы не получать их в дальнейшем цикле
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
        );
        $images = get_posts( $args );
        foreach ( $images as $image ) {
            $image_data = wp_get_attachment_metadata( $image->ID );
            if (!empty($image_data['image_meta']['id'])) {
                $image_custom_id = $image_data['image_meta']['id'];
                $parent_id = $image->post_parent;
                $this->product_images[$parent_id][] = ['id' => $image->ID, 'custom_id' => $image_custom_id];
            }
        }

        // обработаем каждый элемент выгрузки в цикле
        write_to_log('Импорт товаров');
        foreach ($data->shop->offers->offer as $offer) {
            $result = $this->import_item($offer);
            $results[] = $result;
        }

        // статус товаров, отсутствовавших в выгрузке, изменим на черновик 
        write_to_log('Обработка черновиков');
        $args = array(
            'post_type' => 'product',
        );
        $products = get_posts( $args );
        if ( $products ) {
            foreach ( $products as $product ) {
                if (in_array($product->ID, $this->processed_products)) continue;
                $post_data = array(
                    'ID' => $product->ID,
                    'post_status' => 'draft',
                );
                wp_update_post($post_data);
            }
        } 

        // Обновим выгрузку
        if (class_exists('ProductExporter')) {
            write_to_log('Обновление выгрузки');
            $importer = new ProductExporter();
            $results[] = $importer->generate_yml();
        } else {
            $results[] = ['status' => 'error', 'error-message' => 'Ошибка обновления выгрузки: Класс ProductExporter не найден'];
        }

        
        return $results;
    }

    // обработка каждой категории из выгрузки
    private function import_category($cat) {

        // подготовим данные
        if (!empty($cat['id'])) $cat_id = htmlspecialchars($cat['id']);
        if (!empty($cat[0])) $cat_title = htmlspecialchars($cat[0]);

        // не обрабатываем категории с незаполненным id
        if (empty($cat_id)) {   
            if (!empty($cat_title)) return ['status' => 'error', 'error-message' => 'Не заполнен id' ,'title'   => $cat_title ]; 
            else return ['status' => 'error', 'error-message' => 'Не заполнен id' , 'title'   => 'Название не указано' ];
        }

        // находим категорию по id
        $updated_terms = [];
        $args = array(
            'post_types' => 'product',
            'taxonomy' => 'product_cat',
            'hide_empty' => false, 
            'meta_key'   => 'id', 
            'meta_value' => $cat_id, 
            'meta_compare' => '=', 
        );
        $terms = get_terms( $args );
        if ( $terms ) {
            foreach ( $terms as $term ) {
                $updated_terms[] = ['term' => $term, 'cat_id' => $cat_id];
            }
        }

        // не обрабатываем категории с id, дублирующимися на стороне сайта
        if (count($updated_terms) > 1) return ['status' => 'error', 'error-message' => 'Категории с дублирующимися id на сайте не обновлены. id категорий: '.$cat_id ];

        // при обнаружении в выгрузке второго и последующих вхождений категорий с одинаковыми id, их не обрабатываем
        if ((count($updated_terms) == 1) && (in_array($updated_terms[0]['term']->term_id, $this->processed_terms))) return ['status' => 'error', 'error-message' => 'В выгрузке обнаружено дублирование id, обновлено только первое вхождение категории. id категории: '.$cat_id ];

        // обновляем или добавляем категорию
        if (count($updated_terms) == 1)  $term = wp_update_term( $updated_terms[0]['term']->term_id, 'product_cat', ['name' => $cat_title] );
        else $term = wp_insert_term( $cat_title, 'product_cat' );

        if ( is_wp_error( $term ) ) return ['status' => 'error', 'error-message'   => $cat_title.' Категория не импортирована: '.$term->get_error_message()];
        write_to_log('Категория с id '.$cat_id.' добавлена');

        $this->processed_terms[] = $term['term_id'];

        update_term_meta( $term['term_id'], 'id', sanitize_text_field( $cat_id ) );

        return ['status' => 'success', 'post_id' => $term['term_id'], 'title'   => $cat_title ];

    }
    
    // обработка отдельного элемента выгрузки
    private function import_item($item) {

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // подготовим данные
        if (!empty($item['id'])) $item_id = htmlspecialchars($item['id']);
        if (!empty($item->name)) $item_name = htmlspecialchars($item->name);
        if (!empty($item->description)) $item_description = htmlspecialchars($item->description);
        if (!empty($item->categoryId)) $item_categoryId = htmlspecialchars($item->categoryId);
        if (!empty($item->price)) $item_price = htmlspecialchars($item->price);
        $exist_images = '';

        // не обрабатываем товары с незаполненным id
        if (empty($item_id)) {   
            if (!empty($item_name)) return ['status' => 'error', 'error-message' => 'Не заполнен id' ,'title'   => $item_name ]; 
            else return ['status' => 'error', 'error-message' => 'Не заполнен id' , 'title'   => 'Название не указано' ];
        }

        // находим товар по id
        $updated_products = [];
        $args = array(
            'post_type' => 'product',
            'meta_key'   => 'id', 
            'meta_value' => $item_id, 
            'meta_compare' => '=', 
        );
        $products = get_posts( $args );
        if ( $products ) {
            foreach ( $products as $product ) {
                $updated_products[] = $product;
            }
        } 

        // не обрабатываем товары с id, дублирующимися на стороне сайта
        if (count($updated_products) > 1) return ['status' => 'error', 'error-message' => 'На сайте обнаружено дублирование id, товары не обновлены. id товара: '.$item_id ];
 
        // при обнаружении в выгрузке второго и последующих вхождений товаров с одинаковыми id, их не обрабатываем
        if ((count($updated_products) == 1) && (in_array($updated_products[0]->ID, $this->processed_products))) return ['status' => 'error', 'error-message' => 'В выгрузке обнаружено дублирование id, обновлено только первое вхождение товара. id товара: '.$item_id ];

        $post_data = [
            'post_type'    => 'product',
            'post_status'  => 'publish',
            'post_excerpt' => '',
        ];

        if (isset($item_name)) $post_data['post_title'] = $item_name;
        if (isset($item_description)) $post_data['post_content'] = $item_description;
        if (count($updated_products) == 1) $post_data['ID'] = $updated_products[0]->ID; // обновляем продукт, если он уже есть на сайте
        
        // Вставляем/обновляем запись
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) return ['status' => 'error', 'error-message'   => $post_id->get_error_message(), 'data'    => $post_data];
        write_to_log('Товар с id '.$item_id.' добавлен');
        
        $this->processed_products[] = $post_id;

        // Добавляем принадлежность к категориям
        if (!empty($item_categoryId)) {
            $term = array_search($item_categoryId, array_column($this->product_cat_terms, 'cat_id', 'term'));
            wp_set_object_terms( $post_id, [ (int)$term ], 'product_cat');
        }

        // Добавляем мета-поля
        $meta = ['price', 'sku', 'url', 'currencyId', 'store', 'delivery', 'categoryId'];
        foreach ($meta as $meta_item) {
            if (isset($item->{$meta_item})) {
                $meta_val = htmlspecialchars($item->{$meta_item});
                update_post_meta($post_id, $meta_item, $meta_val);
            }
        }
        if (isset($item_price)) update_post_meta($post_id, 'price', (string)round($item_price*1.1));
        update_post_meta($post_id, 'id', $item_id);

        // Добавляем изображения

        // если идёт обновдление существующего товара, то будем проверять наличиствующие изображения, чтобы не загружать дубликаты
        if (!empty($post_data['ID']) && isset($this->product_images[$post_data['ID']])) $exist_images = $this->product_images[$post_data['ID']];

        foreach ($item->picture as $picture) {
            $sanitized_url = filter_var($picture, FILTER_SANITIZE_URL);
            $sanitized_url_2 = htmlspecialchars(mb_convert_encoding($picture, 'UTF-8')); // альтернативная очистка, потому что FILTER_SANITIZE_URL удаляет кириллицу

            if (!empty($exist_images)) {
                $search_1 = array_search($sanitized_url, array_column($exist_images, 'custom_id'));
                $search_2 = array_search($sanitized_url_2, array_column($exist_images, 'custom_id'));
                if ($search_1 !== false || $search_2 !== false) continue;
            }

            $attachment_id = media_sideload_image( $sanitized_url, $post_id, 'Изображение товара', 'id' );
            if (( is_wp_error( $attachment_id ) ) && ($attachment_id->get_error_message() == 'Not Found')) {
                $sanitized_url = $sanitized_url_2;
                $attachment_id = media_sideload_image( $sanitized_url, $post_id, 'Изображение товара', 'id' );
            }
            if ( is_wp_error( $attachment_id ) ) return ['status' => 'error', 'error-message'   => $picture.' '.$attachment_id->get_error_message()];
            else {
                // запишем оригинальный url в качестве внешнего идентификатора изображения, чтобы при дальнейшем импорте проверять на существование
                $data = wp_get_attachment_metadata( $attachment_id );
                $data['image_meta']['id'] = $sanitized_url;
                wp_update_attachment_metadata( $attachment_id, $data );
            }
        } 
        
        return ['status' => 'success', 'post_id' => $post_id, 'title'   => $post_data['post_title'] ];
    }
    
}
