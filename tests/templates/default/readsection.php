<div><?= $this->body() ?><?= $text ?></div>
<?php if ($this->has('list')) : ?>
    <?php echo $this->section('list'); ?>
<?php else : ?>
    <p>no list</p>
<?php endif ?>
