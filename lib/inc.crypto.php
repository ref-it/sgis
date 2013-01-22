<?php

function encrypt( $msg, $k, $base64 = true ) {

	# open cipher module (do not change cipher/mode)
	if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
		return false;

	$msg = serialize($msg);					  # serialize
	$iv  = mcrypt_create_iv(32, MCRYPT_RAND);	      # create iv

	if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
		return false;

	$msg  = mcrypt_generic($td, $msg);			    # encrypt
	$msg  = $iv . $msg;							  # prepend iv
	$mac  = self::pbkdf2($msg, $k, 1000, 32);	    # create mac
	$msg .= $mac;							      # append mac

	mcrypt_generic_deinit($td);					  # clear buffers
	mcrypt_module_close($td);					      # close cipher module

	if ( $base64 ) $msg = base64_encode($msg);	    # base64 encode?

	return $msg;								# return iv+ciphertext+mac
}

function decrypt( $msg, $k, $base64 = true ) {
	if ( $base64 ) $msg = base64_decode($msg);		    # base64 decode?

	# open cipher module (do not change cipher/mode)
	if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
		return false;

	$iv  = substr($msg, 0, 32);						  # extract iv
	$mo  = strlen($msg) - 32;						      # mac offset
	$em  = substr($msg, $mo);						      # extract mac
	$msg = substr($msg, 32, strlen($msg)-64);		      # extract ciphertext
	$mac = self::pbkdf2($iv . $msg, $k, 1000, 32);	  # create mac

	if ( $em !== $mac )								  # authenticate mac
		return false;

	if ( mcrypt_generic_init($td, $k, $iv) !== 0 )    # initialize buffers
		return false;

	$msg = mdecrypt_generic($td, $msg);				  # decrypt
	$msg = unserialize($msg);						      # unserialize

	mcrypt_generic_deinit($td);						  # clear buffers
	mcrypt_module_close($td);						      # close cipher module
	return $msg;									# return original msg
}

function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {

	$hl = strlen(hash($a, null, true));     # Hash length
	$kb = ceil($kl / $hl);		    # Key blocks to compute
	$dk = '';						      # Derived key

	# Create key
	for ( $block = 1; $block <= $kb; $block ++ ) {

		# Initial hash for this block
		$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

		# Perform block iterations
		for ( $i = 1; $i < $c; $i ++ )

			# XOR each iterate
			$ib ^= ($b = hash_hmac($a, $b, $p, true));

		$dk .= $ib; # Append iterated block
	}

	# Return derived key of correct length
	return substr($dk, 0, $kl);
}

