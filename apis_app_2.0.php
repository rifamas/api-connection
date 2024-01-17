<?php
/*
Plugin Name: Apis conecction app new version 2.0
Description: Agrega nuevas API y funcionalidades a WordPress.
Version: 1.0
Author: Omar Gomez
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;




##################################### Membresias ###############################################
function get_variable_subscriptions($data_membership) {

    $author = $data_membership['author'] ?? '';

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'variable-subscription',
            ),
        ),
        'author'         => $author,
    );

    $products_query = new WP_Query($args);

    $products = array();

    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            $product_description = $product->get_description();
            $image_id = get_post_thumbnail_id($product->get_id());
            $_option_orteos = get_post_meta( $product_id, '_option_sorteos', true );
            $_papeletas_option_1 = get_post_meta($product_id, '_papeletas_option_1', true);
            $_papeletas_option_2 = get_post_meta($product_id, '_papeletas_option_2', true);
            $_papeletas_option_3 = get_post_meta($product_id, '_papeletas_option_3', true);
            $valores = array();
            if ($_papeletas_option_1) {
                $valores[] = $_papeletas_option_1.' €';
            }
            if ($_papeletas_option_2) {
                $valores[] = $_papeletas_option_2.' € ';
            }
            if ($_papeletas_option_3) {
                $valores[] = $_papeletas_option_3.' € ';
            }
            $valores_string = implode(' | ', $valores);

            // Verificar si hay una imagen destacada
            if ($image_id) {
                // Si hay una imagen destacada, obtener la URL de la imagen
                $image_url = wp_get_attachment_url($image_id);
            } else {
                // Si no hay imagen destacada, usar la imagen de placeholder de WooCommerce
                $image_url = wc_placeholder_img_src();
            }
            
            $author_id = get_post_field('post_author', $product_id);
            $author_data = get_userdata($author_id);
            $gallery_image_ids = $product->get_gallery_image_ids();
            $gallery_images = array_map(function ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                $image_data = wp_get_attachment_metadata($image_id);
                $image_info = array(
                    'id'              => $image_id,
                    'src'             => $image_url,
                );
                return $image_info;
            }, $gallery_image_ids);

            // Aquí puedes agregar más campos según tus necesidades
            $product_data = array(
                'id'    => $product_id,
                'name'  => $product->get_name(),
                'description'=> $product_description,
                'price' => $product->get_price(),
                'image' => $image_url,
                'author'=> $author_id,
                'author_name'=> $author_data->display_name,
                'sorteos' => $_option_orteos,
                'prices'  => $valores_string,
                'images'  => $gallery_images,
            );

            $products[] = $product_data;
        }
    }
    wp_reset_postdata();
    // Devolver los productos como un objeto JSON
    echo json_encode($products);
}

function get_author_membership_api_route() {
    register_rest_route('rifamas/v1', 'variable-subscription', array(
        'methods' => 'GET',
        'callback' => 'get_variable_subscriptions',
        'args' => array(
            'author' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
        ),
    ));
}
add_action('rest_api_init', 'get_author_membership_api_route');
#########################################################################################################





################################ Crear Productos ########################################################
function create_product_from_app(WP_REST_Request $request) {
    // Obtener los parámetros de la solicitud
    $name             = $request->get_param('name');
    $description      = $request->get_param('description');
    $shortDescription = $request->get_param('short_description');
    $type             = $request->get_param('type');
    $rifaType         = $request->get_param('_tipo_rifa_1');
    $rifaType2        = $request->get_param('_tipo_rifa_2');
    $price            = $request->get_param('precio');
    $images           = $request->get_param('images');
    $maxTickets       = $request->get_param('_max_tickets');
    $lotteryPrice     = $request->get_param('_lottery_price');
    $author           = $request->get_param('author');
    $pesoProducto     = $request->get_param('peso_producto');
    $weightProduct    = $request->get_param('_weight_product');
    $productState     = $request->get_param('_product_state');
    $category_number  = $request->get_param('category');

    // Aquí puedes procesar los datos y crear el producto en WordPress

    // Ejemplo de creación de un nuevo producto
    $product_id = wp_insert_post(array(
        'post_title'    => $name,
        'post_content'  => $description,
        'post_excerpt'  => $shortDescription,
        'post_type'     => 'product',
        'post_status'   => 'publish',
        'post_author'   => $author,
        // Agrega más campos según sea necesario
    ));

    // Devuelve una respuesta
    if ($product_id) {
        //set type product
        
        //set category
        wp_set_post_terms($product_id, $category_number, 'product_cat');


        if($type === 'lottery' || $type === 'rifa/venta' ){
            //Set type product
            wp_set_object_terms( $product_id , 'lottery', 'product_type');
            $publish_date = get_post_field( 'post_date', $product_id);

            //Dates lottery
            update_post_meta( $product_id , '_lottery_dates_from', $publish_date);
            $new_date = date('Y-m-d H:i:s', strtotime($publish_date . ' +1 month'));
            update_post_meta( $product_id , '_lottery_dates_to'  ,$new_date );

            update_post_meta( $product_id , '_tipo_rifa_1', $rifaType);
            update_post_meta( $product_id , '_max_tickets', $maxTickets);
            update_post_meta( $product_id , '_min_tickets', $maxTickets);
            
            //Lotery Price total rifa/venta
            update_post_meta( $product_id , '_lottery_price', $price);
        
            //price by tickets
            update_post_meta( $product_id , '_lottery_sale_price', $lotteryPrice );
            update_post_meta( $product_id , '_price', $lotteryPrice );
            update_post_meta( $product_id , '_sale_price', '' );


            update_post_meta( $product_id , '_lottery_started', 1 );
            update_post_meta( $product_id , '_lottery_use_pick_numbers','yes');
            update_post_meta( $product_id , '_lottery_num_winners', 1);
        }else{
            wp_set_object_terms($product_id , 'simple', 'product_type');
            //Set price
            update_post_meta( $product_id , '_price', $price );
        }
        
        
        update_post_meta( $product_id, 'author', $author);
        update_post_meta( $product_id, 'peso_producto', $pesoProducto);
        update_post_meta( $product_id, '_weight_product', $weightProduct);
        update_post_meta( $product_id, '_product_state', $productState);
        update_post_meta( $product_id, 'category', $category_number);


        //Test
        update_post_meta( $product_id, 'algodeimagen', $images);
        update_post_meta( $product_id, 'dato que obtiene rifaType2', $rifaType2);
        update_post_meta( $product_id, 'dato que obtiene type', $type );
        
        //by type
        update_post_meta( $product_id , 'product_type', $type );

        //Price product
        update_post_meta( $product_id , '_regular_price', $price );

        
        //by default
        update_post_meta( $product_id , '_stock_status', 'instock' );
        update_post_meta( $product_id , '_virtual', 'yes');

        return rest_ensure_response(['message' => 'Producto creado con éxito']);
    } else {
        return rest_ensure_response(['error' => 'Error al crear el producto'], 500);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('rifamas/v1', 'crear-producto', array(
        'methods' => 'POST',
        'callback' => 'create_product_from_app',
    ));
});



#########################################################################################################





#########################################################################################################

#########################################################################################################







##################################### Cargar imagenes #####################################
function handle_image_upload_callback( WP_REST_Request $request ) {
    if ('POST' !== $request->get_method()) {
        return rest_ensure_response(['error' => 'Método no permitido']);
    }

    $files = $request->get_file_params();
    if (empty($files['file'])) {
        return rest_ensure_response(['error' => 'No se ha proporcionado ningún archivo']);
    }

    // Procesar la imagen y obtener el ID del adjunto
    $attachment_id = handle_uploaded_file($files['file']);

    return rest_ensure_response(['attachment_id' => $attachment_id]);
}

function handle_uploaded_file( $file ) {
    // Obtener el ID del post al que deseas asociar la imagen (puedes cambiarlo según tus necesidades)
    $post_id = 1;

    // Manejar la subida del archivo y obtener el ID del adjunto
    $attachment_id = media_handle_upload($file, $post_id);

    return $attachment_id;
}

add_action('rest_api_init', function () {
    register_rest_route('rifamas/v1', '/upload-image', array(
        'methods'  => 'POST',
        'callback' => 'handle_image_upload_callback',
        'args'     => array(
            'file' => array(
                'required'          => true,
                'validate_callback' => 'is_wp_error',
                'sanitize_callback' => 'rest_sanitize_request_arg',
            ),
        ),
        'permission_callback' => function () {
            return current_user_can('upload_files');
        },
    ));
});

// Utiliza el hook rest_insert_attachment para manejar la lógica después de la subida de la imagen
function wpse_rest_insert_attachment( $attachment, $request, $creating ) {
    // Lógica después de la subida de la imagen
}
add_action( 'rest_insert_attachment', 'wpse_rest_insert_attachment', 10, 3 );
#########################################################################################################





################################### Crear membresía ########################################
function create_membership_subscriptions(WP_REST_Request $request) {
    //Llamar a la función create_product_variation con los datos necesarios
    $author = $request->get_param('author');
    // Validar que el autor existe en WordPress
    if (!$author) {
        return rest_ensure_response(['error' => 'El autor proporcionado no es válido']);
    }else{
        // Obtener los parámetros de la solicitud
        $variacion1 = $request->get_param('variacion-1');
        $variacion2 = $request->get_param('variacion-2');
        $variacion3 = $request->get_param('variacion-3');
        $papeletasVariacion1 = $request->get_param('papeletas-variacion-1');
        $papeletasVariacion2 = $request->get_param('papeletas-variacion-2');
        $papeletasVariacion3 = $request->get_param('papeletas-variacion-3');
        $descripcion = $request->get_param('descripcion');
        $nombreProducto = $request->get_param('nombre-producto');
        $logoUrl = $request->get_param('logo_url');
        $optionSorteos = $request->get_param('_option_sorteos');

        $product_data = array(
            'nombre-producto' => $nombreProducto,
            'descripcion' => $descripcion,
            'author' => $author,
            'opciones-sorteos' => $optionSorteos,
            'variacion-1' => $variacion1,
            'variacion-2' => $variacion2,
            'variacion-3' => $variacion3,
            'papeletas-variacion-1' => $papeletasVariacion1,
            'papeletas-variacion-2' => $papeletasVariacion2,
            'papeletas-variacion-3' => $papeletasVariacion3,
            'logo' => $logoUrl,
        );

            
        $productoId = procesar_creacion_membresia($product_data);


        // Obtener solo 'productoId' con la clave
        $productoIdSolo = isset($productoId['productoId']) ? ['productoId' => $productoId['productoId']] : null;

        echo json_encode($productoIdSolo);
        
    }

}
function create_membership_subscriptions_post() {
    register_rest_route('membresia/v1', 'crear', array(
        'methods' => 'POST',
        'callback' => 'create_membership_subscriptions',
        'args'     => array(
            'author' => array(
                'required' => true,
                    'validate_callback' => 'is_numeric', 'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param); 
                    }
                ),
            ),
        )
    );
}
add_action('rest_api_init', 'create_membership_subscriptions_post');
#########################################################################################################





################################# Crear regalos ########################################################
function crear_regalo_callback(WP_REST_Request $request) {
    // Asegúrate de que la solicitud sea una solicitud POST
    if ('POST' !== $request->get_method()) {
        return rest_ensure_response(['error' => 'Método no permitido']);
    }

    // Obtén los parámetros de la solicitud
    $imagen = $request->get_param('imagen');
    $description = $request->get_param('description');
    $product_ID = $request->get_param('product_ID');
    $name = $request->get_param('name');
    $date = $request->get_param('date');
    $author = $request->get_param('author');
    $type = 'lottery';
    $date_active_membership = date("Y-m-d", strtotime("01-" . date("m-Y", strtotime("+1 month")))) . " 21:00";
    $date2 = date("Y-m-d", strtotime("01-" . date("m-Y", strtotime("+1 month"))));

    $send_data_gift = array(
        'name' => $name,
        'description' => $description,
        'product_ID' => $product_ID,
        'date' => $date,
        'author' => $author,
    );
    
    $id_gift =  wp_insert_post(array(
        'post_title' => $name,
        'post_content' => $description,
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_author' => $author,
         'meta_input'    => array(
                '_lottery_dates_from'      => $date_active_membership,
                '_lottery_dates_to'        => $date2." 21:35",/////////////////////////////ver fecha/////////
                '_lottery_use_pick_numbers'=> 'yes',
                '_manage_stock'            => 'yes',
                '_max_tickets'             => '0',
                '_min_tickets'             => '0',
                '_stock'                   =>  0 ,
                '_stock_status'            => 'instock',
                '_virtual'                 => 'yes',
                'product_type'             => 'lottery',
                '_tipo_rifa_1'             => 'Cuponazo',
                '_lottery_num_winners'     => 1,
                'id_membership_data'       => $product_ID
            )
        // Agrega más campos según sea necesario
    ));

    // Devuelve una respuesta
    if ($id_gift) {
        wp_set_object_terms($id_gift , 'lottery', 'product_type');
        $last_number = 1;
        $existing_meta = get_post_meta($product_ID);
        $numbers = array();
        foreach ($existing_meta as $meta_key => $meta_value) {
            if (preg_match('/^id_product_gift_(\d+)$/', $meta_key, $matches)) {
                $numbers[] = intval($matches[1]);
                echo intval($matches[1]);
            }
        }
        // Obtener el número máximo
        $max_number = max($numbers);

        // Incrementar el número máximo en 1 para obtener el último número
        $last_number = $max_number + 1;
        
        // Construir el nuevo metadato con el número
        $new_meta = 'id_product_gift_' . $last_number;

        // Guardar el nuevo metadato del producto en la membresía principal
        add_post_meta($product_ID, $new_meta, $id_gift, false);
        $hide_product_membership = array( 'exclude-from-search', 'exclude-from-catalog' ); // for hidden..
        wp_set_post_terms( $id_gift, $hide_product_membership, 'product_visibility', false );
    }

    // Devuelve la respuesta
    return rest_ensure_response(['mensaje' => 'Regalo creado con éxito']);
}


add_action('rest_api_init', function () {
    register_rest_route('rifamas/v1', 'crear-regalo', array(
        'methods' => 'POST',
        'callback' => 'crear_regalo_callback',
        'permission_callback' => function () {
            // Aquí puedes agregar lógica para verificar permisos si es necesario
            return true;
        },
    ));
});
#########################################################################################################







################################# Login ##############################################

// Función para realizar la solicitud al endpoint JWT
function request_jwt_token($username, $password) {
    $jwt_endpoint_url  = home_url( '/wp-json/jwt-auth/v1/token');

    // Configurar los datos para la solicitud POST
    $post_data = array(
        'username' => $username,
        'password' => $password,
    );

    // Realizar la solicitud al endpoint JWT
    $response = wp_remote_post($jwt_endpoint_url, array(
        'body' => json_encode($post_data),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    // Verificar si la solicitud fue exitosa
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        // Decodificar la respuesta JSON
        $data = json_decode($response['body'], true);

        // Extraer el token de la respuesta
        $token = isset($data['token']) ? $data['token'] : '';

        return $token;
    }

    return null;
}

//Login y datos de usuarios
function custom_login_callback( $data ) {
    $username = $data['username'];
    $password = $data['password'];

    // Realiza la verificación de las credenciales del usuario
    $user = wp_authenticate( $username, $password );

    if ( is_wp_error( $user ) ) {
        // Las credenciales no son válidas
        return new WP_Error( 'authentication_failed', 'Credenciales incorrectas', array( 'status' => 403 ) );
    }

    $token = request_jwt_token($username, $password);
    $user_id = $user->ID;
    $user_email = $user->user_email;
    $user_name  = $user->display_name;

    // Devuelve la respuesta
    return array(
        'ID' => $user_id,
        'email'=> $user_email,
        'username'=> $user_name,
        '_jwtuser' => $token,
        'jwtData' => $token,
        'jwt_token' => $token,
    );
}

// Registra la ruta de la API
add_action( 'rest_api_init', function () {
    register_rest_route( 'api/v1', '/user_data', array(
        'methods'  => 'POST',
        'callback' => 'custom_login_callback',
    ) );
} );
###########################################################################################





################################ Campos personalizados products ###########################################
function custom_prepare_product_for_api($response, $producto, $request) {

    if (isset($request['orderby']) && $request['orderby'] === 'last_updated') {
        // Función de comparación para ordenar por fecha de actualización (modified)
        $compare_function = function ($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        };
        // Ordena $response->data directamente
        usort($response->data, $compare_function);
    }
    /*$response->data = array_filter($response->data, function ($productData) {
        return isset($productData['catalog_visibility']) && $productData['catalog_visibility'] !== 'hidden';
    });*/
    $product = wc_get_product($producto);
    $product_type = $product->get_type();
    $author_id = get_post_field('post_author', $product->get_id());
    $variation_interna = array();
    
    // Obtener el nombre del autor
    $author_name = get_the_author_meta('display_name', $author_id);
    
    $response->data['author'] = array(
        'name' => $author_name,
    );

    if ($product && $product->get_type() === 'variable-subscription') {
        $variations = $product->get_available_variations();
        foreach ($variations as $key => $variation) {
            $variation_interna[] = array(
                'variation_id' => $variation['variation_id'],
                'display_price' => get_post_meta($variation['variation_id'], '_price', true)
            );
        }
        $response->data['variation_id'] = $variation_interna;
    }

    if (empty($response->data['images']) || count($response->data['images']) === 0) {
        $response->data['images'] = array(
            array(
                'src' => wc_placeholder_img_src(),
                'alt' => '',
            ),);
    }

    /*$product_visibility = isset($response->data['catalog_visibility']) ? $response->data['catalog_visibility'] : '';
    if ($product_visibility === 'hidden') {
        continue;
    }*/

    if ($product_type === 'lottery' ) {
        $_lottery_participants = !empty($product->get_lottery_participants_count()) ? $product->get_lottery_participants_count() : 0;
        $max_tickets = $product->get_max_tickets()+1;
        $tickets = $product->get_lottery_participants_count();

        if ($max_tickets > 0) {
            $result_porcent = ($tickets / $max_tickets) * 100;
            $porcentaje_ventas = floor($result_porcent);
        } else {
            // Manejo de caso especial cuando el número máximo de boletos es cero o negativo
            $porcentaje_ventas = 0;
        }

        $response->data['porcent_products'] = $porcentaje_ventas;
        $response->data['participants'] = $_lottery_participants;
    }
    return $response;
}
// Registra el filtro para personalizar la respuesta de la API de productos
add_filter('woocommerce_rest_prepare_product_object', 'custom_prepare_product_for_api', 10, 3);
###########################################################################################





// Función de devolución de llamada para el endpoint personalizado
function get_products_by_params($data) {
    // Obtiene los parámetros de la solicitud
    $search = isset($data['search']) ? $data['search'] : '';
    $per_page = isset($data['per_page']) ? intval($data['per_page']) : 10;
    $page = isset($data['page']) ? intval($data['page']) : 1;
    $author = isset($data['author']) ? intval($data['author']) : 0;
    $type = isset($data['type']) ? $data['type'] : '';
    $category = isset($data['category']) ? $data['category'] : '';


    // Construye una consulta para obtener los productos de WooCommerce
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'author' => $author,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_visibility',
                'field' => 'slug',
                'terms' => array('exclude-from-catalog'),
                'operator' => 'NOT EXISTS',
            ),
        ),
        'orderby' => 'date',
        'order' => 'DESC',
    );

    // Añadir la condición de búsqueda si se proporciona
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Añadir las condiciones de tipo, categoría y visibilidad
    if (!empty($type)) {
        $args['meta_query'][] = array(
            'key' => 'product_type',
            'value' => $type,
        );
    }

    if (!empty($category)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $category,
        );
    }

    $products_query = new WP_Query($args);

    // Verifica si hay productos encontrados
    if ($products_query->have_posts()) {
        $products = array();

        // Construye el array de productos
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product = wc_get_product(get_the_ID());

            if ($product) {
                $images = array();
                $product_images = $product->get_gallery_image_ids();

                foreach ($product_images as $image_id) {
                    $image_data = wp_get_attachment_metadata($image_id);
                    $image_src  = wp_get_attachment_image_src($image_id, 'full');

                    $images[] = array(
                        'id'  => $image_id,
                        'src' => $image_src[0],
                        'name'=> pathinfo($image_data['file'], PATHINFO_FILENAME),
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                    );
                }

                $product_data = $product->get_data();
                $product_data['images'] = $images;

                $products[] = $product_data;
            }
        }

        // Restablece los datos del post
        wp_reset_postdata();

        // Devuelve la respuesta en formato JSON
        return rest_ensure_response($products);
    } else {
        // Si no se encuentran productos, devuelve un mensaje
        return rest_ensure_response(array('message' => 'No se encontraron productos.'));
    }
}



// Función de registro de endpoint
function register_custom_products_endpoint() {
    register_rest_route('rifamas/v1', '/get-products/', array(
        'methods' => 'GET',
        'callback' => 'get_products_by_params',
        'args' => array(
            'search',
            'per_page',
            'page',
            'author',
            'type',
            'category',
            'catalog_visibility',
        ),
    ));
}

// Registro del endpoint durante la inicialización de la API REST
add_action('rest_api_init', 'register_custom_products_endpoint');





#################################### Conteo de productos favoritos  ####################################################
function register_get_wish_api_route() {
    register_rest_route('count_favorite_product/v1', 'favorite_product', array(
        'methods' => 'GET',
        'callback' => 'countTotalHearts',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_get_wish_api_route');
function addOrUpdateWishlist($data){
    global $wpdb,$product;

    // Obtener el ID del producto y el ID del usuario desde los datos de la solicitud
    $product_id = $data['product_id'];
    $user_id = $data['user_id'];
    //Obtener el precio regular del producto
    $regular_price = get_post_meta($product_id, '_regular_price', true);

    // Verificar si ya existe un registro para el usuario y el producto
    $existing_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."yith_wcwl WHERE prod_id = %d AND user_id = %d", $product_id, $user_id));

    if (!$existing_record) {
        // Si no existe, agregar un nuevo registro
        $wpdb->insert(
            $wpdb->prefix . 'yith_wcwl',
            array(
                'prod_id' => $product_id,
                'quantity' => 1,
                'user_id' => $user_id,
                'wishlist_id' => 1,  
                'position' => 0,  
                'original_price' => $regular_price,  
                'original_currency' => 'EUR',
                'dateadded' => current_time('mysql'),
                'on_sale' => 0,
            ),
            array('%d', '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d')
        );
        
        // Puedes realizar cualquier acción adicional aquí si es necesario

        // Devolver el resultado
        return array('message' => 'Registro agregado correctamente.');
    } else {
        // Si ya existe, eliminar el registro existente
        $wpdb->delete(
            $wpdb->prefix . 'yith_wcwl',
            array(
                'prod_id' => $product_id,
                'user_id' => $user_id,
            ),
            array('%d', '%d')
        );

        // Puedes realizar cualquier acción adicional aquí si es necesario

        // Devolver el resultado
        return array('message' => 'Registro eliminado correctamente.');
    }
}
function register_post_wishlist_api_route() {
    register_rest_route('add_favorite_product/v1', 'add_or_update', array(
        'methods' => 'POST',
        'callback' => 'addOrUpdateWishlist',
        'args' => array(
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_post_wishlist_api_route');
#########################################################################################################





################################### Suma las visualizacion de los productos ##############################################
function send_product_view_callback( $request ) {
    // Obtén el ID del producto desde la solicitud
    $product_id = $request->get_param( 'product_id' );

    // Verifica si el producto existe
    /*if ( ! wc_get_product( $product_id ) ) {
        return new WP_Error( 'invalid_product', 'Producto no válido', array( 'status' => 400 ) );
    }*/
    $meta = get_post_meta($product_id, '_total_views_count', TRUE);
    $meta = ($meta) ? $meta + 1 : 1;
    update_post_meta($product_id, '_total_views_count', $meta);

    return new WP_REST_Response( array( 'message' => 'Metadata actualizada con éxito' ), 200 );
}


add_action( 'rest_api_init', function() {
    register_rest_route( 'rifamas/v1', '/send_product_view', array(
        'methods'  => 'POST',
        'callback' => 'send_product_view_callback',
        'args'     => array(
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ) );
} );
#########################################################################################################





########################### Obtener los datos de los productos favoritos ##################################################
function get_user_favorite_products($data) {
    global $wpdb;
    $user_id = $data['user_id'];

     $ids_products_favorite = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT prod_id FROM {$wpdb->prefix}yith_wcwl WHERE user_id = %d",
            $user_id
        ),
        ARRAY_A
    );

    $favorite_products = array();
     foreach ($ids_products_favorite as $product_data) {
        $product_id = $product_data['prod_id'];
        $product = wc_get_product($product_id);

        if ($product) {
            $images = array();
                $product_images = $product->get_gallery_image_ids();

                foreach ($product_images as $image_id) {
                    $image_data = wp_get_attachment_metadata($image_id);
                    $image_src  = wp_get_attachment_image_src($image_id, 'full');

                    $images[] = array(
                        'id'  => $image_id,
                        'src' => $image_src[0],
                        'name'=> pathinfo($image_data['file'], PATHINFO_FILENAME),
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                    );
                }

                $product_data = $product->get_data();
                $product_data['images'] = $images;

                $favorite_products[] = $product_data;
            }
        }
    wp_reset_postdata();
    return rest_ensure_response($favorite_products);
}

### ### ### ### ######  ### Endpoint ####### ### ### ### ### ### 
function register_favorite_product_endpoint() {
    register_rest_route('rifamas', '/products/favorites', array(
        'methods' => 'GET',
        'callback' => 'get_user_favorite_products',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_favorite_product_endpoint');
#########################################################################################################






#################################### Productos vendidos #################################################
function obtener_productos_vendidos_woocommerce($data) {
    global $wpdb;
    $user_id = $data['user_id'];

    $query = $wpdb->prepare(
        "SELECT
            order_items.order_item_name AS product_name,
            itemmeta.meta_value AS quantity,
            order_id_meta.meta_value AS order_id,
            order_date.post_date AS order_date
        FROM
            {$wpdb->prefix}woocommerce_order_items AS order_items
        INNER JOIN
            {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON order_items.order_item_id = itemmeta.order_item_id
        INNER JOIN
            {$wpdb->prefix}woocommerce_order_itemmeta AS order_id_meta ON order_items.order_item_id = order_id_meta.order_item_id
        INNER JOIN
            {$wpdb->prefix}posts AS order_date ON order_id_meta.meta_value = order_date.ID
        WHERE
            order_items.order_item_type = 'line_item'
            AND itemmeta.meta_key = '_qty'
            AND order_id_meta.meta_key = '_product_id'
            AND order_date.post_status IN ('wc-completed', 'wc-processing')
            AND order_date.post_author = %d
        ORDER BY
            order_date.post_date DESC",
        $user_id
    );

    $sold_products = $wpdb->get_results($query, ARRAY_A);

    return rest_ensure_response($sold_products);
}

function register_sold_products_endpoint() {
    register_rest_route('rifamas/v1', '/productos_vendidos/(?P<user_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'obtener_productos_vendidos_woocommerce',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_sold_products_endpoint');
#########################################################################################################





#################################### Obtener productos ############################################
function obtener_productos_usuario_endpoint($data){
    $user_id = $data['id'];
    $product_type = $data['product_type'];

    // Aquí puedes realizar la lógica que necesites para obtener los productos del usuario con el tipo especificado
    // Por ejemplo, puedes utilizar funciones de WordPress o consultas personalizadas

    // Ejemplo: Obtener productos del usuario con el tipo especificado
    $args = array(
        'author'      => $user_id,
        'post_type'   => 'product',
        'post_status' => 'publish',
        'meta_query'  => array(
            'relation'=> 'AND',
            array(
                'key'  => 'product_type',
                'value' => $product_type
            ),
            // Puedes agregar más condiciones según tus necesidades
        ),
    );

    $productos_usuario = get_posts($args);

    // Devolver la respuesta
    if ($productos_usuario) {
        return rest_ensure_response($productos_usuario);
    } else {
        return rest_ensure_response(array('message' => 'No se encontraron productos para este usuario y tipo.'));
    } 
}


function register_productos_usuario_endpoint() {
    register_rest_route('rifamas/v1', '/productos_usuario/', array(
        'methods' => 'GET',
        'callback' => 'obtener_productos_usuario_endpoint',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'product_type' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_productos_usuario_endpoint');
#########################################################################################################




#########################################################################################################
function obtener_meta_productos_endpoint($data) {
    // Obtener parámetro id_membership_data de la solicitud
    $id_membership_data = $data['id_membership_data'];

    $product_ids = array();
    $product_details = array();

    // Iterar sobre los nombres de los metadatos
    for ($i = 1; $i <= 4; $i++) {
        $meta_key = 'id_product_gift_' . $i;
        $product_id = get_post_meta($id_membership_data, $meta_key, true);

        // Verificar si se encontró un ID de producto
        if ($product_id) {
            $product_ids[] = $product_id;
        }
    }

    // Obtener información detallada de los productos a través de la API de WooCommerce
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        // Verificar si el producto existe
        if ($product) {

            $gallery_image_ids = $product->get_gallery_image_ids();

            $gallery_images = array_map(function ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                $image_data = wp_get_attachment_metadata($image_id);
                $image_info = array(
                    'id'              => $image_id,
                    'src'             => $image_url,
                    'alt'             => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                );
                return $image_info;
            }, $gallery_image_ids);

            // Agregar detalles del producto al array
            $product_details[] = array(
                'id'          => $product_id,
                'name'        => $product->get_name(),
                'description' => $product->get_description(),
                'price'       => $product->get_price(),
                'images'      => $gallery_images,
                // Agrega más detalles según sea necesario
            );
        }
    }
    // Enviar la respuesta JSON
    wp_send_json($product_details);  
}


function register_productos_meta_endpoint() {
    register_rest_route('rifamas/v1', '/products/meta/', array(
        'methods' => 'GET',
        'callback' => 'obtener_meta_productos_endpoint',
         'permission_callback' => '__return_true',
        'args' => array(
            'id_membership_data' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_productos_meta_endpoint');
#########################################################################################################

function add_to_cart_product_function($request) {
    $user_id      = $request->get_param('user_id');
    $token        = $request->get_param('token');
    $product_id   = $request->get_param('product_id');
    $variation_id = $request->get_param('variation_id');
    $tickets      = json_decode($request->get_param('tickets'));
    $price        = $request->get_param('price');

    // Validar el token JWT
    if (login_with_jwt_token($token)) {

        // Obtener el precio del producto si no se proporciona
        if ($price) {
            $price = get_post_meta($product_id, '_price', true);
        }

        $qty = $request->get_param('qty');
        if (!$qty) {
            $qty = 1;
        }

        // Incluir archivos de WooCommerce
        if (defined('WC_ABSPATH')) {
            require_once('wp-load.php');
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
            include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
        }

        // Inicializar la sesión de WooCommerce
        if ( null === WC()->session ) {
            $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
            WC()->session = new $session_class();
            WC()->session->init();
        }

        if ( null === WC()->customer ) {
            WC()->customer = new WC_Customer( $user_id, true );
        }

        if ( null === WC()->cart ) {
            WC()->cart = new WC_Cart();
            $cart  = WC()->cart->get_cart();
            $product_cart_id = WC()->cart->generate_cart_id($product_id);
            $cart_item_key = WC()->cart->find_product_in_cart($product_cart_id);

            
            if ($cart_item_key) {
                // Si el producto ya está en el carrito, actualiza la cantidad
                $cart_item = WC()->cart->get_cart_item($cart_item_key);
                $new_qty = $cart_item['quantity'] + $qty;
                WC()->cart->set_quantity($cart_item_key, $new_qty);

                // Producto actualizado exitosamente en el carrito
                return rest_ensure_response(array('succeeded' => true, 'message' =>  'Producto actualizado en el carrito'));
            } else {
                // Si el producto no está en el carrito, agrégalo
                $add_to_cart = WC()->cart->add_to_cart($product_id, $qty, $variation_id, array(), $price);

                if ($add_to_cart) {
                    // Producto agregado exitosamente al carrito
                    return rest_ensure_response(array('succeeded' => true, 'message' => 'Producto agregado al carrito'));
                } else {
                    // Error al agregar el producto al carrito
                    return rest_ensure_response(array('error' => 'Error al agregar el producto al carrito'));
                }
            }
        } else {
            // WooCommerce no está disponible
            return rest_ensure_response(array('error' => 'WooCommerce no está disponible'));
        }
    } else {
        // Error en la autenticación del token JWT
        return rest_ensure_response(array('error' => 'Hubo un error en la autenticación del token JWT'));
    }
}

function login_with_jwt_token($token) {
    // URL del endpoint de validación de JWT proporcionado por el plugin
    $validation_endpoint_url = 'https://staging.rifamas.es/wp-json/jwt-auth/v1/token/validate';

    // Configurar los datos para la solicitud POST
    $post_data = array(
        'token' => $token,
    );

    // Configurar las cabeceras, incluyendo la cabecera "Authorization"
    $headers = array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    );

    // Realizar la solicitud al endpoint de validación de JWT
    $response = wp_remote_post($validation_endpoint_url, array(
        'body'    => json_encode($post_data),
        'headers' => $headers,
    ));

    // Verificar si la solicitud fue exitosa
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        // La respuesta puede contener información adicional, como el usuario que inició sesión
        $data = json_decode($response['body'], true);

        return true; // Token válido
    }

    return false; // Token inválido
}

// Registrar el endpoint
function add_to_cart_product_endpoint() {
    register_rest_route('wp/v2', '/add_to_cart_product/', array(
        'methods' => 'POST',
        'callback' => 'add_to_cart_product_function',
        'permission_callback' => '__return_true',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);            
                } 
            ),
            'token' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param) && !empty($param);
                }
            ),
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'variation_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'tickets' => array(),
            'price' => array(
                'validate_callback' => function($param, $request, $key) {
                    return true;
                }
            ),
        ),
    ));
}
// Agregar acción para registrar el endpoint
add_action('rest_api_init', 'add_to_cart_product_endpoint');



// Función para obtener el carrito de un usuario
function obtener_carrito_usuario_endpoint($request) {
    $user_id = $request->get_param('user_id');
    $token = $request->get_param('token');

    // Validar el token JWT
    if (login_with_jwt_token($token)) {

        // Configuración de la solicitud cURL para obtener el carrito
        $url = home_url('/wp-json/wc/store/cart/');
        $headers = array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        );

        // Inicializar la sesión cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecutar la solicitud cURL y obtener la respuesta
        $response = curl_exec($ch);

        // Verificar si hubo algún error en la solicitud
        if (curl_errno($ch)) {
            return rest_ensure_response(array('error' => 'Error en la solicitud cURL: ' . curl_error($ch)));
        }

        // Cerrar la sesión cURL
        curl_close($ch);

        // Decodificar la respuesta JSON
        $cart_data = json_decode($response, true);

        if (isset($cart_data['items'])) {
            $cart_items = array();
            foreach ($cart_data['items'] as $item) {
                $price = isset($item['prices']['price']['amount']) ? $item['prices']['price']['amount'] : 0;
                $cart_items[] = array(
                    'id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'name' => $item['name'],
                    'price' => $price,
                    'image' => $item['images'][0]['src'],
                );
            }
        } else {
            return rest_ensure_response(array('error' => 'No se encontraron elementos en el carrito'));
        }

        $totals = isset($cart_data['totals']) ? $cart_data['totals'] : array();

        // Devolver información del carrito en la respuesta
        return rest_ensure_response(array('cart_items' => $cart_items, 'totals' => $totals));
    } else {
        // Error en la autenticación del token JWT
        return rest_ensure_response(array('error' => 'Hubo un error en la autenticación del token JWT'));
    }
}



/*
// Función para obtener el carrito de un usuario
function obtener_carrito_usuario_endpoint($request) {
    $user_id = $request->get_param('user_id');
    $token = $request->get_param('token');

    // Validar el token JWT
    if (login_with_jwt_token($token)) {

        // Configuración de la solicitud cURL para obtener el carrito
        $url = home_url('/wp-json/wc/store/cart/');
        $headers = array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        );

        // Inicializar la sesión cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecutar la solicitud cURL y obtener la respuesta
        $response = curl_exec($ch);

        // Verificar si hubo algún error en la solicitud
        if (curl_errno($ch)) {
            return rest_ensure_response(array('error' => 'Error en la solicitud cURL: ' . curl_error($ch)));
        }

        // Cerrar la sesión cURL
        curl_close($ch);

        // Decodificar la respuesta JSON
        $cart_data = json_decode($response, true);

        $cart_items = array();
        foreach ($cart_data['items'] as $item) {
            $price = isset($item['prices']['price']['amount']) ? $item['prices']['price']['amount'] : 0;
            $cart_items[] = array(
                'id' => $item['id'],
                'quantity' => $item['quantity'],
                'name' => $item['name'],
                'price' => $price,
                'image' => $item['images'][0]['src'],
            );
        }
        $totals = isset($cart_data['totals']) ? $cart_data['totals'] : array();

        // Devolver información del carrito en la respuesta
        return rest_ensure_response(array('cart_items' => $cart_data));
    } else {
        // Error en la autenticación del token JWT
        return rest_ensure_response(array('error' => 'Hubo un error en la autenticación del token JWT'));
    }
}*/




// Función para registrar el endpoint
function get_cart_user_endpoint_rifamas() {
    register_rest_route('wp/v2', '/get_cart_user_rifamas/', array(
        'methods' => 'GET',
        'callback' => 'obtener_carrito_usuario_endpoint',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);            
                } 
            ),
            'token' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param) && !empty($param);
                }
            ),
        ),
    ));
}

// Agregar acción para registrar el endpoint
add_action('rest_api_init', 'get_cart_user_endpoint_rifamas');




// Función para eliminar un producto del carrito
function eliminar_producto_carrito_endpoint($request) {
    $user_id = $request->get_param('user_id');
    $token = $request->get_param('token');
    $product_id = $request->get_param('product_id');

    // Validar el token JWT
    if (login_with_jwt_token($token)) {

        // Configuración de la solicitud cURL para eliminar el producto del carrito
        $url = home_url('/wp-json/wc/store/cart/remove-item');
        $headers = array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        );

        // Datos del producto a eliminar
        $data = array(
            'key' => $product_id, // Puedes necesitar ajustar esto dependiendo de cómo identifiques los productos en el carrito
        );

        // Inicializar la sesión cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Ejecutar la solicitud cURL y obtener la respuesta
        $response = curl_exec($ch);

        // Verificar si hubo algún error en la solicitud
        if (curl_errno($ch)) {
            return rest_ensure_response(array('error' => 'Error en la solicitud cURL: ' . curl_error($ch)));
        }

        // Cerrar la sesión cURL
        curl_close($ch);

        // Decodificar la respuesta JSON
        $result = json_decode($response, true);

        // Verificar si la eliminación fue exitosa
        if (isset($result['removed'])) {
            return rest_ensure_response(array('success' => true, 'message' => 'Producto eliminado del carrito'));
        } else {
            return rest_ensure_response(array('error' => 'Error al eliminar el producto del carrito'));
        }
    } else {
        // Error en la autenticación del token JWT
        return rest_ensure_response(array('error' => 'Hubo un error en la autenticación del token JWT'));
    }
}

// Función para registrar el nuevo endpoint
function register_remove_product_endpoint() {
    register_rest_route('wp/v2', '/remove_product_from_cart/', array(
        'methods' => 'DELETE',
        'callback' => 'eliminar_producto_carrito_endpoint',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);            
                } 
            ),
            'token' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param) && !empty($param);
                }
            ),
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}

// Agregar acción para registrar el nuevo endpoint
add_action('rest_api_init', 'register_remove_product_endpoint');




//add_action( 'template_redirect', add_to_cart_product_function($request) );

// Función para agregar productos al carrito
/*function add_to_cart_product_function($request) {
    $user_id = absint($request['user_id']);
    $product_id = absint($request['product_id']);

    if (empty($user_id) || empty($product_id)) {
        // Manejar error si los datos no son válidos
        return rest_ensure_response(array('error' => 'Datos de solicitud incorrectos'));
    }

    // Autenticar al usuario
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, false);
    update_user_caches($user_id);
    do_action('wp_login', true, $user_id);

    // Inicializar el carrito
    WC()->cart = new WC_Cart();

    // Agregar al carrito
    $added_to_cart = WC()->cart->add_to_cart($product_id, 1);

    if ($added_to_cart) {
        $checkout_url = wc_get_checkout_url();
        // Redirigir al usuario al checkout
        wp_safe_redirect($checkout_url);
    } else {
        // Manejar error si no se pudo agregar al carrito
        return rest_ensure_response(array('error' => 'No se pudo agregar el producto al carrito'));
    }
}

// Registrar el endpoint
function add_to_cart_product_endpoint() {
    register_rest_route('wp/v2', '/add_to_cart_product/', array(
        'methods' => 'POST',
        'callback' => 'add_to_cart_product_function',
    ));
}*/


// Agregar acción para registrar el endpoint
//add_action('rest_api_init', 'add_to_cart_product_endpoint');
#############################################################################################################
/*// Función para agregar productos al carrito
function add_to_cart_product_function($request) {
    $user_id = $request->get_param('user_id');
    $product_id = $request->get_param('product_id');
    $variation_id = $request->get_param('variation_id');
    $tickets = json_decode($request->get_param('tickets'));
    $price = $request->get_param('price');

    if (empty($user_id) || empty($product_id) || empty($tickets) || empty($price)) {
        return rest_ensure_response(array('error' => 'Faltan parámetros requeridos'));
    }

    // Obtener el token del usuario
    //$token = get_user_token($user_id);

    // Verificar si se obtuvo el token correctamente
    if (!$token) {
        return rest_ensure_response(array('error' => 'Error al obtener el token de acceso'));
    }

    // Configurar el token en las cabeceras
    $headers = array(
        'Authorization' => 'Bearer ' . $token,
    );

    // Lógica para agregar productos al carrito
    foreach ($tickets as $ticket) {
        WC()->cart->add_to_cart($product_id, 1, $variation_id, $ticket, array('price' => $price));
    }

    // Obtener la URL del checkout
    $checkout_url = wc_get_checkout_url();

    // Redirigir al usuario al checkout
    wp_safe_redirect($checkout_url);
    exit; // Importante: asegúrate de salir después de redirigir para evitar problemas
}

// Registrar el endpoint
function add_to_cart_product_endpoint() {
    register_rest_route('wp/v2', '/add_to_cart_product/', array(
        'methods' => 'POST',
        'callback' => 'add_to_cart_product_function',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'variation_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'tickets' => array(),
            'price' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}

// Agregar acción para registrar el endpoint
add_action('rest_api_init', 'add_to_cart_product_endpoint');*/



// En functions.php o en tu plugin personalizado

// Callback para el endpoint /autologin
/*function autologin_callback(WP_REST_Request $request) {
    // Obtén el user_id del parámetro
    $user_id = $request->get_param('user_id');

    // Comprueba si el user_id es válido y existe en WordPress
    if ($user_id && get_user_by('ID', $user_id)) {
        // Loguea al usuario automáticamente
        //clean_user_cache( $user_id );
        wp_clear_auth_cookie();
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true, false );
        update_user_caches( $user_id );
        do_action('wp_login', true  , $user_id);

        // Redirige al usuario a la página deseada (puedes personalizar esto)
        wp_redirect(home_url('/mi-cuenta')); // Cambia '/mi-cuenta' por la URL que desees

        // Finaliza la ejecución para evitar respuestas adicionales
        exit;
    } else {
        // El user_id no es válido, maneja el error según tus necesidades
        return new WP_Error('invalid_user_id', 'El user_id proporcionado no es válido.');
    }
}

// Registra el endpoint /autologin
function register_autologin_endpoint() {
    register_rest_route('rifamas/v1', '/autologin/', array(
        'methods'  => 'GET',
        'callback' => 'autologin_callback',
        'args'     => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_autologin_endpoint');
*/


#############################################################################################################


function consultar_disponibilidad_tickets($data) {
    
    // Obtener parámetros de la solicitud
    $producto_id = isset($data['product_id']) ? $data['product_id'] : 0;
    //$woocommerce_ck = isset($data['woocommerce_ck']) ? $data['woocommerce_ck'] : '';
    //$woocommerce_cs = isset($data['woocommerce_cs']) ? $data['woocommerce_cs'] : '';


    // Verificar si la función wc_lotery_get_available_ticket existe
    if (!function_exists('wc_lotery_get_available_ticket')) {
        return rest_ensure_response(array('error' => 'La función wc_lotery_get_available_ticket no está disponible.'));
    }

    // Obtener la disponibilidad de tickets utilizando la función específica
    $taken_numbers = wc_lottery_pn_get_taken_numbers( $producto_id );
    $reserved_numbers = wc_lottery_pn_get_reserved_numbers( $producto_id );
    $max_tickets   = intval( get_post_meta( $producto_id, '_max_tickets', true ) );
    $tickets = range(0, $max_tickets);
    $disponibilidad_tickets = array_diff ($tickets,$taken_numbers, $reserved_numbers);
    //$disponibilidad_tickets = wc_lotery_get_available_ticket($producto_id);

    // Verificar si se obtuvo la disponibilidad
    if ($disponibilidad_tickets !== false) {
        //return rest_ensure_response(array_values('disponibilidad_tickets' => $disponibilidad_tickets));
        return rest_ensure_response(array_values($disponibilidad_tickets));
    } else {
        return rest_ensure_response(array('error' => 'No se pudo obtener la disponibilidad de tickets.'));
    }
}

function validar_credenciales_woocommerce($ck, $cs) {
    // Obtener todas las cabeceras de la solicitud
    $headers = getallheaders();

    // Validar las credenciales de la API de WooCommerce
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        $valid = wc_api_check_callback_params($auth_header, $ck, $cs);
        return $valid;
    }

    return false;
}

add_action('woocommerce_loaded', 'consultar_disponibilidad_tickets');
function register_disponibilidad_tickets_endpoint() {
    register_rest_route('rifamas/v1', '/tickets/disponibilidad/', array(
        'methods' => 'GET',
        'callback' => 'consultar_disponibilidad_tickets',
        'permission_callback' => '__return_true',
        'args' => array(
            'product_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'woocommerce_ck' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'woocommerce_cs' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_disponibilidad_tickets_endpoint');
#############################################################################################################





#############################################################################################################

add_action('rest_api_init', function () {
    register_rest_route('rifamas/v1', '/papeletas_usuario/', array(
        'methods' => 'GET',
        'callback' => 'get_ticket_numbers_buyed',
        'args' => array(
            'user_id' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'product_id' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
});

function get_ticket_numbers_buyed($data) {
    $userid = $data['user_id'];
    $product_id = $data['product_id'];

    // Obtener el objeto de producto
    $product = wc_get_product($product_id);

    if (!$product) {
        // Si el producto no existe, puedes manejarlo aquí
        $response = array('status' => 'error', 'message' => 'Producto no válido');
        return rest_ensure_response($response);
    }

    $lottery_history = $product->lottery_history();

    if ($lottery_history) {
        // Array para almacenar solo los números de las papeletas compradas por el usuario
        $numeros_comprados = array();

        foreach ($lottery_history as $history_value) {
            if ($history_value->userid == $userid) {
                // Agregar el número de la papeleta al array
                $numeros_comprados[] = apply_filters('ticket_number_display_html', $history_value->ticket_number, $product);
            }
        }

        // Verificar si se compraron papeletas
        if (!empty($numeros_comprados)) {
            // Devolver solo los números de las papeletas compradas como respuesta JSON
            return rest_ensure_response($numeros_comprados);
        } else {
            // Si no se encontraron papeletas compradas para el usuario actual
            $response = array('status' => 'success', 'message' => 'El usuario no ha comprado papeletas para este producto');
            return rest_ensure_response($response);
        }
    }

    // Si no hay historial de lotería para el usuario actual
    $response = array('status' => 'success', 'message' => 'Papeleta comprada con éxito');
    return rest_ensure_response($response);
}




##################################### Agregar al carrito ###################################################
/*function add_to_cart_product_callback( WP_REST_Request $request ) {
    $to_email = 'omargomez.mza@gmail.com';
    $subject  = 'Comprar.';
    $email_content = 'como comprar';
    $headers   = 'MIME-Version: 1.0' . "\r\n";
    $headers  .= 'Content-type: text/html; charset='.get_bloginfo('charset').'' . "\r\n";
    $headers  .= 'From: Probar <rifamas@rifamas.es>' . "\r\n";
    wp_mail( $to_email, $subject, $email_content, $headers );
    if ( ! function_exists( 'WooCommerce' ) ) {
        include_once ABSPATH .'/wp-content/plugins/woocommerce/woocommerce.php';
    }
    
    // Verifica si la clase WC_Cart existe
    global $woocommerce;
    //$woocommerce->cart->empty_cart();


    $user_id = $request->get_param( 'user_id' );
    $product_id = $request->get_param( 'product_id' );
    $variation_id = $request->get_param( 'variation_id' );
    $tickets = $request->get_param( 'tickets' );

    $price = $request->get_param( 'price' ); /*get_post_meta( $product_id, '_lottery_sale_price', true );*/

    // Añadir el nuevo producto al carrito
    //WC()->cart->add_to_cart( $product_id, 1, $variation_id, array(), array( 'tickets' => $tickets, 'price' => $price ) );
    //$prod_unique_id = $woocommerce->cart->generate_cart_id( $product_id );
    // Remove it from the cart by un-setting it
    //unset( $woocommerce->cart->cart_contents[$product_id] );

    /*WC()->cart->add_to_cart( 
        $user_id,
        $product_id, 
        1,
        $variation_id,
        array(),
        array( 
            'tickets' => $tickets,
        ),$price  
    );
    // Obtener la URL del checkout
    $checkout_url = wc_get_checkout_url();

    // Redirigir al usuario al checkout
    return rest_ensure_response( array( 'redirect_url' => $checkout_url ) );
}

/*add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/add_to_cart_product', array(
        'methods'  => 'POST',
        'callback' => 'add_to_cart_product_callback',
        'args'     => array(
            'user_id'      => array(
                'required'          => true,
                'validate_callback' => 'is_numeric', 'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); 
                }
            ),
            'product_id'   => array(
                'required'          => true,
                'validate_callback' => 'is_numeric', 'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); 
                }
            ),
            'variation_id' => array(
                'required'          => false,
                'validate_callback' => 'is_numeric', 'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); 
                }
            ),
            'tickets'      => array(
                'required'          => false,
            ),
            'price'        => array(
                'required'          => false,
                'validate_callback' => 'is_numeric', 'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); 
                }
            ),
        ),
    ) );
} );*/
// Registrar el endpoint
/*add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/add_to_cart_product', array(
        'methods'  => 'POST',
        'callback' => 'add_to_cart_product_callback',
        'args'     => array(
            'user_id'      => array(
                'required'          => true,
                'validate_callback' => function ( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'product_id'   => array(
                'required'          => true,
                'validate_callback' => function ( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'variation_id' => array(
                'required'          => false,
                'validate_callback' => function ( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'tickets'      => array(
                'required'          => false,
            ),
            'price'        => array(
                'required'          => false,
                'validate_callback' => function ( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
} );*/
#########################################################################################################