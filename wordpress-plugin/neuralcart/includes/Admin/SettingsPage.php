<?php
/**
 * NeuralCartSettingsPage
 * WordPress Admin → Settings → NeuralCart
 */
class NeuralCartSettingsPage
{
    public static function register(): void
    {
        add_options_page(
            'NeuralCart Settings',
            'NeuralCart',
            'manage_options',
            'neuralcart',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized'));
        }

        // Save settings
        if (isset($_POST['neuralcart_save']) && check_admin_referer('neuralcart_settings')) {
            update_option('neuralcart_saas_url', sanitize_url($_POST['saas_url'] ?? ''));
            if (!empty($_POST['regenerate_key'])) {
                update_option('neuralcart_api_key', 'nc_' . bin2hex(random_bytes(20)));
            }
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        $apiKey  = get_option('neuralcart_api_key', '');
        $saasUrl = get_option('neuralcart_saas_url', 'https://asianhost.net');
        $baseUrl = get_rest_url(null, 'neuralcart/v1');
        ?>
        <div class="wrap">
            <h1>🤖 NeuralCart Connector</h1>
            <p>Connect your WooCommerce store with NeuralCart AI Chatbot SaaS.</p>

            <div style="background:#fff;padding:24px;border:1px solid #ddd;border-radius:8px;max-width:600px;margin-top:20px;">
                <h2>📋 Connection Details</h2>
                <p>Copy these into your <strong>NeuralCart Dashboard → Store Connections</strong></p>

                <table class="form-table">
                    <tr>
                        <th>Plugin API URL</th>
                        <td>
                            <code style="background:#f0f0f0;padding:6px 12px;border-radius:4px;font-size:14px;"><?= esc_html($baseUrl) ?></code>
                            <button onclick="navigator.clipboard.writeText('<?= esc_js($baseUrl) ?>')" class="button button-small" style="margin-left:8px;">Copy</button>
                        </td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td>
                            <code style="background:#f0f0f0;padding:6px 12px;border-radius:4px;font-size:14px;"><?= esc_html($apiKey) ?></code>
                            <button onclick="navigator.clipboard.writeText('<?= esc_js($apiKey) ?>')" class="button button-small" style="margin-left:8px;">Copy</button>
                        </td>
                    </tr>
                </table>

                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field('neuralcart_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="saas_url">NeuralCart SaaS URL</label></th>
                            <td><input id="saas_url" name="saas_url" type="url" value="<?= esc_attr($saasUrl) ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Regenerate API Key</th>
                            <td><label><input type="checkbox" name="regenerate_key" value="1"> Generate a new API key (old key will stop working)</label></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="neuralcart_save" class="button-primary" value="Save Settings">
                    </p>
                </form>
            </div>

            <div style="background:#fff;padding:24px;border:1px solid #ddd;border-radius:8px;max-width:600px;margin-top:20px;">
                <h2>📖 How to Connect</h2>
                <ol>
                    <li>Copy the <strong>Plugin API URL</strong> and <strong>API Key</strong> above</li>
                    <li>Go to <a href="<?= esc_url($saasUrl) ?>/admin" target="_blank">NeuralCart Dashboard</a></li>
                    <li>Navigate to <strong>Platform → Store Connections → Add New</strong></li>
                    <li>Select <strong>WordPress / WooCommerce</strong></li>
                    <li>Paste the URL and API Key</li>
                    <li>Click <strong>Test Connection</strong></li>
                    <li>Done! Your chatbot will now use your WooCommerce products.</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
