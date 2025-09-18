<?

/////////////////////////////////////////////////////
//  Пользовательские типы записей

add_action( 'init', 'user_post_types' );
function user_post_types() {
    register_post_type( 'product',
        array(
            'labels' => array(
                'name' => __( 'Товары' ),
            ),
            'description' => __('Описание'),
            'public' => true,
            'show_ui' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'menu_position' => 6,
            'hierarchical' => true,
            'query_var' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'excerpt'),
            'can_export' => true
        )
    );   
}

//  Пользовательские типы записей
/////////////////////////////////////////////////////

/////////////////////////////////////////////////////
//  Пользовательские свойства

add_action( 'add_meta_boxes', 'add_product_fields' );

function add_product_fields() {
    add_meta_box(
        'product_fields',
        'Дополнительные свойства',
        'product_fields_cont',
        'product',
        'normal',
        'high'
    );
    add_meta_box(
        'product_imgs',
        'Изображения',
        'product_imgs_cont',
        'product',
        'normal',
        'high'
    );
}

function product_fields_cont( $post ) {
  wp_nonce_field( plugin_basename( __FILE__ ), 'product_fields_content_nonce' );
  echo '<table>';
  echo '<tr><td><label for="price">Цена</label></td><td><input type="text" id="price" name="price" placeholder="" value="'.get_post_meta($post->ID, 'price', 1).'"></td></tr>';
  echo '<tr><td><label for="sku">Артикул</label></td><td><input type="text" id="sku" name="sku" placeholder="" value="'.get_post_meta($post->ID, 'sku', 1).'"></td></tr>';
  echo '<tr><td><label for="stock">Наличие</label></td><td><input type="text" id="stock" name="stock" placeholder="" value="'.get_post_meta($post->ID, 'stock', 1).'"></td></tr>';
  echo '<tr><td><label for="currencyId">Валюта</label></td><td><input type="text" id="currencyId" name="currencyId" placeholder="" value="'.get_post_meta($post->ID, 'currencyId', 1).'"></td></tr>';
  echo '<tr><td><label for="store">Хранение</label></td><td><input type="text" id="store" name="store" placeholder="" value="'.get_post_meta($post->ID, 'store', 1).'"></td></tr>';
  echo '<tr><td><label for="delivery">Доставка</label></td><td><input type="text" id="delivery" name="delivery" placeholder="" value="'.get_post_meta($post->ID, 'delivery', 1).'"></td></tr>';
  echo '</table>';
}

function product_imgs_cont( $post ) {
  wp_nonce_field( plugin_basename( __FILE__ ), 'product_fields_content_nonce' );
  echo '<div>';
  $images = get_attached_media( 'image' ); // Получить все изображения
    if ( $images ) {
        foreach ( $images as $image ) {
            $image_url = wp_get_attachment_image_url( $image->ID, 'thumbnail' );
            $image_url_full = wp_get_attachment_image_url( $image->ID, 'full' );
            if ( $image_url ) {
                echo '<a class="" target="_blank" href="'.$image_url_full.'"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image->post_title ) . '"></a>';
                //echo get_metadata( 'attachment', $image->ID, 'test', 1 );
                echo '<pre>';
                print_r(wp_get_attachment_metadata( $image->ID ));
                echo '</pre>';
            }
        }
    }
  echo '</div>';
}

add_action( 'save_post', 'product_fields_save' );

function product_fields_save( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
  return;
  if ( isset($_POST['product_fields_content_nonce']) && !wp_verify_nonce( $_POST['product_fields_content_nonce'], plugin_basename( __FILE__ ) ) )
  return;
  if ( isset($_POST['post_type']) && 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) )
    return;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) )
    return;
  }
  if ( isset($_POST['price'])) update_post_meta( $post_id, 'price', $_POST['price'] );
  if ( isset($_POST['sku'])) update_post_meta( $post_id, 'sku', $_POST['sku'] );
  if ( isset($_POST['stock'])) update_post_meta( $post_id, 'stock', $_POST['stock'] );
  if ( isset($_POST['currencyId'])) update_post_meta( $post_id, 'stock', $_POST['currencyId'] );
  if ( isset($_POST['store'])) update_post_meta( $post_id, 'store', $_POST['store'] );
  if ( isset($_POST['delivery'])) update_post_meta( $post_id, 'delivery', $_POST['delivery'] );
  
}


//  Пользовательские свойства
/////////////////////////////////////////////////////

/////////////////////////////////////////////////////
//  Пользовательские таксономии

add_action( 'init', 'user_taxonomies', 0 );

function user_taxonomies() {
    register_taxonomy(
        'product_cat',
        'product',
        array(
            'labels' => array(
                'name' => 'Категории',
                'add_new_item' => 'Добавить категорию',
                'new_item_name' => "Новая категория"
            ),
            'show_ui' => true,
            'show_tagcloud' => true,
            'hierarchical' => false
        )
    );
}

//  Пользовательские таксономии
/////////////////////////////////////////////////////

/////////////////////////////////////////////////////
//  Пользовательские свойства таксономий

add_action( 'product_cat_add_form_fields', 'product_cat_add_fields' ); 
function product_cat_add_fields( $taxonomy ) { ?> 
    <div class="form-field">
        <label for="id">ID</label>
        <input type="text" name="id" />
    </div>' 
<?php }

add_action( 'product_cat_edit_form_fields', 'product_cat_edit_fields', 10, 2 ); 
function product_cat_edit_fields( $term, $taxonomy ) { ?>
    <tr class="form-field">
        <th><label for="id">ID</label></th>
        <td><input name="id" type="text" value="<?=get_term_meta( $term->term_id, 'id', 1 ) ?>" /></td>
    </tr> 
<?php }

add_action( 'created_product_cat', 'product_cat_save_fields' );
add_action( 'edited_product_cat', 'product_cat_save_fields' ); 
function product_cat_save_fields( $term_id ) { 
    if( isset( $_POST[ 'id' ] ) ) update_term_meta( $term_id, 'id', sanitize_text_field( $_POST[ 'id' ] ) );
}

//  Пользовательские свойства таксономий
/////////////////////////////////////////////////////