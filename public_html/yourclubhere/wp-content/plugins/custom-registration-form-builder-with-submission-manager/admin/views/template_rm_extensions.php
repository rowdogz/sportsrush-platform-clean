<?php
if (!defined('WPINC')) {
    die('Closed');
}

echo '';


?>

<div class="rm-text-left rm-py-3">
    <h2 style="margin: 4px 0px">Enhance Your RegistrationMagic Experience with Extensions</h2>
    <p style="padding: 0px 0px">All Premium extensions are free for RegistrationMagic Premium users. Explore and activate them to expand your form capabilities.</p>
</div>

<div class="rm_pr_block_container rm-d-flex rm-flex-wrap">
    <!-- Free Extension -->
    <div class="rm_pr_block_small">
        <div class="rm_pr_feature_icon"><img class="rm_feature_icon" src="<?php echo RM_IMG_URL; ?>premium/turnstile-icon.png"></div>
        <div class="rm_pr_feature_content">
            <div class="rm_pr_feature_title">Turnstile Antispam Security Ext.</div>
            <span class="rm_pr_tag rm_pr_tag-free">Available</span>
            <div class="rm_pr_feature_desc">Protect your forms from spam bots with Cloudflare Turnstile's advanced, non-intrusive detection system. Ensure a smooth user experience while maintaining privacy-focused security.</div>
        </div>
    </div>

    <!-- Upcoming Extension 1 -->
    <div class="rm_pr_block_small">
        <div class="rm_pr_feature_icon"><img class="rm_feature_icon" src="<?php echo RM_IMG_URL; ?>premium/honeypot-icon.png"></div>
        <div class="rm_pr_feature_content">
            <div class="rm_pr_feature_title">HoneyPot</div>
            <span class="rm_pr_tag rm_pr_tag-upcoming">Upcoming</span>
            <div class="rm_pr_feature_desc">Add invisible honeypot fields to detect and block automated spam submissions. Enhance form security without disrupting the user experience.</div>
        </div>
    </div>

    <!-- Upcoming Extension 2 -->
    <div class="rm_pr_block_small">
        <div class="rm_pr_feature_icon"><i class="material-icons">save</i></div>
        <div class="rm_pr_feature_content">
            <div class="rm_pr_feature_title">Save Form Progress Ext.</div>
            <span class="rm_pr_tag rm_pr_tag-upcoming">Upcoming</span>
            <div class="rm_pr_feature_desc">Allow users to save incomplete forms and resume later without losing their data. Ideal for lengthy or complex submissions requiring multiple sessions.</div>
        </div>
    </div>

    <!-- Upcoming Extension 3 -->
    <div class="rm_pr_block_small">
        <div class="rm_pr_feature_icon"><i class="material-icons">autorenew</i></div>
        <div class="rm_pr_feature_content">
            <div class="rm_pr_feature_title">Recurring Payments Ext.</div>
            <span class="rm_pr_tag rm_pr_tag-upcoming">Upcoming</span>
            <div class="rm_pr_feature_desc">Enable recurring payment options for your forms, perfect for subscriptions and memberships. Integrates with popular gateways like PayPal and Stripe.</div>
        </div>
    </div>
    
    
    <!-- Upcoming Extension 4 -->
    <div class="rm_pr_block_small">
        <div class="rm_pr_feature_icon"><i class="material-icons">extension</i></div>
        <div class="rm_pr_feature_content">
            <div class="rm_pr_feature_title">OpenAI Integration Ext.</div>
            <span class="rm_pr_tag rm_pr_tag-upcoming">Upcoming</span>
            <div class="rm_pr_feature_desc">Leverage OpenAI to generate AI-powered summaries and insights for form submissions. Improve data analysis and streamline workflows with intelligent processing.</div>
        </div>
    </div>
    
</div>


<style>
    /* Container styling */
    
    
    .rm_pr_feature_icon{
        display: none;
    }   
    
.rm_pr_block_container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.rm_pr_block_small {
    display: inline-block !important;
}

.rm_pr_block_small {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex: 1 1 calc(25% - 20px);
    max-width: calc(33% - 48px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
}

.rm_pr_block_small:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.rm_pr_feature_icon {
    font-size: 36px;
    color: #6DC1B1;
    margin-right: 12px;
}

.rm_pr_feature_content {
    flex: 1;
}

.rm_pr_feature_title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.rm_pr_feature_desc {
    font-size: 14px;
    color: #555;
    margin-top: 8px;
    line-height: 1.5;
}

.rm_pr_tag {
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    margin-bottom: 8px;
}

.rm_pr_tag-free {
    background-color: #6DC1B1;
    color: #fff;
}

.rm_pr_tag-upcoming {
    background-color: #FFA500;
    color: #fff;
}

</style>
