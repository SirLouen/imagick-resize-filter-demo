<?php
/**
 * Plugin Name: Imagick Resize Filter Demo
 * Description: Demonstrates the use of WP_Image_Editor_Imagick->thumbnail_image() with specific filters (FILTER_TRIANGLE and FILTER_LANCZOS).
 * Version: 1.0.0
 * Author: SirLouen <sir.louen@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'irfd_admin_menu' );

function irfd_admin_menu() {
    add_menu_page(
        'Imagick Resize Demo',
        'Imagick Resize',
        'manage_options',
        'imagick-resize-filter-demo',
        'irfd_admin_page_callback',
        'dashicons-format-image',
        80
    );
}

function irfd_admin_page_callback() {
    ?>
    <div class="wrap" id="irfd-admin-page">
        <h1>Imagick Resize Filter Demo</h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'irfd_resize_image_action', 'irfd_resize_image_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="image_id">Image ID</label></th>
                    <td>
                        <input type="number" id="image_id" name="image_id" value="<?php echo esc_attr( isset( $_POST['image_id'] ) ); ?>" required />
                        <p class="description">Enter the ID of an image from your Media Library.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Resize Image' ); ?>
        </form>

        <?php
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'irfd_resize_image_action', 'irfd_resize_image_nonce' ) ) {
            irfd_handle_resize_submission();
        }
        ?>
    </div>
    <?php
}

function irfd_add_filter_then_resize() {
    return 'FILTER_HAMMING';
}

function irfd_handle_resize_submission() {
    $image_id      = isset( $_POST['image_id'] ) ? intval( $_POST['image_id'] ) : 0;
    $target_width  = 500;
    $target_height = 0;
    $image_path    = get_attached_file( $image_id );

    $editor        = wp_get_image_editor( $image_path, array( 'editor' => 'WP_Image_Editor_Imagick' ) );
    $filter_triangle = 'FILTER_TRIANGLE';
    $editor->thumbnail_image( $target_width, $target_height, $filter_triangle );

    $path_parts_triangle = pathinfo( $image_path );
    $new_filename_triangle = $path_parts_triangle['filename'] . '-triangle-resized-' . $target_width . 'x' . $target_height . '.' . $path_parts_triangle['extension'];
    $upload_dir_triangle = wp_upload_dir();
    $new_image_path_triangle = $upload_dir_triangle['path'] . '/' . $new_filename_triangle;
    $save_result_triangle = $editor->save( $new_image_path_triangle );

    add_filter( 'imagick_resize_filter', 'irfd_add_filter_then_resize');
    $editor->resize( $target_width, $target_height, false );
    remove_filter( 'imagick_resize_filter', 'irfd_add_filter_then_resize' );

    $path_parts_hamming = pathinfo( $image_path );
    $new_filename_hamming = $path_parts_hamming['filename'] . '-hamming-resized-' . $target_width . 'x' . $target_height . '.' . $path_parts_hamming['extension'];
    $upload_dir_hamming = wp_upload_dir();
    $new_image_path_hamming = $upload_dir_hamming['path'] . '/' . $new_filename_hamming;
    $save_result_hamming = $editor->save( $new_image_path_hamming );

    $editor_lanczos = wp_get_image_editor( $image_path, array( 'editor' => 'WP_Image_Editor_Imagick' ) );
    $editor_lanczos->thumbnail_image( $target_width, $target_height );

    $path_parts_lanczos = pathinfo( $image_path );
    $new_filename_lanczos = $path_parts_lanczos['filename'] . '-lanczos-resized-' . $target_width . 'x' . $target_height . '.' . $path_parts_lanczos['extension'];
    $upload_dir_lanczos = wp_upload_dir();
    $new_image_path_lanczos = $upload_dir_lanczos['path'] . '/' . $new_filename_lanczos;
    $save_result_lanczos = $editor_lanczos->save( $new_image_path_lanczos );

    $original_url = wp_get_attachment_image_url( $image_id, 'full' );

    $relative_new_path_triangle = str_replace( $upload_dir_triangle['basedir'], '', $save_result_triangle['path'] );
    $new_image_url_triangle = $upload_dir_triangle['baseurl'] . $relative_new_path_triangle;
    $filesize_triangle = filesize( $new_image_path_triangle );

    $relative_new_path_hamming = str_replace( $upload_dir_hamming['basedir'], '', $save_result_hamming['path'] );
    $new_image_url_hamming = $upload_dir_hamming['baseurl'] . $relative_new_path_hamming;
    $filesize_hamming = filesize( $new_image_path_hamming );

    $relative_new_path_lanczos = str_replace( $upload_dir_lanczos['basedir'], '', $save_result_lanczos['path'] );
    $new_image_url_lanczos = $upload_dir_lanczos['baseurl'] . $relative_new_path_lanczos;
    $filesize_lanczos = filesize( $new_image_path_lanczos );

    echo '<div class="notice notice-success"><p>Images resized successfully!</p></div>';
    echo '<h3 style="text-align: center;">Results:</h3>';
    
    echo '<div class="result-images" style="text-align: center;">';
    echo '  <div style="margin-bottom: 20px;"><h4>Original Image</h4><img src="' . $original_url . '" alt="Original Image" style="max-width: 1000px; height: auto; display: block; margin-left: auto; margin-right: auto;"></div>';
    echo '  <div style="display: flex; justify-content: center;">';
    echo '    <div style="text-align: center; margin-right: 0;"><h4>Resized Image w/ Function (FILTER_TRIANGLE)</h4><img src="' . $new_image_url_triangle . '?t='.time().'" alt="Resized Image (Triangle)" style="height: auto; display: block;"><p>Filesize: ' . $filesize_triangle . ' bytes</p></div>';
    echo '    <div style="text-align: center; margin-right: 0;"><h4>Resized Image w/ Filter Hook (FILTER_HAMMING)</h4><img src="' . $new_image_url_hamming . '?t='.time().'" alt="Resized Image (Hamming)" style="height: auto; display: block;"><p>Filesize: ' . $filesize_hamming . ' bytes</p></div>';
    echo '    <div style="text-align: center; margin-left: 0;"><h4>Resized Image Default (FILTER_LANCZOS)</h4><img src="' . $new_image_url_lanczos . '?t='.time().'" alt="Resized Image (Lanczos)" style="height: auto; display: block;"><p>Filesize: ' . $filesize_lanczos . ' bytes</p></div>';
    echo '  </div>';
    echo '</div>';
} 