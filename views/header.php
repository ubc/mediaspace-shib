<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<title><?php echo isset($entry) ? $entry->name." - ".Config::get('title') : Config::get('title'); ?></title>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $base_url; ?>assets/css/kmh.css" />
		<!--[if IE]>
			<script type="text/javascript">
			 window.onerror = function() { return true; }
			document.createElement("time");
		</script>
	<![endif]-->
		<script type="text/javascript">
			var kdp = false;
			var kmh = {
				isKdpEmpty : function(show_alert) {
					var is_empty = (kdp) ? kdp.evaluate("{playerStatusProxy.kdpStatus}") : false;
					// kdpStatus returns null / "empty" / "ready"
					if(is_empty) {
						kmh.kdp_ready = true;
					}
					else if(show_alert) {
						var msg = "Player not ready yet.";
						msg = kmh.localize ? kmh.localize(msg) : (playlists ? playlists.localize(msg) : msg);
						alert(msg);
					}
					return is_empty;
				},
				kdp_empty	: false,
				doFirstLoad : function() {
					this.kdp_empty = true;
				}
			}

			function jsCallbackReady(player_id) {
				kdp = document.getElementById(player_id); // creates globally scoped object
				if(kmh.isKdpEmpty(false)) {
					kmh.doFirstLoad();
				}
				else {
					kdp.addJsListener("kdpEmpty", "kmh.doFirstLoad");
				}
				kdp.addJsListener("entryReady", "kmh.updateMediaInfoAndRelated");
				kdp.addJsListener("playerPlayed", "kmh.closeNavOnPlay");
				if(kmh.auto_play_on_load == 0) {
					kdp.addJsListener("mediaReady", "kmh.autoPlayAfterFirstLoad");
				}
				if(kmh.auto_continue) {
					kdp.addJsListener("playerPlayEnd", "kmh.loadNextVideo");
				}
				if(kmh.admin_role) { //allowembed
					kdp.addJsListener("entryReady", "kmh.embedCodeForEntry"); // redundent - covered by tab onclick
					kdp.addJsListener("entryReady", "kmh.getEntryPlaylists");
				}
			}
		</script>
	</head>

	<body>
		<div id="kwrap" class="kwrap <?php echo $pageType; ?>" onclick="return false;">
			<div id="kheader">
				<span id="branding">
				<?php // move to action
				$linkUrl = Config::get("logoLink");
				$isExternalLink = (strrpos($linkUrl, "http") === false) ? false : true;
				if($linkUrl == "home")
					$linkUrl = HttpHelper::getBaseURL();
				?>
					<!--php switch linkUrl
						case "home"
						case "false"
						default
					-->
				<?php if($linkUrl) { ?>
					<a href="<?php echo $linkUrl; ?>" <?php if($isExternalLink) { ?>  target="_blank"<?php } ?>>
				<?php } if(Config::get("logoImage")) { ?>
						<img src="<?php echo $base_url; ?>assets/images/<?php echo Config::get("logoImage"); ?>" alt="<?php echo Config::get("logoAltText"); ?>" />
				<?php }	else {
					  echo '<span>' . Config::get("logoAltText") . '</span>';
					  }
					  if($linkUrl) { ?>
					</a>
				<?php } ?>
				</span>
<?php
	$action = ($role == 'anonymous') ? 'login' : 'logout';
	if($action == 'login')
	{
		# changed by Compass
		if(method_exists($authClass, 'getLoginURL')) 
		{
			$href = call_user_func(array($authClass, 'getLoginURL'));
		} else {
			$href = Config::get("loginUrl") . '?action=none';
		}
		if(strpos($href,'http') === false)
		{
			$href = HttpHelper::getBaseURL() . $href;
		}
		$actionHtml = ' <a href="' . $href . '" ><img src="https://www.auth.cwl.ubc.ca/CWL_login_button.gif" width="76" height="25" alt="CWL Login" border="0"></a>';
		# end
	}
	else
	{
		$href = HttpHelper::getBaseURL() . 'logout.php';
		# changed by Compass
		$actionHtml = $username.' (<a href="' . $href . '" >' . KalturaMediaSpaceLocale::localize($action) . '</a>)';
		# end
	}
//	$actionHtml = ' (<a href="' . $href . '" id="klogout">' . KalturaMediaSpaceLocale::localize($action) . '</a>)';
?>
				<div>
					<ul>
						<li><?php echo $actionHtml; ?></li>

<?php
	/* @todo: this is hacky. should be handled through $actions array in index ? */
	$allowedRoles = array(Config::get('unmoderatedAdminRole'),Config::get('adminRole'),Config::get('privateOnlyRole'),'anonymous');
	if(in_array($role, $allowedRoles)): ?>
						<li>
	<?php if($isMyMedia): ?>
							<strong>
	<?php else: ?>
							<a id="kmy_media" href="<?php echo HttpHelper::getBaseURL(); ?>index.php/action/my-media">
	<?php endif; ?>
								<?php echo KalturaMediaSpaceLocale::localize("My Media"); ?>
	<?php if(!$isMyMedia): ?>
							</a>
	<?php else: ?>
							</strong>
	<?php endif; ?>
						</li>
						<li>
	<?php if($isPlaylistPage): ?>
							<strong>
	<?php else: ?>
							<a id="kplaylist_page" href="<?php echo HttpHelper::getBaseURL(); ?>index.php/action/playlist-page">
	<?php endif; ?>
								<?php echo KalturaMediaSpaceLocale::localize("My Playlists"); ?>
	<?php if(!$isPlaylistPage): ?>
							</a>
	<?php else: ?>
							</strong>
	<?php endif; ?>
						</li>
<?php endif; ?>

	<?php if(Config::getArray("helpLinks")): ?>
						<li id="help_menu">
							<a href="#" id="help_menu_trigger"><?php echo KalturaMediaSpaceLocale::localize("Help"); ?></a>
							<ul>
		<?php foreach(Config::getArray("helpLinks") as $details): ?>
								<li><a href="<?php echo $details['value']; ?>" target="_blank"><?php echo $details['name']; ?></a></li>
		<?php endforeach; ?>
							</ul>
						</li>
	<?php endif; ?>
					</ul>
					<form id="main_search" action="">
						<input type="text" name="search_for" id="search_for" value="<?php echo KalturaMediaSpaceLocale::localize("Search all videos"); ?>" />
						<button type="submit" id="do_main_search"><?php echo KalturaMediaSpaceLocale::localize("Search"); ?></button>
						<button type="button" class="reset hidden" id="cancel_main_search"><?php echo KalturaMediaSpaceLocale::localize("Reset"); ?></button>
					</form>
				</div><!--div-->
			</div><!--header-->
			<div id="main_nav">
				<div id="scroll_pane">
					<ul>
						<?php echo $main_cats_html; ?>
					</ul>
				</div>
				<i></i>
				<span>
					<a href="#" id="main_nav_prev">previous</a><!-- @todo: ids?-->
					<a href="#" id="main_nav_next">next</a>
				</span>
				<b></b>
			</div>
