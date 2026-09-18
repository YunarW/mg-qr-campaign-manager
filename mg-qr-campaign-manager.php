<?php
/**
 * Plugin Name: MG QR Campaign Manager
 * Description: Manage short QR campaign URLs, UTM parameters, redirects, and basic scan counts for GA4 tracking across Muffin Graphics websites.
 * Version: 1.1.0
 * Author: Muffin Graphics
 */

if (!defined('ABSPATH')) exit;

class MG_QR_Campaign_Manager {
    const CPT = 'mg_qr_campaign';
    const LEGACY_CPT = 'ho_qr_campaign';
    const NONCE = 'mg_qr_campaign_nonce';

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::CPT, [$this, 'save_meta']);
        add_action('template_redirect', [$this, 'handle_redirect']);
        add_filter('post_type_link', [$this, 'campaign_permalink'], 10, 2);
        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'column_content'], 10, 2);
        add_action('admin_notices', [$this, 'admin_notice']);
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }

    public static function activate() {
        $self = new self();
        $self->register_cpt();
        $self->migrate_legacy_data();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function register_cpt() {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'MG QR Campaigns',
                'singular_name' => 'MG QR Campaign',
                'add_new' => 'Tambah Campaign',
                'add_new_item' => 'Tambah MG QR Campaign',
                'edit_item' => 'Edit MG QR Campaign',
                'menu_name' => 'MG QR Campaigns',
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-qrcode',
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'q', 'with_front' => false],
            'show_in_rest' => false,
        ]);
    }

    public function migrate_legacy_data() {
        global $wpdb;

        $legacy_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
                self::LEGACY_CPT
            )
        );

        if (!$legacy_ids) return;

        $meta_map = [
            '_ho_qr_destination' => '_mg_qr_destination',
            '_ho_qr_source'      => '_mg_qr_source',
            '_ho_qr_medium'      => '_mg_qr_medium',
            '_ho_qr_campaign'    => '_mg_qr_campaign',
            '_ho_qr_content'     => '_mg_qr_content',
            '_ho_qr_term'        => '_mg_qr_term',
            '_ho_qr_enabled'     => '_mg_qr_enabled',
            '_ho_qr_scans'       => '_mg_qr_scans',
            '_ho_qr_last_scan'   => '_mg_qr_last_scan',
        ];

        foreach ($legacy_ids as $post_id) {
            $wpdb->update(
                $wpdb->posts,
                ['post_type' => self::CPT],
                ['ID' => (int) $post_id],
                ['%s'],
                ['%d']
            );

            foreach ($meta_map as $old_key => $new_key) {
                if (metadata_exists('post', $post_id, $old_key) && !metadata_exists('post', $post_id, $new_key)) {
                    update_post_meta($post_id, $new_key, get_post_meta($post_id, $old_key, true));
                }
            }
        }
    }

    public function add_meta_boxes() {
        add_meta_box('mg_qr_settings', 'MG QR Campaign Settings', [$this, 'render_settings'], self::CPT, 'normal', 'high');
        add_meta_box('mg_qr_stats', 'Tracking', [$this, 'render_stats'], self::CPT, 'side', 'default');
    }

    private function field($post_id, $key, $default = '') {
        $v = get_post_meta($post_id, $key, true);
        return $v !== '' ? $v : $default;
    }

    public function render_settings($post) {
        wp_nonce_field(self::NONCE, self::NONCE);
        $dest = $this->field($post->ID, '_mg_qr_destination');
        $source = $this->field($post->ID, '_mg_qr_source');
        $medium = $this->field($post->ID, '_mg_qr_medium', 'qr_code');
        $campaign = $this->field($post->ID, '_mg_qr_campaign');
        $content = $this->field($post->ID, '_mg_qr_content');
        $term = $this->field($post->ID, '_mg_qr_term');
        $status = $this->field($post->ID, '_mg_qr_enabled', '1');
        $short = get_permalink($post);
        ?>
        <style>
            .mgqr-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.mgqr-grid .full{grid-column:1/-1}.mgqr-field label{display:block;font-weight:600;margin-bottom:6px}.mgqr-field input,.mgqr-field select{width:100%}.mgqr-help{color:#666;font-size:12px;margin-top:4px}.mgqr-short{background:#f6f7f7;padding:10px;border-radius:6px;font-family:monospace;word-break:break-all}
            @media(max-width:782px){.mgqr-grid{grid-template-columns:1fr}}
        </style>
        <div class="mgqr-grid">
            <div class="mgqr-field full"><label>Destination URL</label><input type="url" name="mg_qr_destination" value="<?php echo esc_attr($dest); ?>" placeholder="https://example.com/landing-page/"><div class="mgqr-help">URL tujuan setelah QR discan.</div></div>
            <div class="mgqr-field"><label>utm_source</label><input type="text" name="mg_qr_source" value="<?php echo esc_attr($source); ?>" placeholder="komik_nextg"></div>
            <div class="mgqr-field"><label>utm_medium</label><input type="text" name="mg_qr_medium" value="<?php echo esc_attr($medium); ?>" placeholder="qr_code"></div>
            <div class="mgqr-field"><label>utm_campaign</label><input type="text" name="mg_qr_campaign" value="<?php echo esc_attr($campaign); ?>" placeholder="promo_august"></div>
            <div class="mgqr-field"><label>utm_content</label><input type="text" name="mg_qr_content" value="<?php echo esc_attr($content); ?>" placeholder="poster_a"></div>
            <div class="mgqr-field"><label>utm_term (opsional)</label><input type="text" name="mg_qr_term" value="<?php echo esc_attr($term); ?>"></div>
            <div class="mgqr-field"><label>Status</label><select name="mg_qr_enabled"><option value="1" <?php selected($status,'1'); ?>>Aktif</option><option value="0" <?php selected($status,'0'); ?>>Nonaktif</option></select></div>
            <div class="mgqr-field full"><label>Short URL</label><div class="mgqr-short"><?php echo esc_html($short); ?></div><div class="mgqr-help">Gunakan URL ini untuk QR Code. Tujuan bisa diganti kapan saja tanpa mengubah QR fisik.</div></div>
        </div>
        <?php
    }

    public function render_stats($post) {
        $scans = (int) get_post_meta($post->ID, '_mg_qr_scans', true);
        $last = get_post_meta($post->ID, '_mg_qr_last_scan', true);
        echo '<p><strong>Total Scan</strong><br><span style="font-size:28px;font-weight:700">' . esc_html(number_format_i18n($scans)) . '</span></p>';
        echo '<p><strong>Scan Terakhir</strong><br>' . ($last ? esc_html($last) : 'Belum ada') . '</p>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE])), self::NONCE)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $map = [
            '_mg_qr_destination' => ['mg_qr_destination', 'esc_url_raw'],
            '_mg_qr_source'      => ['mg_qr_source', 'sanitize_text_field'],
            '_mg_qr_medium'      => ['mg_qr_medium', 'sanitize_text_field'],
            '_mg_qr_campaign'    => ['mg_qr_campaign', 'sanitize_text_field'],
            '_mg_qr_content'     => ['mg_qr_content', 'sanitize_text_field'],
            '_mg_qr_term'        => ['mg_qr_term', 'sanitize_text_field'],
            '_mg_qr_enabled'     => ['mg_qr_enabled', 'sanitize_text_field'],
        ];

        foreach ($map as $meta => [$field, $san]) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta, call_user_func($san, wp_unslash($_POST[$field])));
            }
        }
    }

    public function campaign_permalink($url, $post) {
        if ($post->post_type !== self::CPT) return $url;
        return home_url('/q/' . $post->post_name . '/');
    }

    private function build_target($post_id) {
        $dest = get_post_meta($post_id, '_mg_qr_destination', true);
        if (!$dest) return home_url('/');

        $params = [];
        foreach ([
            'utm_source'   => '_mg_qr_source',
            'utm_medium'   => '_mg_qr_medium',
            'utm_campaign' => '_mg_qr_campaign',
            'utm_content'  => '_mg_qr_content',
            'utm_term'     => '_mg_qr_term',
        ] as $param => $meta) {
            $v = trim((string) get_post_meta($post_id, $meta, true));
            if ($v !== '') $params[$param] = $v;
        }

        return add_query_arg($params, $dest);
    }

    public function handle_redirect() {
        if (!is_singular(self::CPT)) return;
        global $post;
        if (!$post) return;

        $enabled = get_post_meta($post->ID, '_mg_qr_enabled', true);
        if ($enabled === '0') {
            status_header(404);
            nocache_headers();
            wp_die('QR campaign ini sedang nonaktif.', 'QR Campaign Nonaktif', ['response' => 404]);
        }

        $scans = (int) get_post_meta($post->ID, '_mg_qr_scans', true);
        update_post_meta($post->ID, '_mg_qr_scans', $scans + 1);
        update_post_meta($post->ID, '_mg_qr_last_scan', current_time('mysql'));

        $target = $this->build_target($post->ID);
        wp_safe_redirect($target, 302, 'MG QR Campaign Manager');
        exit;
    }

    public function columns($cols) {
        return [
            'cb'       => $cols['cb'],
            'title'    => 'Campaign',
            'short'    => 'Short URL',
            'source'   => 'Source / Medium',
            'campaign' => 'Campaign',
            'scans'    => 'Scans',
            'date'     => $cols['date'],
        ];
    }

    public function column_content($col, $post_id) {
        if ($col === 'short') echo '<code>' . esc_html(get_permalink($post_id)) . '</code>';
        if ($col === 'source') echo esc_html(get_post_meta($post_id, '_mg_qr_source', true) . ' / ' . get_post_meta($post_id, '_mg_qr_medium', true));
        if ($col === 'campaign') echo esc_html(get_post_meta($post_id, '_mg_qr_campaign', true));
        if ($col === 'scans') echo esc_html(number_format_i18n((int) get_post_meta($post_id, '_mg_qr_scans', true)));
    }

    public function admin_notice() {
        if (!current_user_can('manage_options')) return;
        if (get_option('mg_qr_notice_done')) return;
        echo '<div class="notice notice-info is-dismissible"><p><strong>MG QR Campaign Manager aktif.</strong> Buat campaign di menu MG QR Campaigns, lalu gunakan short URL /q/slug/ pada QR Code.</p></div>';
        update_option('mg_qr_notice_done', 1);
    }
}

new MG_QR_Campaign_Manager();
