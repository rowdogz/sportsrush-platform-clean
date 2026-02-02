<?php
/* @var $this NewsletterExtensions */

use Newsletter\License;

$controls = new NewsletterControls();

if ($controls->is_action('send_support_data')) {
    if ($this->is_license_active()) {
        $response = wp_remote_post('https://www.thenewsletterplugin.com/wp-content/support-data.php?k='
                . rawurlencode(Newsletter::instance()->get_license_key()),
                ['body' => wp_json_encode($this->build_support_data())]);
        $body = wp_remote_retrieve_body($response);

        $controls->add_toast_done();
    } else {
        $controls->add_toast('Premium license not active');
    }
}

if ($controls->is_action('cron-service')) {
    if ($this->is_license_active()) {
        $response = wp_remote_post('https://www.thenewsletterplugin.com/wp-content/cron-service.php?k='
                . rawurlencode(Newsletter::instance()->get_license_key()),
                ['body' => wp_json_encode(['cron_url' => site_url('wp-cron.php')])]);

        $response_code = wp_remote_retrieve_response_code($response);
//    echo wp_remote_retrieve_body($response);
//    echo $response_code;
        if ($response_code != 200) {
            $controls->errors = 'Unable to activate the service: ' . esc_html(wp_remote_retrieve_body($response));
        } else {
            $controls->add_toast_done();
        }
    } else {
        $controls->add_toast('Premium license not active');
    }
}

if ($controls->is_action('save')) {
    $this->save_options($controls->data);
    $controls->add_toast_saved();
}

if ($controls->is_action('send_support_email')) {
    if ($this->is_license_active()) {
        $controls->errors = [];

        $url = str_replace(['http://', 'https://'], '', home_url());
        $subject = 'Test email from ' . $url . ' for TNP support (' . gmdate(DATE_ISO8601) . ')';

        $message = NewsletterMailerAddon::get_test_message('test@thenewsletterplugin.com', $subject);

        $r = Newsletter::instance()->deliver($message);

        if (is_wp_error($r)) {
            $controls->errors[] = '<strong>FAILED</strong> (' . esc_html($r->get_error_message()) . ')';
        }
        $message = NewsletterMailerAddon::get_test_message('tnpplugin@gmail.com', $subject);

        $r = Newsletter::instance()->deliver($message);

        if (is_wp_error($r)) {
            $controls->errors[] = '<strong>FAILED</strong> (' . esc_html($r->get_error_message()) . ')';
        }
        $message = NewsletterMailerAddon::get_test_message('tnpplugin@yahoo.com', $subject);

        $r = Newsletter::instance()->deliver($message);

        if (is_wp_error($r)) {
            $controls->errors[] = '<strong>FAILED</strong> (' . esc_html($r->get_error_message()) . ')';
        }
        $message = NewsletterMailerAddon::get_test_message('tnpplugin@outlook.com', $subject);

        $r = Newsletter::instance()->deliver($message);

        if (is_wp_error($r)) {
            $controls->errors[] = '<strong>FAILED</strong> (' . esc_html($r->get_error_message()) . ')';
        }

        if (empty($controls->errors)) {
            $controls->add_toast_done();
        }
    } else {
        $controls->add_toast('Premium license not active');
    }
}
?>

<style>
<?php include __DIR__ . '/css/dashboard.css' ?>
</style>

<div class="wrap tnp-extensions-support" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <h2><?php esc_html_e('Addons Manager and Support', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php'; ?>
    </div>

    <div id="tnp-body">
        <?php $controls->show() ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-dashboard">

                <div class="tnp-cards-container">

                    <div class="tnp-card">
                        <div class="tnp-card-title">Premium support</div>

                        <?php if (License::is_reseller()) { ?>
                            <p>
                                You're using a reseller license, please contact your supplier for support. If you're the
                                reseller, you can get support directly from your
                                <a href="https://www.thenewsletterplugin.com/account" target="_blank">account page</a>.
                            </p>
                        <?php } ?>


                            <p>
                                <?php $controls->yesno('allow'); ?> <?php $controls->button_save(); ?>
                                Allow our support to
                                <a href="#tnp-support-data-json" rel="modal:open">access the diagnostic data</a>
                            </p>


                        <p>
                            <?php $controls->btn('send_support_data', __('Send data for support', 'newsletter'), ['disabled'=>License::is_free()]) ?>
                            <?php
                            if (false && !License::is_reseller()) {
                                $controls->btn_link('https://www.thenewsletterplugin.com/account', __('Open a ticket', 'newsletter'),
                                        ['target' => '_blank', 'secondary' => 'true']);
                            }
                            ?>
                        </p>
                        <p>
                            <a href="#tnp-support-data-json" rel="modal:open">See the support data</a>.
                            Our support staff will use those information to help resolving your issues.
                        </p>

                        <p>
                            <?php $controls->btn('send_support_email', __('Send test emails for support', 'newsletter'), ['disabled'=>License::is_free()]) ?>
                        </p>
                        <p>
                            It could be requested by our staff to analyze problems with your emails. A few test emails
                            are sent to our test addresses. To run a test yourself see the
                            <a href="admin.php?page=newsletter_system_delivery" target="_blank">Help/Delivery panel</a>.
                        </p>
                        <p>
                            <?php $controls->btn('cron-service', 'Activate the cron service', ['disabled'=>License::is_free()]); ?>
                        </p>
                        <p>
                            This is a complementary service, only one site is supported. You can check the activation
                            on your <a href="https://www.thenewsletterplugin.com/account/cron" target="_blank">account page</a>.
                        </p>


                    </div>

                    <div class="tnp-card">
                        <div class="tnp-card-title">How to get support</div>

                        <h3><i class="fas fa-book"></i> Documentation</h3>
                        <p>
                            We have <a href="https://www.thenewsletterplugin.com/documentation" target=_blank">extensive documentation</a>
                            about the Newsletter plugin settigs and feature and the free and commercial addons.
                        </p>

                        <h3><i class="fas fa-comment"></i> Forum</h3>
                        <p>We run a <a href="https://www.thenewsletterplugin.com/forums" target=_blank">support forum</a>
                            where you can send your requests for help, new features, ideas and so on.
                        </p>

                    </div>



                </div>

            </div>

        </form>

    </div>
</div>

<div id="tnp-support-data-json" style="display: none">
    <h3>Support data</h3>
    <p>No emails or other personal data is shared.</p>
    <pre style="height: 200px; overflow: auto; background-color: #fff; padding: 1rem;"><?php echo esc_html(wp_json_encode($this->build_support_data(), JSON_PRETTY_PRINT)); ?></pre>
</div>