<?php


	
	
	// Отключение ненужных плагинов и стилей
	// Разместить wp-content/mu-plugins
    if ( ! function_exists('disable_plugins_when_wp_page') ) {
		

		add_filter( 'option_active_plugins', 'disable_plugins_when_wp_page' );		
		function disable_plugins_when_wp_page($active_plugins){
			
			// ничего не делаем - это админка, но не аякс запрос
			if( is_admin() && ! defined('DOING_AJAX') ) return $active_plugins;

			// ничего не делаем - аякс запрос из админки
			if( defined('DOING_AJAX') && strpos($_SERVER['HTTP_REFERER'], '/wp-admin/')  ) return $active_plugins;
			
			require (ABSPATH . WPINC . '/pluggable.php');

			$cur_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			
			if($cur_link == $_SERVER['HTTP_HOST']){
				$for_disable = array('woocommerce/woocommerce.php');
				foreach ($for_disable as $value){
					$key = array_search( $value , $active_plugins );
					if ( false !== $key ) {
						unset( $active_plugins[$key] );
					}
				}
			}				
			
			global $all_custom_css_dequeue;
			global $all_custom_script_dequeue;
			
			$all_custom_script_dequeue = array();
			$all_custom_css_dequeue = array('dashicons');


			return $active_plugins;
		}
		
		
		add_action('wp_enqueue_scripts','dequeue_style_a_scripts',100);
		function dequeue_style_a_scripts(){
		
			global $all_custom_css_dequeue;
			global $all_custom_script_dequeue;
		

			if($all_custom_css_dequeue){
				foreach ($all_custom_css_dequeue as $value){
					wp_dequeue_style($value);
					wp_deregister_style($value);
				}
			}
			
			if($all_custom_script_dequeue){
				foreach ($all_custom_script_dequeue as $value){
					wp_dequeue_script( $value );
					wp_deregister_script($value);
				}
			}

			
		}
		
		
		// Изменяем вывод скриптов с head в footer
		function footer_enqueue_scripts(){
			remove_action('wp_head','wp_print_scripts');
			remove_action('wp_head','wp_print_head_scripts',9);
			add_action('wp_footer','wp_print_scripts',5);
			add_action('wp_footer','wp_print_head_scripts',5);
		}
		add_action('wp_print_scripts','footer_enqueue_scripts', 10);
		
    }




	// Катировки валют
	/*
	$USD_EUR = file_get_contents("http://free.currencyconverterapi.com/api/v5/convert?q=USD_EUR&compact=y");
	$USD_RUB = file_get_contents("http://free.currencyconverterapi.com/api/v5/convert?q=USD_RUB&compact=y");
	// 3.1 Проверка взяты ли катировки.
	if($USD_EUR !== FALSE && $USD_RUB !== FALSE){
		$USD_EUR_decoder = json_decode($USD_EUR);
		$USD_RUB_decoder = json_decode($USD_RUB);
		$ch_USD_EUR = $USD_EUR_decoder->USD_EUR->val;
		$ch_USD_RUB = $USD_RUB_decoder->USD_RUB->val;
		// Если приведнные ниже значения равны 0 или отсутствуют тогда выходим из проги
		if(empty($ch_USD_EUR) && $ch_USD_EUR === false && $ch_USD_EUR == 0 && empty($ch_USD_RUB) && $ch_USD_RUB === false && $ch_USD_RUB == 0){
			return;
		}
	}else{
		return;
	}
	*/
	
	$xml = simplexml_load_file('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
	if ($xml){
		$arr_c = $xml->Cube->Cube;
		foreach( $arr_c->Cube as $attr){	
			if($attr->attributes()->currency == "USD"){
				$USD_EUR = $attr->attributes()->rate;
			}
			if($attr->attributes()->currency == "RUB"){
				$EUR_RUB = $attr->attributes()->rate;
			}
		}
		$ch_USD_EUR = (1/(floatval($USD_EUR)));
		$ch_USD_RUB = ($ch_USD_EUR * (floatval($EUR_RUB)));
		if(empty($ch_USD_EUR) && $ch_USD_EUR === false && $ch_USD_EUR == 0 && empty($ch_USD_RUB) && $ch_USD_RUB === false && $ch_USD_RUB == 0){
			return;
		}
	}else{
		return;
	}


	// Выключает обновление переводов у всего сайта!
	add_filter ('auto_update_translation', '__return_false');

	// Remove the additional information tab
	add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
	function woo_remove_product_tabs( $tabs ){unset( $tabs['additional_information'] );}


	// Ограничение длины пользовательских полей при регистрации
	function myplugin_check_fields_reg( $errors, $sanitized_user_login, $user_email ) {
		$arr_user_fild = array('Username' => $_POST['user_login']);
		$arr_user_fild_l = array('Username' => 20);
		foreach($arr_user_fild as $value_f => $value){
			if( isset($value) && (strlen($value) > $arr_user_fild_l[$value_f]) ){
				$errors->add('length_error', __('<strong>ERROR</strong>: You have exceeded the allowed field length limit.', 'bbpress') . ' (' . __($value_f, 'bbpress') . ')');
			}
		}
		return $errors;
	}
	add_filter( 'registration_errors', 'myplugin_check_fields_reg', 10, 3 );



	// Добавляет один файл jquery
	function my_jquery_scripts(){
		wp_deregister_script( 'jquery' );
		//wp_register_script( 'jquery', get_stylesheet_directory_uri() . '/assets/js/jquery.min.js');
		wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js');
		wp_enqueue_script( 'jquery' );
	}
	add_action( 'wp_enqueue_scripts', 'my_jquery_scripts' );



	// Убирает заголовок у страниц категорий продукта
	add_action('woodmart_after_header', 'show_page_title', 3);
	function show_page_title(){
		if ( is_product_category() ) {
			function del_show_page_title(){
				return false;
			}
			add_filter( 'woocommerce_show_page_title', 'del_show_page_title');
		}
	}



	// заменяет сниппет SEO, на схему 

	function remove_schema_woocommerce_product( $types ) {
		if ( ( $index = array_search( 'product', $types ) ) !== false ) {
			unset( $types[ $index ] );
		}
		return $types;
	}
	add_filter( 'woocommerce_structured_data_type_for_page', 'remove_schema_woocommerce_product' );

	add_filter( 'wpseo_json_ld_output', '__return_empty_array' );


	// добавляет атрибуты в каталог
	add_action( 'woocommerce_after_shop_loop_item_title', 'show_attributes', 20 );


	// для пустой цены выводить "цена по запросу"
	function my_price_replace($price, $_product) {
		if ($_product->get_price() == 0)  return __( 'Цена по запросу', '' );
		return $price;
	}
	add_filter( 'woocommerce_empty_price_html', 'my_price_replace', 1, 2 );




	// Удаляет надпись распродажа
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );



	// Сохраняет данные после регистрации
	function wooc_save_extra_register_fields( $customer_id ){
		
		if ( isset( $_POST['billing_first_name'] ) ) {
			update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
			update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
		}
		
	}
	add_action( 'woocommerce_created_customer', 'wooc_save_extra_register_fields' );





	// Добавляет комментарии к заказу
	add_action( 'woocommerce_checkout_update_order_meta', 'product_custom_order_notes', 100, 2 );
	function product_custom_order_notes( $order_id ){

		$order = wc_get_order( $order_id );


		if ( isset( $_POST['billing_comment_order'] ) && !empty( $_POST['billing_comment_order'] ) ) {
			$note = sanitize_text_field($_POST['billing_comment_order']);
			$order->add_order_note( $note );
		}
		
		if ( isset( $_POST['billing_company_curent'] ) && !empty( $_POST['billing_company_curent'] ) && isset( $_POST['billing_answer_type'] ) && $_POST['billing_answer_type'] == "company" ) {

			$cur_profile = $_POST['billing_company_curent'];
		
			$field_update_company = array( 
				"billing_company_name_" => 'company_name', 
				"billing_company_inn_" => 'company_inn', 
				"billing_company_adress_" => 'company_adress', 
				"billing_company_pay_account_" => 'company_pay_account', 
				"billing_company_cash_account_" => 'company_cash_account', 
				"billing_company_bik_" => 'company_bik' 
			);

			foreach ( $field_update_company as $f => $v) {
				if ( isset( $_POST[$f . $cur_profile] ) && !empty( $_POST[$f . $cur_profile] ) ) {
					$order->update_meta_data( $v, sanitize_text_field($_POST[$f . $cur_profile]) );			
				}
			}	
			

			
			if ( isset( $_POST['delivery_adress'] ) && !empty( $_POST['delivery_adress'] ) ) {
				$order->set_shipping_address_1( sanitize_text_field($_POST['delivery_adress']), 'shipping' );
			}else{
				if ( isset( $_POST['billing_company_adress_' . $cur_profile] ) && !empty( $_POST['billing_company_adress_' . $cur_profile] ) ) {
					$order->set_shipping_address_1( sanitize_text_field($_POST['billing_company_adress_' . $cur_profile]), 'shipping' );
				}
			}
			
			$arr_delivery_method = array( 
				"pickup" => 'Самовывоз', 
				"courier" => 'Курьер', 
				"mail" => 'Почта', 
				"tk" => 'ТК'
			);
			
			
			if ( isset( $_POST['delivery_method'] ) && !empty( $_POST['delivery_method'] ) ) {
				$order->set_shipping_address_2( sanitize_text_field( 'Доставка: ' .$arr_delivery_method[$_POST['delivery_method']]), 'shipping' );
			}

			
			
			/*
			if ( isset( $_POST['billing_company_delivery_adress_' . $cur_profile] ) && !empty( $_POST['billing_company_delivery_adress_' . $cur_profile] ) ) {
				
				$shipping_address = $order->get_address( 'shipping' );
				$shipping_address['address_1'] = sanitize_text_field($_POST['billing_company_delivery_adress_' . $cur_profile]);

				$order->set_address( $shipping_address, 'shipping' );

			}
			*/
			
		}
		
		$order->save();
		
	}


	// Показывает дополнительные поля
	add_action( 'woocommerce_admin_order_data_after_order_details', 'order_meta_general' );
	function order_meta_general( $order ){

		echo '<br class="clear" />';
		echo '<h4>Реквизиты компании <a href="#" class="edit_address">Edit</a></h4>';
			
		$company_name = get_post_meta( $order->id, 'company_name', true );
		$company_inn = get_post_meta( $order->id, 'company_inn', true );
		$company_adress = get_post_meta( $order->id, 'company_adress', true );
		$company_pay_account = get_post_meta( $order->id, 'company_pay_account', true );
		$company_cash_account = get_post_meta( $order->id, 'company_cash_account', true );
		$company_bik = get_post_meta( $order->id, 'company_bik', true );
				
		echo '<div class="address">';
				
		
		if(!empty($company_name)){
			echo '<p><strong>Название компании:</strong>' . $company_name . '</p>';
		}

		if(!empty($company_inn)){
			echo '<p><strong>ИНН:</strong>' . $company_inn . '</p>';
		}

		if(!empty($company_adress)){
			echo '<p><strong>Юридический адрес:</strong>' . $company_adress . '</p>';
		}

		if(!empty($company_pay_account)){
			echo '<p><strong>р/с:</strong>' . $company_pay_account . '</p>';
		}

		if(!empty($company_cash_account)){
			echo '<p><strong>к/с:</strong>' . $company_cash_account . '</p>';
		}

		if(!empty($company_bik)){
			echo '<p><strong>БИК:</strong>' . $company_bik . '</p>';
		}

				
		echo '</div>';
		echo '<div class="edit_address">';

			
		woocommerce_wp_text_input( array(
			'id' => 'company_name',
			'label' => 'Название компании:',
			'value' => $company_name,
			'wrapper_class' => 'form-field-wide'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'company_inn',
			'label' => 'ИНН:',
			'value' => $company_inn,
			'wrapper_class' => 'form-field-wide'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'company_adress',
			'label' => 'Юридический адрес:',
			'value' => $company_adress,
			'wrapper_class' => 'form-field-wide'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'company_pay_account',
			'label' => 'р/с:',
			'value' => $company_pay_account,
			'wrapper_class' => 'form-field-wide'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'company_cash_account',
			'label' => 'к/с:',
			'value' => $company_cash_account,
			'wrapper_class' => 'form-field-wide'
		) );
		woocommerce_wp_text_input( array(
			'id' => 'company_bik',
			'label' => 'БИК:',
			'value' => $company_bik,
			'wrapper_class' => 'form-field-wide'
		) );


		echo '</div>';
	 

	}

	// Обновляет дополнительные поля в панели администратора
	add_action( 'woocommerce_process_shop_order_meta', 'save_general_details' );
	function save_general_details( $ord_id ){
		update_post_meta( $ord_id, 'company_name', wc_clean( $_POST[ 'company_name' ] ) );
		update_post_meta( $ord_id, 'company_inn', wc_clean( $_POST[ 'company_inn' ] ) );
		update_post_meta( $ord_id, 'company_adress', wc_clean( $_POST[ 'company_adress' ] ) );
		update_post_meta( $ord_id, 'company_pay_account', wc_clean( $_POST[ 'company_pay_account' ] ) );
		update_post_meta( $ord_id, 'company_cash_account', wc_clean( $_POST[ 'company_cash_account' ] ) );
		update_post_meta( $ord_id, 'company_bik', wc_clean( $_POST[ 'company_bik' ] ) );
	}




add_filter( 'woocommerce_get_breadcrumb', 'bbloomer_single_product_edit_prod_name_breadcrumbs', 9999, 2 );

function bbloomer_single_product_edit_prod_name_breadcrumbs( $crumbs, $breadcrumb ) {
	if ( is_product() ) {
		global $product;
		//$index = count( $crumbs );
		//$brands = wp_get_object_terms( $product->get_id(), 'pwb-brand' );


		if( $index == 2 ){

			//$crumbs[4][0] = $crumbs[3][0];
			//$crumbs[4][1] = $crumbs[3][1];	

			// Получить по названию url 
			//$crumbs[3][0] = $brands[0]->name;
			//$crumbs[3][1] = '/brand/' . $brands[0]->slug;
			

			//$crumbs[1][0] = 'Каталог';
			//$crumbs[1][1] = get_permalink( 19822 );				
			
		}else{
			
			$crumbs[1][0] = 'Каталог';
			$crumbs[1][1] = get_permalink( 19822 );				
		}
		
		if(current_user_can('administrator')){
			$arr_cat_p = get_term_by( 'slug', $crumbs[2][0], 'product_cat' );
			
			if( $arr_cat_p !== false && $arr_cat_p->parent > 0){
				

				$crumbs[4][0] = $crumbs[3][0];
				$crumbs[4][1] = $crumbs[3][1];
				
				$crumbs[3][0] = $crumbs[2][0];
				$crumbs[3][1] = $crumbs[2][1];

				$term = get_term_by( 'id', $arr_cat_p->parent, 'product_cat' );			
				$crumbs[2][1] = get_category_link($term->term_id);
				$crumbs[2][0] = ($term->name);
				
			}
			
		}
		

		//if( !empty($brands[0]->name) ){
			//if( $index != 2 ) {
				


				//$crumbs[$index] = $crumbs[$index-1];
				// Получить по названию url 
				//$crumbs[$index-1][0] = $brands[0]->name;
				//$crumbs[$index-1][1] = '/brand/' . $brands[0]->slug;	
			
			//}
		//}
	}
	
	if ( is_product_category() ){		
		
		$crumbs[0][0] = 'Каталог';
		$crumbs[0][1] = get_permalink( 19822 );
		
		$crumb[0][0] = 'Главная';
		$crumb[0][1] = 'commsoft.ru';
		
		$crumbs = array_merge($crumb,$crumbs);
		
	}		
	
	if ( is_search() ){
		if(isset($crumbs[1])){
			unset($crumbs[1]);
			$crumbs[1][0] = 'Поиск';
		}
		if(isset($crumbs[2])){
			unset($crumbs[2]);
		}
	}
	


	return $crumbs;
}



	function my_custom_brand_sidebar(){
		register_sidebar(
			array (
				'name' => __( 'Brand Sidebar' ),
				'id' => 'brand-side-bar',
				'description' => __( 'Brand Sidebar' ),
				'before_widget' => '<div class="widget-content">',
				'after_widget' => "</div>",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			)
		);
		register_sidebar(
			array (
				'name' => __( 'Search Product Sidebar' ),
				'id' => 'search-product-side-bar',
				'description' => __( 'Search Product Sidebar' ),
				'before_widget' => '<div class="widget-content">',
				'after_widget' => "</div>",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			)
		);
	}
	add_action( 'widgets_init', 'my_custom_brand_sidebar' );



	function show_subcategories( $atts = array() ) {
		
		global $wp_query;
		if(is_product_category()){
			
			$cat_category = $wp_query->get_queried_object()->term_id;
		
			// Get subcategories of the current category
			$terms    = get_terms([
				'taxonomy'    => 'product_cat',
				'order'         => 'ASC',
				'hide_empty'  => true,
				'parent'      => $cat_category
			]);



			if($terms && ! is_wp_error($terms)){
					
				$output = '<div class="cat-ch-list"><div class="row">';
					
				// Loop through product subcategories WP_Term Objects
				foreach ( $terms as $term ) {
					$term_link = get_term_link( $term, 'product_cat' );			
					
					// get the thumbnail id using the queried category term_id
					$thumbnail_id = get_term_meta( $term ->term_id, 'thumbnail_id', true ); 
					
					if( $thumbnail_id ){
						// get the image URL
						$image = wp_get_attachment_url( $thumbnail_id ); 			
					}else{
						$image = wc_placeholder_img_src( $size = 'woocommerce_thumbnail' );
					}
					
					$output .= '<div class="block-c col-12 col-sm-6 col-lg-4">';
						$output .= '<p><a class="cat-ch-list-link" href="' . $term_link . '"><img class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" src="' . $image . '" alt="' . $term->name . '" width="450" height="350"></a></p>';
						$output .= '<h2><a class="cat-ch-list-title" href="' . $term_link . '">' . $term->name . '</a></h2>';
					$output .= '</div>';
						
						
				}
									
				$output .= '</div></div>';
				
			}else{
				return;
			}

		}else{
			return;
		}

		
		return $output;
	}

	function show_brands( $atts = array() ){
		include(get_stylesheet_directory() . '/brands/shortcodes/az-listing.php');
		return;
	}



	if(!(is_admin())){
		add_shortcode('show_subcategories_div', 'show_subcategories');
		add_shortcode('brand_listing', 'show_brands');
	}






?>
