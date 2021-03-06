<?php

if (!class_exists('youtube_w_analytics')) :

	class youtube_w_analytics {
		
		//declaring different variables needed for class
		var $sessions_needed = "";
		var $definitions = array();
		var $error = array();
		var $access_level = "";
		var $uatag = "";
		var $pvparams = array();
		var $videos = array();
		var $variables = array();
		var $video_table_name = "";

		//var $self = array();
		
		function __construct() {
			

			//place to set initial settings for plugin
			$this->sessions_needed = false;	
			$this->access_level = "manage_options";
			$this->uatag = "ga";

			$this->variables['video_table_name'] ='youtube_w_analytics';
			
			$this->video_table_name = 'youtube_w_analytics';

			// https://developers.google.com/youtube/player_parameters
			// https://developers.google.com/youtube/js_api_reference
			$this->pvparams = array ( 
									// 'autohide' => '',				
									// 'autoplay' => '',		
									// 'cc_load_policy' => '',	
									// 'color' => '',			
									// 'controls' => '',		
									// 'disablekb' => '',
									// 'enablejsapi' => '',
									// 'end' => '',
									// 'fs' => '',
									// 'hl' => '',
									// 'iv_load_policy' => '',
									// 'list' => '',
									// 'listType' => '',
									// 'loop' => '',
									'modestbranding' => false,	
									// 'origin' => '',
									// 'playerapiid' => '',		
									// 'playlist' => '',			
									'rel' => '0',				
									// 'showinfo' => '',			
									// 'start' => '',				
									'theme' => 'dark',			
									);
			
			register_activation_hook(__FILE__, array(&$this,'activate'));
			register_deactivation_hook(__FILE__,array(&$this,'deactivate'));

			add_action('init', array(&$this, 'init'));
			
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));

		}

		function activate() {
			$youtube_w_analytics = new youtube_w_analytics();

			$tableName = $youtube_w_analytics->video_table_name;

			$tableSql = "
						`id` int(11) NOT NULL AUTO_INCREMENT,
					 	`youtubeid` varchar(64) NOT NULL,
					 	`videovariables` text NOT NULL,
					 	`timestampexample` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					 	UNIQUE id ( `id` )
						";

			self::create_table($tableName,$tableSql);
			
		}
		
		function deactivate() {
			$youtube_w_analytics = new youtube_w_analytics();

			$tableName = $youtube_w_analytics->video_table_name;
			self::drop_table($this->$tableName);
		}
		function init() {
			if ($this->sessions_needed) :
				if (!session_id()) :
					session_start();
				endif;
			endif;
			
			$this->init_settings();				
			
		}
		
		function define_variable($variablename, $variabledata) {
			define($variablename, $variabledata);
			$this->definitions[$variablename] = $variabledata;
			return true;
		}
		
		function admin_init() {
			//customized initilization for the admin area only
			$this->init_settings();
			
		}
		
		function init_settings() {
			//customized init settings for entire program backend and front end
			add_action('wp_head', array(&$this, 'header') );
			add_action('wp_footer', array(&$this, 'footer') );
		}

		function footer() {
			echo "<!--Doing Footer-->\n";
		}

		function header() {
			echo "<!--Doing Header-->\n";
		}
		
		function update_options() {
			
		}
		
		function add_menu() { 
			/* This menu choice adds the menu choice under the main menu settings */
			//add_options_page('Plugin Ba Settings', 'Incon Tracking', 'manage_options', 'incon_tracking_settings', array(&$this, 'incon_tracking_settings_page'));
			
			/* This menu choice adds it as a main menu allowing for sub pages */
			// Add the top-level admin menu to backend of WordPress
			$page_title = 'YouTube with Analytics Tracking Settings';
			$menu_title = 'YouTube w/ UAT';
			$capability = $this->access_level; //'manage_options';
			$menu_slug = 'ywa-settings';
			$menu_function = array(&$this, 'settings_page');
			add_menu_page($page_title, $menu_title, $capability, $menu_slug, $menu_function);
		 
			// Add submenu page with same slug as parent to ensure no duplicates
			$sub_menu_title = 'Settings';
			add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $menu_function);
		 
			// Now add the submenu page for Help
			$submenu_page_title = 'Youtube Parameters Help Page';
			$submenu_title = 'YouTube Parameters Help';
			$submenu_slug = 'ywa-second';
			$submenu_function = array(&$this, 'ytp_help_page');
			add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
			
		}

		function settings_page() {
			$this->check_user();
			global $wpdb;
			//if passed then display following code to user
?>
    <!-- Create a header in the default WordPress 'wrap' container -->
    <div class="wrap">
<?php			
			//$this->add_error_msg("Test Error Message");
			echo "<h1>YouTube with Analytics Tracking Settings Page</h2>";
			$this->disp_errors();

			$tableName = $this->video_table_name;

			$wpdb->get_results( 'SELECT * FROM ' . $tableName . ' ORDER BY id ASC' );
			$vidcount = $wpdb->num_rows;
			if ($vidcount > 0) {


			} else {
				echo "<h2>Currently no videos setup in the system.";
			}
?>
</div>
<?php
			
		}
		
		/* 
		 * The check_user function checks to see if they have sufficient permissions
		 * and if not then displays error message and does a wp_die.
		 */
		function check_user($user_can) {
			if ($user_can == '') $user_can = $this->access_level;
			if (!current_user_can($user_can)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));	
			}
			return true;
		}
		
		function create_table($tablename, $variableSql) {
			/*		Sample $variableSql data
						
					  Automatically adds database prefix to this database table

					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `varcharexample` varchar(64) NOT NULL,
					  `textexample` text NOT NULL,
					  `intexample` int(11) NOT NULL DEFAULT '',
					  `timestampexample` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  UNIQUE ( `id` )
			*/
			global $wpdb;
			if ($variableSql != '') {
				$sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix . $tablename."` ( ".$variableSql.");";
				error_log($sql);
				$wpdb->query($sql);
				return true;
			}
			
			return false;
		}
		
		function drop_table($tablename) {
			global $wpdb;
			$sql = 	"DROP TABLE IF EXISTS " . $wpdb->prefix . $tablename;
			if ($wpdb->query($sql)) 
				return true;			
			return false;	
		}

		function disp_errors() {
			$displayText = '';
			if (count($this->error) > 0 ) {
				foreach ($this->error as $text) {
					$displayText .= $text . "<br>";
				}
				echo "<div style='color:#ff0000;font-weight:bold;'>".$displayText."</div>";
				return true;
			}
			return false;
		}
		
		function add_error_msg($msg) {
			$this->error[] = $msg;
		}
		
		function display_header_code() {
			$videos = $this->videos;
			?>
			<script>
				 var tag = document.createElement('script');
				 tag.src = "http://www.youtube.com/player_api";
				 var firstScriptTag = document.getElementsByTagName('script')[0];
				 firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
				<?php
				foreach ($videos as $video) {
					?>
				 var player<?=$video['id']?>;
				 var lastAction<?=$video['id']?> = '';
				 <?php
				}
				?>
				 function onYouTubePlayerAPIReady() {
				 	$videos = $this->videos;
				<?php
				foreach ($videos as $video) {
					?>
					 player<?=$video['id']?> = new YT.Player('player<?=$video['id']?>', {
						 playerVars: {
							 <?php
							 foreach ($pvparams as $pname => $pvalue) {
								 if ($pvalue != '') echo $pname.": '".$pvalue."',\n";
							 }
							 ?>
						 },
						 height: '<?=$video['height']?>',
						 width: '<?=$video['width']?>',
						 videoId: '<?=$video['youtubeid']?>',
						 events: {
							 'onStateChange': onPlayerStateChange<?=$video['id']?>
						 }
					 });
					 <?php
				}
				?>
				 }
			</script>
            <?php
		}
		
		function display_player_code($videonum, $videotitle) {
			
			?>
<div id="player<?=$videonum?>"></div>
     <script>
		function onPlayerStateChange<?=$videonum?>(event) {
             switch (event.data) {
                 case YT.PlayerState.PLAYING:
						<?=$this->uatag?>('send', 'event', '<?=$videotitle?>', 'started');
                     break;
                 case YT.PlayerState.ENDED:
						<?=$this->uatag?>ga('send', 'event', '<?=$videotitle?>', 'completed');
                     break;
                 case YT.PlayerState.PAUSED:
                     if (lastAction != 'paused') {
						<?=$this->uatag?>ga('send', 'event', '<?=$videotitle?>', 'paused');
                     } else {
                         lastAction = 'paused';
                     }
                     break;
             }
         }
	</script>
                <?php	
		}

		function ytp_help_page() {
			$this->check_user();
			//if passed then display following code to user
			?>
				<!-- Create a header in the default WordPress 'wrap' container -->
				<div class="wrap">
			<?php			
			
						echo "<h1>YouTube Parameters Help Page</h2>";
						$this->disp_errors();
			?>
            <style>
			dt {
				font-weight:bold;
			}
			</style>
			<h3 id="parameter-subheader">All YouTube player parameters available for plugin</h3>
			<dl>
			  <?php /* ?><?php /* ?><dt id="autohide">autohide (supported players: AS3, HTML5)</dt>
			  <dd id="autohide-definition">Values: 2 (default), 1, and 0. This parameter indicates whether the video controls will automatically hide after a video begins playing. The default behavior (autohide=2) is for the video progress bar to fade out while the player controls (play button, volume control, etc.) remain visible.<br />
				<br />
				<ul>
				  <li>If this parameter is set to 1, then the video progress bar and the player controls will slide out of view a couple of seconds after the video starts playing. They will only reappear if the user moves her mouse over the video player or presses a key on her keyboard.</li>
				  <li>If this parameter is set to 0, the video progress bar and the video player controls will be visible throughout the video and in fullscreen.</li>
				</ul>
			  </dd><?php */ ?>
			  <?php /* ?><dt id="autoplay">autoplay (supported players: AS3, HTML5)</dt>
			  <dd id="autoplay-definition">Values: 0 or 1. Default is 0. Sets whether or not the initial video will autoplay when the player loads.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="cc_load_policy">cc_load_policy (supported players: AS3, HTML5)</dt>
			  <dd id="cc_load_policy-definition">Values: 1. Default is based on user preference. Setting to 1 will cause closed captions to be shown by default, even if the user has turned captions off.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="color">color (supported players: AS3, HTML5)</dt>
			  <dd id="color-definition">This parameter specifies the color that will be used in the player's video progress bar to highlight the amount of the video that the viewer has already seen. Valid parameter values are red and white, and, by default, the player will use the color red in the video progress bar. See the <a target="_blank" href="http://apiblog.youtube.com/2011/08/coming-soon-dark-player-for-embeds.html" spfieldtype="null" spsourceindex="373">YouTube API blog</a> for more information about color options.<br />
				<br />
				<strong>Note:</strong> Setting the color parameter to white will disable the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#modestbranding" spfieldtype="null" spsourceindex="374">modestbranding</a> option.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="controls">controls (supported players: AS3, HTML5)</dt>
			  <dd id="controls-definition">Values: 0, 1, or 2. Default is 1. This parameter indicates whether the video player controls will display. For IFrame embeds that load a Flash player, it also defines when the controls display in the player as well as when the player will load:
				<ul>
				  <li>controls=0 – Player controls do not display in the player. For IFrame embeds, the Flash player loads immediately.</li>
				  <li>controls=1 – Player controls display in the player. For IFrame embeds, the controls display immediately and the Flash player also loads immediately.</li>
				  <li>controls=2 – Player controls display in the player. For IFrame embeds, the controls display and the Flash player loads after the user initiates the video playback.</li>
				</ul>
				<strong>Note:</strong> The parameter values 1 and 2 are intended to provide an identical user experience, but controls=2 provides a performance improvement over controls=1 for IFrame embeds. Currently, the two values still produce some visual differences in the player, such as the video title's font size. However, when the difference between the two values becomes completely transparent to the user, the default parameter value may change from 1 to 2.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="disablekb">disablekb (supported players: AS3, HTML5)</dt>
			  <dd id="disablekb-definition">Values: 0 or 1. Default is 0. Setting to 1 will disable the player keyboard controls. Keyboard controls are as follows: <br />
				Spacebar: Play / Pause <br />
				Arrow Left: Jump back 10% in the current video <br />
				Arrow Right: Jump ahead 10% in the current video <br />
				Arrow Up: Volume up <br />
				Arrow Down: Volume Down <br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="enablejsapi">enablejsapi (supported players: AS3, HTML5)</dt>
			  <dd id="enablejsapi-definition">Values: 0 or 1. Default is 0. Setting this to 1 will enable the Javascript API. For more information on the Javascript API and how to use it, see the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference" spfieldtype="null" spsourceindex="375">JavaScript API documentation</a>.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="end">end (supported players: AS3, HTML5)</dt>
			  <dd id="start-definition">Values: A positive integer. This parameter specifies the time, measured in seconds from the start of the video, when the player should stop playing the video. Note that the time is measured from the beginning of the video and not from either the value of thestart player parameter or the startSeconds parameter, which is used in YouTube Player API functions for loading or queueing a video.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="fs">fs (supported players: AS3, HTML5)</dt>
			  <dd id="fs-definition">Values: 0 or 1. The default value is 1, which causes the fullscreen button to display. Setting this parameter to 0 prevents the fullscreen button from displaying.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="hl">hl (supported players: AS3, HTML5)</dt>
			  <dd id="fs-definition">Sets the player's interface language. The parameter value is an <a target="_blank" href="http://www.loc.gov/standards/iso639-2/php/code_list.php" spfieldtype="null" spsourceindex="376">ISO 639-1 two-letter language code</a>, though other language input codes, such as IETF language tags (BCP 47) may also be handled properly.<br />
				<br />
				The interface language is used for tooltips in the player and also affects the default caption track. Note that YouTube might select a different caption track language for a particular user based on the user's individual language preferences and the availability of caption tracks.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="iv_load_policy">iv_load_policy (supported players: AS3, HTML5)</dt>
			  <dd id="iv_load_policy-definition">Values: 1 or 3. Default is 1. Setting to 1 will cause video annotations to be shown by default, whereas setting to 3 will cause video annotations to not be shown by default.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="list">list (supported players: AS3, HTML5)</dt>
			  <dd id="list-definition">The list parameter, in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="377">listType</a> parameter, identifies the content that will load in the player.<br />
				<ul>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="378">listType</a> parameter value is search, then the list parameter value specifies the search query.</li>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="379">listType</a> parameter value is user_uploads, then the list parameter value identifies the YouTube channel whose uploaded videos will be loaded.</li>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="380">listType</a> parameter value is playlist, then the list parameter value specifies a YouTube playlist ID. In the parameter value, you need to prepend the playlist ID with the letters PL as shown in the example below.<br />
					<pre>http://www.youtube.com/embed?listType=playlist&amp;list=PLC77007E23FF423C6</pre>
				  </li>
				</ul>
				<strong>Note:</strong> If you specify values for the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="381">list</a> and listType parameters, the IFrame embed URL does not need to specify a video ID.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="listType">listType (supported players: AS3, HTML5)</dt>
			  <dd id="listType-definition">The listType parameter, in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="382">list</a> parameter, identifies the content that will load in the player. Valid parameter values are playlist, search, and user_uploads.<br />
				<br />
				If you specify values for the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="383">list</a> and listType parameters, the IFrame embed URL does not need to specify a video ID.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="loop">loop (supported players: AS3, HTML5)</dt>
			  <dd id="loop-definition">Values: 0 or 1. Default is 0. In the case of a single video player, a setting of 1 will cause the player to play the initial video again and again. In the case of a playlist player (or custom player), the player will play the entire playlist and then start again at the first video.<br />
				<br />
				<strong>Note:</strong> This parameter has limited support in the AS3 player and in IFrame embeds, which could load either the AS3 or HTML5 player. Currently, the loop parameter only works in the AS3 player when used in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#playlist" spfieldtype="null" spsourceindex="384">playlist</a> parameter. To loop a single video, set the loop parameter value to 1 and set the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#playlist" spfieldtype="null" spsourceindex="385">playlist</a> parameter value to the same video ID already specified in the Player API URL:<br />
				<pre>http://www.youtube.com/v/<strong>VIDEO_ID</strong>?version=3&amp;loop=1&amp;playlist=<strong>VIDEO_ID</strong></pre>
			  </dd><?php */ ?>
			  <?php  ?><dt id="modestbranding">modestbranding (supported players: AS3, HTML5)</dt>
			  <dd id="modestbranding-definition">This parameter lets you use a YouTube player that does not show a YouTube logo. Set the parameter value to 1 to prevent the YouTube logo from displaying in the control bar. Note that a small YouTube text label will still display in the upper-right corner of a paused video when the user's mouse pointer hovers over the player.<br />
				<br />
			  </dd><?php  ?>
			  <?php /* ?><dt id="origin">origin (supported players: AS3, HTML5)</dt>
			  <dd id="origin-definition">This parameter provides an extra security measure for the IFrame API and is only supported for IFrame embeds. If you are using the IFrame API, which means you are setting the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#enablejsapi" spfieldtype="null" spsourceindex="386">enablejsapi</a> parameter value to 1, you should always specify your domain as the origin parameter value.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playerapiid">playerapiid (supported players: AS3)</dt>
			  <dd id="playerapiid-definition">Value can be any alphanumeric string. This setting is used in conjunction with the JavaScript API. See the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference" spfieldtype="null" spsourceindex="387">JavaScript API documentation</a> for details.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playlist">playlist (supported players: AS3, HTML5)</dt>
			  <dd id="playlist-definition">Value is a comma-separated list of video IDs to play. If you specify a value, the first video that plays will be the VIDEO_ID specified in the URL path, and the videos specified in the playlist parameter will play thereafter.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playsinline">playsinline (supported players: HTML5)</dt>
			  <dd id="playlist-definition">This parameter controls whether videos play inline or fullscreen in an HTML5 player on iOS. Valid values are:<br />
				<ul>
				  <li>0: This value causes fullscreen playback. This is currently the default value, though the default is subject to change.</li>
				  <li>1: This value causes inline playback for UIWebViews created with the allowsInlineMediaPlayback property set toTRUE.</li>
				</ul>
			  </dd><?php */ ?>
			  <dt id="rel">rel (supported players: AS3, HTML5)</dt>
			  <dd id="rel-definition">Values: 0 or 1. Default is 1. This parameter indicates whether the player should show related videos when playback of the initial video ends.<br />
				<br />
			  </dd>
			  <?php /* ?><dt id="showinfo">showinfo (supported players: AS3, HTML5)</dt>
			  <dd id="showinfo-definition">Values: 0 or 1. The parameter's default value is 1. If you set the parameter value to 0, then the player will not display information like the video title and uploader before the video starts playing.<br />
				<br />
				If the player is loading a playlist, and you explicitly set the parameter value to 1, then, upon loading, the player will also display thumbnail images for the videos in the playlist. Note that this functionality is only supported for the AS3 player since that is the only player that can load a playlist.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="start">start (supported players: AS3, HTML5)</dt>
			  <dd id="start-definition">Values: A positive integer. This parameter causes the player to begin playing the video at the given number of seconds from the start of the video. Note that similar to the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference#seekTo" spfieldtype="null" spsourceindex="388">seekTo</a> function, the player will look for the closest keyframe to the time you specify. This means that sometimes the play head may seek to just before the requested time, usually no more than around two seconds.<br />
				<br />
			  </dd><?php */ ?>
			  <dt id="theme">theme (supported players: AS3, HTML5)</dt>
			  <dd id="theme-definition">This parameter indicates whether the embedded player will display player controls (like a play button or volume control) within a dark or light control bar. Valid parameter values are dark and light, and, by default, the player will display player controls using the dark theme. See the <a target="_blank" href="http://apiblog.youtube.com/2011/08/coming-soon-dark-player-for-embeds.html" spfieldtype="null" spsourceindex="389">YouTube API blog</a> for more information about the dark and light themes.</dd>
			</dl>
			
			</div>
			<?php
			
		}
		
		
	}


endif;