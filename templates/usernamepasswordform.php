<?php
/**
 * Template form for setting username/password.
 *
 * Parameters:
 * - 'data': Parameters which should be included in the yes-request.
 * - 'attributes': The attributes for displaying the form.
 *
 * @package sgis
 * @version $Id$
 */
assert('is_array($this->data["attributes"])');

$this->data['header'] = $this->t('{sgis:sgis:sgis_header}');
$this->data['head']  = '<link rel="stylesheet" type="text/css" href="/' .
    $this->data['baseurlpath'] . 'module.php/sgis/style.css" />' . "\n";

$this->includeAtTemplateBase('includes/header.php');
$attributes = $this->data['attributes'];

$email = $this->data["attributes"]["mail"][0];
$name = $this->data["attributes"]["displayName"][0];

if ($this->data['errorcode'] !== NULL) {
?>
        <div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
                <img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" class="float-l" style="margin: 15px " />
                <h2><?php echo $this->t('{login:error_header}'); ?></h2>
                <p><b><?php echo $this->t('{sgis:sgis:sgis_error_header_' . $this->data['errorcode'] . '}'); ?></b></p>
                <p><?php echo $this->t('{sgis:sgis:sgis_error_desc_' . $this->data['errorcode'] . '}'); ?></p>
        </div>
<?php
}
?>

	<h2 style="break: both"><?php echo $this->t('{sgis:sgis:sgis_header}'); ?></h2>

<p>
<?php
echo $this->t(
    '{sgis:sgis:sgis_accept}',
    array( 'NAME' => $name, 'EMAIL' => $email)
);
?>
</p>

	<form action="?" method="post" name="f">
	<table>
		<tr>
			<td rowspan="3"><img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-authentication.48x48.png" alt="" /></td>
			<td style="padding: .3em;"><?php echo $this->t('{login:username}'); ?></td>
			<td><input type="text" id="username" tabindex="1" name="username" value="" /></td>
                </tr>
		<tr>
			<td style="padding: .3em;"><?php echo $this->t('{login:password}'); ?></td>
			<td><input id="password" type="password" tabindex="2" name="password" /></td>
		</tr>
                <tr>
                        <td><input type="submit" name="yes" id="yesbutton" value="<?php echo htmlspecialchars($this->t('{sgis:sgis:yes}')) ?>" /></td>
                        <td><input type="submit" name="no" id="nobutton" value="<?php echo htmlspecialchars($this->t('{sgis:sgis:no}')) ?>" /></td>
                </tr>
	</table>
<?
foreach ($this->data['data'] as $name => $value) {
    echo '<input type="hidden" name="' . htmlspecialchars($name) .
        '" value="' . htmlspecialchars($value) . '" />';
}
?>
</form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
