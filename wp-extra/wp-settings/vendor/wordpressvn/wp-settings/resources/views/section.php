<?php 
$columns = intval($section->args['columns'] ?? 1);
$grid_class = ($columns > 1) ? 'wps-grid wps-grid-' . $columns : 'striped';
?>
<section id="<?php echo $section->tab->slug.'-'.$section->slug; ?>" class="tab-content">
<div class="title">
<?php if ($section->description) { ?>
    <h3><?php echo $section->title; ?></h3>
    <p><?php echo $section->description; ?></p>
<?php } ?>
</div>
<table class="form-table <?php echo esc_attr($grid_class); ?>">
    <tbody>
    <?php foreach ($section->options as $option) { ?>
        <?php echo $option->render(); ?>
    <?php } ?>
    </tbody>
</table>
</section>