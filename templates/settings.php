<form id="sgis" action="#" method="post">
    <fieldset class="personalblock">
        <legend><?php echo $l->t('SGIS'); ?></legend>
            <p><label for="sgis_url"><?php echo $l->t('URL');?></label><input type="text" id="sgis_url" name="sgis_url" value="<?php echo $_['sgis_url']; ?>"></p>
            <p><label for="sgis_key"><?php echo $l->t('Key');?></label><input type="text" id="sgis_key" name="sgis_key" value="<?php echo $_['sgis_key']; ?>" /></p>
        <input type="submit" value="<?php echo $l->t('Save'); ?>" />
    </fieldset>
</form>
