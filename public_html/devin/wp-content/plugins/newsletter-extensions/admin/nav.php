<?php
$p = sanitize_key($_GET['page'] ?? '');
?>
<ul class="tnp-nav">
    <li class="<?php echo $p === 'newsletter_extensions_index'?'active':''?>"><a href="?page=newsletter_extensions_index"><?php esc_html_e('Addons', 'newsletter')?></a></li>
    <li class="<?php echo $p === 'newsletter_extensions_support'?'active':''?>"><a href="?page=newsletter_extensions_support"><?php esc_html_e('Support', 'newsletter')?></a></li>
</ul>
