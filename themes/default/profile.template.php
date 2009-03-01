<?php
//TODO: implement javascript version of form

if(isset($_GET['action']) && $_GET['action']=='doedit'){
	// login check
	if(!isset($_SESSION['userid'])) die();
	
	// new password
	if(isset($_POST['password'])){
		if(empty($_POST['password']))
			echo 'Password field was empty. Sending you back...<br/>';
		else
			ServerDatabase::getInstance()->updateUserPw($_SESSION['serverid'], $_SESSION['userid'], $_POST['password']);
	}
	// new username
	if(isset($_POST['name'])){
		ServerDatabase::getInstance()->updateUserName($_SESSION['serverid'], $_SESSION['userid'], $_POST['name']);
	}
	// new email
	if(isset($_POST['email'])){
		ServerDatabase::getInstance()->updateUserEmail($_SESSION['serverid'], $_SESSION['userid'], $_POST['email']);
	}
	// remove texture
	if(isset($_GET['remove_texture'])){
		//TODO: send empty texture
		try{
			ServerDatabase::getInstance()->updateUserTexture($_SESSION['serverid'], $_SESSION['userid'], array());
		}catch(Murmur_InvalidTextureException $exc){
			echo 'failed';
		}
	}
	// new texture
	if(isset($_FILES['texture'])){
		if(!file_exists($_FILES['texture']['tmp_name'])){
			echo '<div class="error">Temp file does not exist.</div>';
		}else{
			
			$fileExtension = pathinfo($_FILES['texture']['name']);
			$fileExtension = isset($fileExtension['extension']) ? $fileExtension['extension'] : '';
			
			switch($fileExtension){
				case 'png':
					if(!$texImg = imagecreatefrompng($_FILES['texture']['tmp_name']))
						die('<div class="error">Error: Could not create image resource.</div>');
					$tex = '';
					for($y=0; $y<imagesy($texImg); $y++){
						for($x=0; $x<imagesx($texImg); $x++){
							$colorIndex = imagecolorat($texImg, $x, $y);
							$colors = imagecolorsforindex($texImg, $colorIndex);
							$tex = $tex.$colors['red'].$colors['green'].$colors['blue'].$colors['alpha'];
						}
						usleep(100000);	// don't use up all cpu
					}
					
					break;
				case 'jpg':
				case 'jpeg':
					if(!$texImg = imagecreatefromjpeg($_FILES['texture']['tmp_name']))
						die('<div class="error">Error: Could not create image resource.</div>');
					
					break;
				case 'gif':
					
					break;
				case '':
					if(!$fd = fopen($_FILES['texture']['tmp_name'], 'r'))
						die('<div class="error">opening temp file failed</div>'.$_FILES['texture']['tmp_name']);
					
					ini_set('memory_limit', '40M');
					$tex = fread($fd, $_FILES['texture']['size']);
					fclose($fd);
					
					// RGBA to BGRA
					// for each pixel, swap R with B (36000 = 600*60)
					for($i=0; $i<36000; $i++) {
						$red = $tex[$i*4];
						$tex[$i*4] = $tex[$i*4+2];
						$tex[$i*4+2] = $red;
					}
					
//					$tex = gzcompress($tex);
					
					$texArray = unpack('C*', $tex);
					
					ServerDatabase::getInstance()->updateUserTexture($_SESSION['serverid'], $_SESSION['userid'], $texArray );
					echo 'Texture has been uploaded and set<br/>';
					break;
			}
			
			
			
			
		}
		
	}
}

?>
<h1>Edit Profile</h1>
<form action="?section=profile&amp;action=doedit" <?php if(isset($_GET['action'])&&$_GET['action']=='edit_texture') echo 'enctype="multipart/form-data" '; ?>method="post">
	<table>
		<tr><?php // SERVER Information (not changeable) ?>
			<td class="formitemname"><?php echo $txt['server']; ?>:</td>
			<td>
				<?php
					echo SettingsManager::getInstance()->getServerName($_SESSION['serverid']);
				?>
			</td>
			<td></td>
		</tr>
		<tr><?php // USERNAME ?>
			<td class="formitemname"><?php echo $txt['username']; ?>:</td>
			<td><?php
				if(isset($_GET['action']) && $_GET['action']=='edit_uname'){
					?><input type="text" name="name" value="<?php echo ServerDatabase::getInstance()->getUsername($_SESSION['serverid'], $_SESSION['userid']); ?>" /><?php
				}else{
					echo ServerDatabase::getInstance()->getUsername($_SESSION['serverid'], $_SESSION['userid']);
				} ?></td>
			<td>
				<a href="?section=profile&amp;action=edit_uname" id="profile_uname_edit"<?php if(isset($_GET['action']) && $_GET['action']=='edit_uname'){ echo 'class="hidden"'; } ?>>edit</a>
				<?php if(isset($_GET['action']) && $_GET['action']=='edit_uname'){ echo '<input type="submit" value="update"/>'; } ?><a href="?section=profile&amp;action=doedit_uname" id="profile_uname_update" class="hidden">update</a>
				<a href="?section=profile" id="profile_uname_cancel"<?php if(!isset($_GET['action']) || $_GET['action']!='edit_uname'){ ?> class="hidden"<?php } ?>>cancel</a>
			</td>
		</tr>
		<tr><?php // PASSWORD ?>
			<td class="formitemname"><?php echo $txt['newpassword']; ?>:</td>
			<td><?php if(isset($_GET['action']) && $_GET['action']=='edit_pw'){ ?><input type="text" name="password" id="password" value="" /><?php }else{ echo '<span class="info" title="password is not displayed">*****</span>'; } ?></td>
			<td>
				<a href="?section=profile&amp;action=edit_pw" id="profile_pw_edit"<?php if(isset($_GET['action']) && $_GET['action']=='edit_pw'){ ?> class="hidden"<?php } ?>>edit</a>
				<?php if(isset($_GET['action']) && $_GET['action']=='edit_pw'){ echo '<input type="submit" value="update"/>'; } ?><a id="profile_pw_update" class="hidden">update</a>
				<a href="?section=profile" id="profile_pw_cancel"<?php if(!isset($_GET['action']) || $_GET['action']!='edit_pw'){ ?> class="hidden"<?php } ?>>cancel</a></td>
		</tr>
		<tr><?php // E-MAIL ?>
			<td class="formitemname"><?php echo $txt['newemail']; ?>:</td>
			<td><?php
				if(isset($_GET['action']) && $_GET['action']=='edit_email'){
					?><input type="text" name="email" id="email" value="<?php echo ServerDatabase::getInstance()->getUserEmail($_SESSION['serverid'], $_SESSION['userid']); ?>" /><?php
				}else{
					echo ServerDatabase::getInstance()->getUserEmail($_SESSION['serverid'], $_SESSION['userid']);
				}
			?></td>
			<td>
				<a href="?section=profile&amp;action=edit_email" id="profile_email_edit"<?php if(isset($_GET['action']) && $_GET['action']=='edit_email'){ ?> class="hidden"<?php } ?>>edit</a>
				<?php if(isset($_GET['action']) && $_GET['action']=='edit_email'){ echo '<input type="submit" value="update"/>'; } ?><a id="profile_email_update" class="hidden">update</a>
				<a href="?section=profile" id="profile_email_cancel"<?php if(!isset($_GET['action']) || $_GET['action']!='edit_email'){ ?> class="hidden"<?php } ?>>cancel</a></td>
		</tr>
		<tr><?php // Texture ?>
			<td class="formitemname"><?php echo $txt['texture']; ?>:</td>
			<td><?php
				if(isset($_GET['action']) && $_GET['action']=='edit_texture'){
					?><input type="file" name="texture" id="texture" value="<?php echo ServerDatabase::getInstance()->getUserTexture($_SESSION['serverid'], $_SESSION['userid']); ?>" /><?php
				}else{
					$tex = ServerDatabase::getInstance()->getUserTexture($_SESSION['serverid'], $_SESSION['userid']);
					if(count($tex)==0){
						echo 'no image';
					}else{
						echo 'image set';
					}
//					print_r($tex);
				}
			?></td>
			<td>
				<a href="?section=profile&amp;action=edit_texture" id="profile_texture_edit"<?php if(isset($_GET['action']) && $_GET['action']=='edit_texture'){ ?> class="hidden"<?php } ?>>edit</a>
				<a href="?section=profile&amp;action=doedit&amp;remove_texture" id="profile_texture_remove"<?php if(isset($_GET['action']) && $_GET['action']=='edit_texture'){ ?> class="hidden"<?php } ?>>remove</a>
				<?php if(isset($_GET['action']) && $_GET['action']=='edit_texture'){ echo '<input type="submit" value="update"/>'; } ?><a id="profile_texture_update" class="hidden">update</a>
				<a href="?section=profile" id="profile_texture_cancel"<?php if(!isset($_GET['action']) || $_GET['action']!='edit_texture'){ ?> class="hidden"<?php } ?>>cancel</a>
			</td>
		</tr>
	</table>
	
	<script type="text/javascript">
		$('#profile_uname_edit').click( function(event){
			$('#profile_uname_*').toggle( function(){$(this).removeClass('hidden');}, function(){$(this).addClass('hidden');} );
		} );
	</script>
</form>
<p <?php if(!isset($_GET['action']) || $_GET['action']!='edit_texture'){ ?> class="hidden"<?php } ?>><b>Note:</b> Setting your texture may take several seconds. Please be patient if you upload one.</p>
