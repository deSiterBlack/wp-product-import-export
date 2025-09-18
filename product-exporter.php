<?php

class ProductExporter {
    
    public function __construct() {

    }

    // экспорт товаров
    public function generate_yml() {
 
        $content = '';

        $content .= '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
        $content .= '<yml_catalog date="' . date('Y-m-d H:i') . '">' . "\r\n";
        $content .= '<shop>' . "\r\n";       
        $content .= '<name>Сова-Нянька.рф</name>' . "\r\n";
        $content .= '<url>https://nannyowl.ru</url>' . "\r\n";
        $content .= '<currencies>' . "\r\n";
        $content .= '<currency id="RUB" rate="1"/>' . "\r\n";
        $content .= '</currencies>' . "\r\n";

        // Добавляем категории
        write_to_log('Добавляем в экспорт категории');
        $args = array(
            'post_types' => 'product',
            'taxonomy' => 'product_cat',
            'hide_empty' => false, 
        );
        $terms = get_terms( $args );
        if ( $terms ) {
            $content .= '<categories>' . "\r\n";
            foreach ($terms as $term) {
                $content .= '<category id="' . $term->term_id . '">' . $term->name . '</category>' . "\r\n";
            }
            $content .= '</categories>' . "\r\n";
        }
         
        // Добавляем товары
        write_to_log('Добавляем в экспорт товары');
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
        );
        $products = get_posts( $args );        
        if ( $products ) {
            $content .= '<offers>' . "\r\n";
            foreach ( $products as $product ) {
                $content .= '<offer id="' . $product->ID . '">' . "\r\n";             
                $content .= '<name>'.$product->post_title.'</name>' . "\r\n";                         
                $content .= '<url>'. get_permalink($product->ID).'</url>' ."\r\n";
                $content .= '<price>'. get_post_meta( $product->ID, 'price', true ) .'</price>' . "\r\n";
                $content .= '<currencyId>'. get_post_meta( $product->ID, 'currencyId', true ) .'</currencyId>' . "\r\n";
                $content .= '<vendor>Сова-Нянька</vendor>' . "\r\n";
                $categories = get_the_category($product->ID); 
                if ( ! empty( $categories ) ) {
                    foreach ( $categories as $category ) {
                        $content .= '<categoryId>' . $category->term_id . '</categoryId>' . "\r\n";
                    }
                }
                $content .= '<description><![CDATA[' . stripslashes($product->post_content) . ']]></description>' . "\r\n";    // Описание товара, максимум 3000 символов
                $content .= '</offer>' . "\r\n";
            }
            $content .= '</offers>' . "\r\n";
        } 
        $content .= '</shop>' . "\r\n";
        $content .= '</yml_catalog>' . "\r\n";

        $file_result = file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/yml-export.xml', $content);
        if (!$file_result) return ['status' => 'error', 'error-message' => 'Запись файла не удалась' ];

        return ['status' => 'success'];

    }

}