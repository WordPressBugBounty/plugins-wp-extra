<section id="<?php echo $section->tab->slug.'-'.$section->slug; ?>" class="tab-content">
<div class="title">
<?php if ($section->description) { ?>
    <h3><?php echo $section->title; ?></h3>
    <p><?php echo $section->description; ?></p>
<?php } ?>
</div>
<table class="form-table striped">
    <tbody>
    <?php foreach ($section->options as $option) { ?>
        <?php echo $option->render(); ?>
    <?php } ?>
    </tbody>
</table>
</section>