<?php
/* @var $this NewsletterExtensions */

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$extensions = $this->get_extensions_catalog();
if (!isset($extensions[0]->category)) {
    delete_transient("tnp_extensions_json");
    $extensions = $this->get_extensions_catalog();
}
$license_key = Newsletter::instance()->get_license_key();

$license_data = $this->get_license_data();

if ($controls->is_action('install')) {

    $extension = null;
    foreach ($extensions as $e) {
        if ($e->id == $_GET['id']) {
            $extension = $e;
            break;
        }
    }

    $id = $extension->id;
    $slug = $extension->slug;

    $source = 'https://www.thenewsletterplugin.com/wp-content/plugins/file-commerce-pro/get.php?f=' . urlencode($id) .
            '&k=' . urlencode($license_key) . '&d=' . urlencode(home_url());

    if (!class_exists('Plugin_Upgrader', false)) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }

    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());

    $result = $upgrader->install($source);
    if (!$result || is_wp_error($result)) {
        $controls->errors = 'Error while installing, probably your site cannot download the package from our servers.<br>';
        $controls->errors .= 'Consider to download the addon from your <a href="https://www.thenewsletterplugin.com/account" target="_blank">account page</a> and install manually from the plugin page (Add New/Upload)<br>';

        if (is_wp_error($result)) {
            $controls->errors .= 'Error details: ' . $result->get_error_message();
        }
    } else {
        $result = activate_plugin($extension->wp_slug);
        if (is_wp_error($result)) {
            $controls->errors .= __('Error while activating:', 'newsletter') . " " . $result->get_error_message();
        } else {
            wp_clean_plugins_cache(false);
            $controls->js_redirect(admin_url('admin.php') . '?page=newsletter_extensions_index&installed=1');
//$controls->messages .= __('Installed and activated', 'newsletter');
            die();
        }
    }
//wp_clean_plugins_cache(false);
//wp_redirect(admin_url('admin.php') . '?page=newsletter_main_extensions');
//die();
}

if ($controls->is_action('activate')) {
    $extension = null;
    $id = (int) $_GET['id'];
    foreach ($extensions as $e) {
        if ($e->id == $id) {
            $extension = $e;
            break;
        }
    }
    $result = activate_plugin($extension->wp_slug);
    if (is_wp_error($result)) {
        $controls->errors .= __('Error while activating:', 'newsletter') . " " . $result->get_error_message();
    } else {
        wp_clean_plugins_cache(false);
        $controls->js_redirect(admin_url('admin.php') . '?page=newsletter_extensions_index&activated=1');
        die();
    }
}

if ($controls->is_action('deactivate')) {
    $extension = null;
    $id = (int) $_GET['id'];
    foreach ($extensions as $e) {
        if ($e->id == $id) {
            $extension = $e;
            break;
        }
    }
    $result = deactivate_plugins($extension->wp_slug);
    if (is_wp_error($result)) {
        $controls->errors .= __('Error while deactivating:', 'newsletter') . " " . $result->get_error_message();
    } else {
        wp_clean_plugins_cache(false);
        $controls->js_redirect(admin_url('admin.php') . '?page=newsletter_extensions_index&deactivated=1');
        die();
    }
}

if ($controls->is_action('save')) {

    if (!empty($controls->data['contract_key'])) {
        $option = get_option('newsletter_main');
        $option['contract_key'] = $license_key;
        update_option('newsletter_main', $option);
        $license_data = Newsletter::instance()->get_license_data(true);
    }
}

$map = array();
if (!is_wp_error($license_data) && !empty($license_data->extensions) && is_array($license_data->extensions)) {
    foreach ($license_data->extensions as $item) {
        $map['' . $item->id] = $item;
    }
}

if (!$extensions) {
    $controls->errors .= '<br><br>Your blog cannot contact our addons catalog service. You can get the addons directly from your <a href="https://www.thenewsletterplugin.com/account" target="_blank">account page</a>.';
} else {
    foreach ($extensions AS $e) {
        $e->activate_url = wp_nonce_url(admin_url('admin.php') . '?page=newsletter_extensions_index&act=activate&id=' . ((int) $e->id), 'save');
        $e->deactivate_url = wp_nonce_url(admin_url('admin.php') . '?page=newsletter_extensions_index&act=deactivate&id=' . ((int) $e->id), 'save');
        $e->install_url = wp_nonce_url(admin_url('admin.php') . '?page=newsletter_extensions_index&act=install&id=' . ((int) $e->id), 'save');
        $e->is_installed = file_exists(WP_PLUGIN_DIR . "/" . $e->wp_slug);
        $e->is_active = is_plugin_active($e->wp_slug);

        if (isset($map['' . $e->id])) {
            $e->downloadable = $map['' . $e->id]->downloadable;
        } else {
            $e->downloadable = false;
        }
    }
}

if (isset($_GET['installed'])) {
    $controls->add_toast('Addon installed');
} elseif (isset($_GET['activated'])) {
    $controls->add_toast('Addon activated');
} elseif (isset($_GET['deactivated'])) {
    $controls->add_toast('Addon deactivated');
}

function tnp_extensions_table($extensions, $category) {
    ?>

    <table class="widefat tnp-extensions">
        <?php foreach ($extensions as $e) { ?>
            <?php if (strpos($e->category, $category) === false) continue; ?>
            <tr id="<?php echo esc_attr($e->slug) ?>">
                <td width="1%">
                    <?php if ($e->url) { ?>
                        <a href="<?php echo $e->url ?>" target="_blank">
                        <?php } ?>
                        <img src="<?php echo $e->image ?>" alt="<?php echo esc_attr($e->title) ?>">
                        <?php if ($e->url) { ?>
                        </a>
                    <?php } ?>
                </td>
                <td width="79%">
                    <?php if ($e->url) { ?>
                        <a href="<?php echo $e->url ?>" target="_blank" style="color: #444">
                        <?php } ?>
                        <strong><?php echo esc_html($e->title) ?></strong>
                        <?php if ($e->free) { ?>
                            <span class="tnp-free">Free</span>
                        <?php } ?>

                        <div style="font-size:.9em">
                            <?php echo esc_html($e->description) ?>
                        </div>
                        <?php if ($e->url) { ?>
                        </a>
                    <?php } ?>
                </td>
                <td width="20%">
                    <?php if ($e->is_installed) { ?>

                        <?php if ($e->is_active) { ?>
                            <a href="<?php echo $e->deactivate_url ?>" class="tnp-action tnp-deactivate">
                                <i class="fas fa-times" aria-hidden="true"></i> <?php esc_html_e('Deactivate', 'newsletter') ?>
                            </a>
                        <?php } else { ?>
                            <a href="<?php echo $e->activate_url ?>" class="tnp-action tnp-activate">
                                <i class="fas fa-power-off" aria-hidden="true"></i> <?php esc_html_e('Activate', 'newsletter') ?>
                            </a>
                        <?php } ?>

                    <?php } else { ?>

                        <?php if ($e->downloadable) { ?>
                            <a href="<?php echo $e->install_url ?>" class="tnp-action tnp-install">
                                <i class="fas fa-download" aria-hidden="true"></i> Install Now
                            </a>
                        <?php } else { ?>

                            <?php if ($e->free) { ?>

                                <a href="#tnp-body" class="tnp-action tnp-install">
                                    <i class="fas fa-download" aria-hidden="true"></i> Get a free license
                                </a>
                            <?php } else { ?>
                                <a href="https://www.thenewsletterplugin.com/premium?utm_source=manager&utm_medium=<?php echo urlencode($e->slug) ?>&utm_campaign=plugin" class="tnp-action tnp-buy" target="_blank">
                                    <i class="fas fa-shopping-cart" aria-hidden="true"></i> Buy Now
                                </a>
                            <?php } ?>
                        <?php } ?>

                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>

    <?php
}
?>

<style>
<?php readfile(__DIR__ . '/css/extensions.css') ?>
</style>

<script>
    function tnp_register() {

        if (!document.getElementById('tnp-privacy').checked) {
            alert('Please, accept the terms of service and the privacy policy, thank you!');
            return;
        }

        jQuery.post(ajaxurl, {
            action: "tnp_addons_register",
            _wpnonce: "<?php echo esc_js(wp_create_nonce("register")) ?>",
            email: document.getElementById("tnp-email").value,
            //marketing: document.getElementById('tnp-marketing').checked?'1':'0'
        }, function (data) {
            alert(data.message);
            if (data.reload) {
                location.reload();
            }
        });
    }
    function tnp_license() {
        jQuery.post(ajaxurl, {
            action: "tnp_addons_license",
            _wpnonce: "<?php echo esc_js(wp_create_nonce("license")) ?>",
            license_key: document.getElementById("tnp-license-key").value
        }, function (data) {
            location.reload();
        });
    }
</script>


<div class="wrap tnp-extensions-index" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <h2><?php esc_html_e('Addons Manager and Support', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php'; ?>
    </div>

    <div id="tnp-body">
        <?php $controls->show() ?>

        <?php if (is_wp_error($license_data)) { ?>
            <!-- There is already a message on top of the page, but we should add more here -->
        <?php } else if ($license_data === false || isset($_GET['debug'])) { ?>
            <div class="row tnp-promo" style="margin-left: -15px;">
                <div class="col-md-12">
                    <h1>Almost <span>done</span>!</h1>
                </div>
                <div class="col-md-6">

                    <div class="tnp-promo-box">

                        <h2>Sign up for a free license</h2>

                        <p>
                            Every now and then we'll email you about promotions and you'll get access to our forums and free addons.
                        </p>
                        <p>
                            <input id="tnp-email" type="email" name="email" value="" placeholder="Your email address (double check it!)">
                            <button class="button-primary" onclick="tnp_register()">Get a free license</button>
                        </p>
                        <!--
                        <p>
                            <label><input type="checkbox" id="tnp-marketing"> I'd like to be informed of <strong>promtions</strong> and <strong>discounts</strong></label>
                        </p>
                        -->
                        <p>
                            <label><input type="checkbox" id="tnp-privacy"> I accept the <a href="https://www.thenewsletterplugin.com/terms" target=""_blank">terms of service</a> and the
                                <a href="https://www.thenewsletterplugin.com/privacy" target="_blank_">privacy policy</a></label>
                        </p>
                    </div>

                </div>

                <div class="col-md-6">
                    <div class="tnp-promo-box">
                        <h2>Already have a license or an account?</h2>
                        <p>
                            If you're a member of <a href="https://www.thenewsletterplugin.com/?utm_source=manager&utm_medium=link&utm_campaign=plugin" target="_blank">thenewsletterplugin.com</a>
                            get it on <a href="https://www.thenewsletterplugin.com/account?utm_source=manager&utm_medium=link&utm_campaign=plugin" target="_blank">your account</a>
                        </p>

                        <p>
                            <input id="tnp-license-key" type="text" name="license" value="" placeholder="Your license key">
                            <button class="button-secondary" onclick="tnp_license()">Save</button>
                        </p>
                    </div>
                </div>
            </div>
        <?php } else if ($license_data->expire == 0) { // Free license    ?>

            <h1>Are you enjoying our free addons?</h1>
            <h2><a href="https://www.thenewsletterplugin.com/premium?utm_source=manager&utm_medium=link&utm_campaign=plugin" target="_blank">Check out how to jump up to a pro level with our premium addons</a></h2>

        <?php } ?>

        <?php if ($extensions) { ?>

            <p>
                Addons are regular plugins you can manage from <a href="plugins.php">your plugins page</a> once installed.
            </p>

            <h3>Collecting subscribers</h3>
            <?php tnp_extensions_table($extensions, 'subscription') ?>

            <h3>Creating newsletters</h3>
            <?php tnp_extensions_table($extensions, 'newsletters') ?>

            <h3>Automating your work</h3>
            <?php tnp_extensions_table($extensions, 'automation') ?>

            <h3>Analytics</h3>
            <?php tnp_extensions_table($extensions, 'statistics') ?>

            <h3>Delivery</h3>
            <p>
                High speed sending of your newsletter with professional delivery services. Automatic bounces and complaints management.
            </p>
            <?php tnp_extensions_table($extensions, 'delivery') ?>

            <h3>Tools</h3>
            <?php tnp_extensions_table($extensions, 'tools') ?>

        <?php } else { ?>

            <p style="color: white;">No addons available. Could be a connection problem, try later.</p>

        <?php } ?>


        <p class="clear"></p>

    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>

</div>
