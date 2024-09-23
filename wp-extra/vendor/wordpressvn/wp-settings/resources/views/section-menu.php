<?php if ($linked_sections = $settings->get_active_tab()->get_section_links()) { ?>
    <ul class="subsubsub" style="display: block; width: 100%; margin-bottom: 15px;">
        <?php foreach ($linked_sections as $section) { ?>
            <li><a href="<?php echo $settings->get_url(); ?>&tab=<?php echo $section->tab->slug; ?>&section=<?php echo $section->slug; ?>" class="<?php echo $section->slug == $settings->get_active_tab()->get_active_section()->slug ? 'current' : null; ?>"><?php echo $section->title; ?></a> | </li>
        <?php } ?>
    </ul>
<?php } else { ?>
    <?php if (count($settings->get_active_tab()->get_active_sections()) > 1) { ?>
        <nav class="nav-tab-wrapper nav-section">
        <?php foreach ($settings->get_active_tab()->get_active_sections() as $section) { ?>
             <a href="#" data-section="<?php echo $section->tab->slug; ?>-<?php echo $section->slug; ?>" class="nav-tab"><?php echo $section->title; ?></a>
        <?php } ?>
        </nav>
    <?php } ?>
<?php } ?>