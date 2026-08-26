<ul class="nav-menu">
<?php foreach($settings->tabs as $tab) { ?>
    <li class="nav-item"><a href="<?php echo $settings->get_url(); ?>&tab=<?php echo $tab->slug; ?>" class="nav-link tab-<?php echo esc_attr($tab->slug); ?> <?php echo $tab->slug === $settings->get_active_tab()->slug ? 'active' : null; ?>"><?php echo $tab->title; ?></a></li>
<?php } ?>
</ul>