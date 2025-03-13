<?php
require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");


define('JWT_SECRET_KEY', 'your-secret-key');

add_action('wp_login', 'generate_jwt_after_login', 10, 2);

add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/login', [
        'methods' => 'POST',
        'callback' => 'wpap_login_user',
        'permission_callback' => '__return_true',
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/popup/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_popup_by_id',
        'permission_callback' => 'validate_jwt_token', // Pastikan user terautentikasi
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/popup/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_popup',
        'permission_callback' => '__return_true', // Hanya admin yang bisa hapus
    ]);
});

function inject_jwt_localstorage_script()
{
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            console.log("🔹 Script dijalankan!");

            function getCookie(name) {
                let cookies = document.cookie.split("; ");
                for (let i = 0; i < cookies.length; i++) {
                    let parts = cookies[i].split("=");
                    if (parts[0] === name) {
                        return decodeURIComponent(parts[1]);
                    }
                }
                return null;
            }

            let jwtToken = getCookie("jwt_token");

            if (jwtToken) {
                localStorage.setItem("jwt_token", jwtToken);
                console.log("✅ JWT Token disimpan ke Local Storage:", jwtToken);
            } else {
                console.warn("❌ JWT Token tidak ditemukan di cookies!");
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'inject_jwt_localstorage_script');




function get_popup_by_id($request)
{
    $popup_id = (int) $request['id'];
    $popup = get_post($popup_id);

    if (!$popup || $popup->post_type !== 'popup') {
        return new WP_REST_Response(['message' => 'Popup tidak ditemukan'], 404);
    }

    return new WP_REST_Response(array(
        'id' => $popup->ID,
        'name' => get_the_title($popup->ID),
        'content' => apply_filters('the_content', $popup->post_content),
        'popup_type' => get_post_meta($popup->ID, 'popup_type', true),
        'targeted_pages' => json_decode(get_post_meta($popup->ID, 'targeted_pages', true), true),
    ), 200);
}

// Registrasi API untuk mendapatkan popup
add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/popup/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_popup_by_id',
        'permission_callback' => 'validate_jwt_token',
    ));
});

function wpap_login_user(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username']);
    $password = $params['password'];

    if (empty($username) || empty($password)) {
        return new WP_REST_Response(['message' => 'Username dan password wajib diisi'], 400);
    }

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response(['message' => 'Login gagal, periksa kembali username dan password'], 401);
    }

    $token = generate_jwt_token($user->ID);

    return new WP_REST_Response([
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'roles' => $user->roles
        ]
    ], 200);
}



add_action('rest_api_init', 'register_get_token_api');

function generate_jwt_after_login($user_login, $user)
{
    if (!$user || is_wp_error($user)) {
        return;
    }

    $token = generate_jwt_token($user->ID);

    setcookie("jwt_token", $token, time() + (60 * 60), "/", "", false, true);
    $_SESSION['jwt_token'] = $token;
}





function generate_jwt_token($user_id)
{
    $payload = [
        'user_id' => $user_id,
        'iat' => time(),
        'exp' => time() + (60 * 60)
    ];

    return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
}

function register_get_token_api()
{
    register_rest_route('artistudio/v1', '/get-token', [
        'methods' => 'GET',
        'callback' => 'get_jwt_token', // Pastikan fungsi ini ada
        'permission_callback' => '__return_true',
    ]);
}

add_action('rest_api_init', 'register_get_token_api');

// Fungsi untuk mendapatkan token dari cookie
function get_jwt_token()
{
    if (isset($_COOKIE['jwt_token'])) {
        return new WP_REST_Response(['token' => $_COOKIE['jwt_token']], 200);
    } else {
        return new WP_REST_Response(['error' => 'Token tidak ditemukan'], 401);
    }
}


add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/popup', [
        'methods' => 'GET',
        'callback' => 'get_popups',
        'permission_callback' => 'validate_jwt_token',
    ]);
});


function validate_jwt_token($request)
{
    $auth_header = $request->get_header('Authorization');
    $token = '';

    if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1]; // Token dari Header
    } elseif (isset($_COOKIE['jwt_token'])) {
        $token = $_COOKIE['jwt_token']; // Token dari Cookie
    }

    if (!$token) {
        return new WP_Error('jwt_missing', 'JWT Token tidak ditemukan di header atau cookies', ['status' => 401]);
    }

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
        return true;
    } catch (Exception $e) {
        return new WP_Error('jwt_invalid', 'JWT Token tidak valid: ' . $e->getMessage(), ['status' => 401]);
    }
}

function get_popups()
{
    $query = new WP_Query(array(
        'post_type' => 'popup',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));

    $popups = [];

    while ($query->have_posts()) {
        $query->the_post();

        $popup_id = get_the_ID();

        // Ambil nilai targeted_pages dari metadata
        $targeted_pages = get_post_meta($popup_id, 'targeted_pages', true);

        // Pastikan $targeted_pages berupa string sebelum di-decode
        if (is_string($targeted_pages)) {
            $targeted_pages = json_decode($targeted_pages, true);
        }

        // Jika setelah decode masih bukan array, set ke array kosong
        if (!is_array($targeted_pages)) {
            $targeted_pages = [];
        }

        $popups[] = array(
            'id' => $popup_id,
            'name' => get_the_title(),
            'content' => apply_filters('the_content', get_the_content()),
            'popup_type' => get_post_meta($popup_id, 'popup_type', true) ?: 'modal',
            'popup_status' => get_post_meta($popup_id, 'popup_status', true) ?: 'inactive',
            'targeted_pages' => $targeted_pages, // ✅ Dipastikan selalu array
        );
    }
    wp_reset_postdata();

    return rest_ensure_response($popups);
}

function validate_jwt_and_check_admin_role($request)
{
    $auth_header = $request->get_header('Authorization');

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        return new WP_Error('jwt_missing', 'JWT Token tidak ditemukan di header', ['status' => 401]);
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
        $user_id = $decoded->user_id;
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('invalid_user', 'User tidak ditemukan', ['status' => 401]);
        }

        error_log("User ID: $user_id, Roles: " . implode(', ', $user->roles));

        if (in_array('administrator', $user->roles)) {
            return true;
        }

        return new WP_Error('access_denied', 'Hanya admin yang bisa mengakses', ['status' => 403]);
    } catch (Exception $e) {
        return new WP_Error('jwt_invalid', 'JWT Token tidak valid: ' . $e->getMessage(), ['status' => 401]);
    }
}

function delete_popup($request)
{
    $popup_id = (int) $request['id'];
    $popup = get_post($popup_id);

    if (!$popup || $popup->post_type !== 'popup') {
        return new WP_REST_Response(['message' => 'Popup tidak ditemukan'], 404);
    }

    wp_delete_post($popup_id, true);
    return new WP_REST_Response(['message' => 'Popup berhasil dihapus'], 200);
}



function save_popup_to_cpt(WP_REST_Request $request)
{
    $params = $request->get_json_params();

    // Validasi input
    if (empty($params['popup_name']) || empty($params['popup_content'])) {
        return new WP_Error('invalid_data', 'Nama dan konten popup tidak boleh kosong.', array('status' => 400));
    }

    // Simpan popup sebagai post type "popup"
    $popup_id = wp_insert_post(array(
        'post_type' => 'popup',
        'post_title' => sanitize_text_field($params['popup_name']),
        'post_content' => wp_kses_post($params['popup_content']),
        'post_status' => 'publish',
    ));

    if (is_wp_error($popup_id)) {
        return new WP_Error('db_error', 'Gagal menyimpan popup ke database', array('status' => 500));
    }

    // Simpan metadata tambahan
    update_post_meta($popup_id, 'popup_type', sanitize_text_field($params['popup_type']));
    update_post_meta($popup_id, 'popup_status', sanitize_text_field($params['popup_status'] ?? 'inactive')); // ✅ Tambahkan status
    update_post_meta($popup_id, 'targeted_pages', !empty($params['targeted_pages']) ? $params['targeted_pages'] : array()); // ✅ Simpan sebagai array

    // Ambil data popup yang baru saja dibuat untuk dikembalikan sebagai response
    $popup_data = array(
        'id' => $popup_id,
        'name' => get_the_title($popup_id),
        'content' => get_post_field('post_content', $popup_id),
        'popup_type' => get_post_meta($popup_id, 'popup_type', true),
        'popup_status' => get_post_meta($popup_id, 'popup_status', true), // ✅ Tambahkan status ke response
        'targeted_pages' => get_post_meta($popup_id, 'targeted_pages', true), // ✅ Ambil data halaman
    );

    return rest_ensure_response(array(
        'message' => 'Popup berhasil disimpan',
        'popup' => $popup_data, // ✅ Kirim data lengkap
    ));
}

// Registrasi REST API untuk menyimpan popup
add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/popup', array(
        'methods' => 'POST',
        'callback' => 'save_popup_to_cpt',
        'permission_callback' => 'validate_jwt_and_check_admin_role',
    ));
});




function wp_popup_manager_register_api()
{
    register_rest_route('artistudio/v1', '/popup', array(
        'methods' => 'GET',
        'callback' => function () {
            $popups = get_posts(array(
                'post_type' => 'popup',
                'post_status' => 'publish',
                'numberposts' => -1
            ));

            return array_map(function ($popup) {
                return [
                    'id' => $popup->ID,
                    'name' => get_the_title($popup->ID),
                    'content' => apply_filters('the_content', $popup->post_content),
                    'status' => get_post_meta($popup->ID, 'popup_status', true),
                    'targeted_pages' => get_post_meta($popup->ID, 'popup_target_pages', true),
                ];
            }, $popups);
        }
    ));
}

add_action('rest_api_init', 'wp_popup_manager_register_api');



// API untuk mendapatkan JWT Token
function get_jwt_token1()
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $token = generate_jwt_token($user->ID);
        return rest_ensure_response(['token' => $token]);
    }
    return new WP_Error('no_access', 'User not logged in', ['status' => 401]);
}

add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/get-token', [
        'methods' => 'GET',
        'callback' => 'get_jwt_token',
        'permission_callback' => '__return_true'
    ]);
});

// API untuk mendapatkan daftar popup
function get_popup_data()
{
    $args = [
        'post_type' => 'popup',
        'posts_per_page' => -1
    ];
    $query = new WP_Query($args);
    $popups = [];

    while ($query->have_posts()) {
        $query->the_post();
        $popups[] = [
            'id' => get_the_ID(),
            'name' => get_the_title(),
            'content' => get_the_content(),
            'targeted_pages' => get_post_meta(get_the_ID(), 'targeted_pages', true)
        ];
    }

    return rest_ensure_response($popups);
}

add_action('rest_api_init', function () {
    register_rest_route('artistudio/v1', '/get-popups', [
        'methods' => 'GET',
        'callback' => 'get_popup_data',
        'permission_callback' => '__return_true'
    ]);
});


function register_popup_cpt()
{
    $args = array(
        'public' => true,
        'label' => 'Popups',
        'supports' => array('title', 'editor'),
        'show_in_rest' => true, // Pastikan bisa diakses melalui API
    );
    register_post_type('popup', $args);
}
add_action('init', 'register_popup_cpt');
