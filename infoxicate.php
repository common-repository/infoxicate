<?php 
/**
 * @package infoxicate
 * @author  infoxicate
 * @version 2.0.4
 */
/*
Plugin Name: infoxicate
Plugin URI: http://infoxicate.me/publishers
Description: This plugin adds the infoxicate widget.
Version: 2.0.4
Author: infoxicate
Author URI: http://infoxicate.me/publishers
*/

/**
 * Adds Infoxicate_Widget widget.
 */
class Infoxicate_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'infoxicate_widget', // Base ID
			'Infoxicate_Widget', // Name
			array( 'description' => __( 'Infoxicate Widget', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		echo '
<script type="text/javascript">
var infoxicate_website_id='.get_option('infoxicate_website_id').';
var infoxicate_use_default_css='.($instance['use_default_css'] ? 'true' : 'false').';
</script>
<script id="ifxc-script" src="http://infoxicate.me/widgets/v2"></script>
		';			
			
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['use_default_css'] = strip_tags( $new_instance['use_default_css'] ) ? true : false;
		
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = '';
		}
		if ( isset( $instance[ 'use_default_css' ] ) ) {
			$use_default_css = $instance[ 'use_default_css' ];
		}
		else {
			$use_default_css = false;
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id( 'use_default_css' ); ?>" name="<?php echo $this->get_field_name( 'use_default_css' ); ?>" <?php if(esc_attr( $use_default_css )) echo 'checked' ?>>
		<label for="<?php echo $this->get_field_id( 'use_default_css' ); ?>"><?php _e( 'Use Infoxicate\'s CSS'); ?></label>
		</p>
		<p>
		<?php 
	}

} // class Infoxicate_Widget

// register Infoxicate_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "infoxicate_widget" );' ) );


add_action('admin_menu','infoxicate_admin_menu');


add_filter('the_content', 'infoxicate_in_content',1000); // priority is 1000 so this happens last...

function infoxicate_in_content($content,$sidebar=false)
{
	if(is_single())
	{
		if(get_option('infoxicate_is_widget_in_content_top'))
		{
			$content = '
<script type="text/javascript">
var infoxicate_website_id='.get_option('infoxicate_website_id').';
var infoxicate_use_default_css='.(get_option('infoxicate_use_default_css_in_content') ? 'true' : 'false').';
</script>
<script id="ifxc-script" src="http://infoxicate.me/widgets/v2"></script>
			'.$content;
		
		}
		elseif(get_option('infoxicate_is_widget_in_content_bottom'))
		{
			$content.= '
<script type="text/javascript">
var infoxicate_website_id='.get_option('infoxicate_website_id').';
var infoxicate_use_default_css='.(get_option('infoxicate_use_default_css_in_content') ? 'true' : 'false').';
</script>
<script id="ifxc-script" src="http://infoxicate.me/widgets/v2"></script>
			';
		}
	}
	return $content;
}

function infoxicate_admin_menu()
{
	add_options_page('Infoxicate', 'Infoxicate', 8, 'infoxicate', 'infoxicate_admin_page');
}

function infoxicate_admin_page()
{
	if ($_POST['page_options']=='infoxicate')
	{
		if($_POST['action'] == 'login')
		{
			$response = wp_remote_post('http://infoxicate.me/publishers-api/account/login',array(
				'body'	=> array(
					'email'		=> $_POST['email'],
					'password'	=> $_POST['password'],
				)
			));		
			$decoded = json_decode($response['body']);
			update_option('infoxicate_client_id',$decoded->client_id);
			update_option('infoxicate_client_secret',$decoded->client_secret);

			// now add this website (or fetch the existing one, API will deal with it)
			$response = wp_remote_post('http://infoxicate.me/publishers-api/websites/add',array(
				'body'	=> array(
					'client_id'		=> $decoded->client_id,
					'client_secret'	=> $decoded->client_secret,
					'name'			=> get_bloginfo('name'),
					'url'			=> home_url(),
					'language'		=> substr(get_bloginfo('language'),0,2),
				)
			));		
			$decoded = json_decode($response['body']);
			update_option('infoxicate_website_id',$decoded->publisher_website_id);
			
			$isAuthenticated = true;
		}
		elseif($_POST['action'] == 'signup')
		{
			$response = wp_remote_post('http://infoxicate.me/publishers-api/account/signup',array(
				'body'	=> array(
					'email'		=> $_POST['email'],
					'password'	=> $_POST['password'],
				)
			));		
			$decoded = json_decode($response['body']);
			update_option('infoxicate_client_id',$decoded->client_id);
			update_option('infoxicate_client_secret',$decoded->client_secret);

			// now add this website
			$response = wp_remote_post('http://infoxicate.me/publishers-api/websites/add',array(
				'body'	=> array(
					'client_id'		=> $decoded->client_id,
					'client_secret'	=> $decoded->client_secret,
					'name'			=> get_bloginfo('name'),
					'url'			=> home_url(),
					'language'		=> substr(get_bloginfo('language'),0,2),
				)
			));		
			$decoded = json_decode($response['body']);
			update_option('infoxicate_website_id',$decoded->publisher_website_id);
			
			
			$isAuthenticated = true;
		
		}
		elseif($_POST['action'] == 'add-ping')
		{
			$response = wp_remote_post('http://infoxicate.me/publishers-api/pings/add',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
					'description'			=> $_POST['description'],
				)
			));		
			$decoded = json_decode($response['body']);
			$isAuthenticated = true;
			$isJustAddedPing = true;
			
		}
		elseif($_POST['action'] == 'settings')
		{
			update_option('infoxicate_is_widget_in_content_top',($_POST['is_widget_in_content_top'] ? 1 : 0));
			update_option('infoxicate_is_widget_in_content_bottom',($_POST['is_widget_in_content_bottom'] ? 1 : 0));
			update_option('infoxicate_use_default_css_in_content',($_POST['use_default_css_in_content'] ? 1 : 0));
			$isAuthenticated = true;
			$isJustUpdatedSettings = true;
			
		}
	}
	else
	{
		if(get_option('infoxicate_client_id') && get_option('infoxicate_client_secret'))
		{
			$isAuthenticated = true;
		}
		elseif($_GET['oauth'] == 'true')
		{
			update_option('infoxicate_client_id',$_GET['client_id']);
			update_option('infoxicate_client_secret',$_GET['client_secret']);

			// now add this website
			$response = wp_remote_post('http://infoxicate.me/publishers-api/websites/add',array(
				'body'	=> array(
					'client_id'		=> $_GET['client_id'],
					'client_secret'	=> $_GET['client_secret'],
					'name'			=> get_bloginfo('name'),
					'url'			=> home_url(),
					'language'		=> substr(get_bloginfo('language'),0,2),
				)
			));		
			$decoded = json_decode($response['body']);
			update_option('infoxicate_website_id',$decoded->publisher_website_id);
			
			
			$isAuthenticated = true;
		
		}
		else
		{

			?>
			<div class="wrap">
			<div class="updated below-h2">
				<h2>Infoxicate Connect</h2>
				<h3>To start, your blog must be connected to Infoxicate for Publishers.<br/>If you have an infoxicate publisher account, please login. Otherwise, please signup.</h3>
			</div>

			<div style="background:#eee;padding:10px;margin:10px;text-align:center;">
				<div style="float:left;">
					<p class="submit">
						<a class="button-connector" href="http://infoxicate.me/publishers/oauth/login?redirect-to=<?php echo urlencode(infoxicate_current_url().'&oauth=true'); ?>">Login to Infoxicate for Publishers</a>
					</p>
				</div>
				<div style="float:left;margin: 0px 40px;">
					<p class="submit"><strong>--- OR ---</strong></p>
				</div>
				<div style="float:left;">
					<p class="submit">
						<a class="button-connector" href="http://infoxicate.me/publishers/oauth/signup?redirect-to=<?php echo urlencode(infoxicate_current_url().'&oauth=true'); ?>">Signup to Infoxicate for Publishers</a>
					</p>
				</div>
				<div style="clear:both;">
				</div>
			</div>
			


			</div>
			<?php
		
		
		
		}
	}
	
	if($isAuthenticated)
	{
		if ( isset ( $_GET['tab'] ) ) infoxicate_admin_tabs($_GET['tab']); else infoxicate_admin_tabs('pings');
		if ( isset ( $_GET['tab'] ) ) 
		{
			$tab = $_GET['tab'];
		}
   		else
   		{
   			$tab = 'pings';
   		}
   		
   		switch($tab)
   		{
   		case 'pings':
   		
   		
			$response = wp_remote_post('http://infoxicate.me/publishers-api/websites/pings',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
				)
			));		
			
			$decoded = json_decode($response['body']);

   		
   			?>
   			<div class="wrap">
   			
   			<?php if(count($decoded) ==0){?>
   				<div class="updated below-h2">
   					<h3>To start, please add a ping. A ping is something you'd like to offer your readers to be notified about. For example, "I publish a restaurant review".</h3>
   				</div>
   			<?php }elseif($isJustAddedPing && !infoxicate_is_widget_installed()){ ?>
   				<div class="updated below-h2">
   					<h3>Great! Now what happens is this: your ping has been delivered to our workshop where our craftsmen will develop it. We will let you know once they're done.
   					<br/><br/>Now is a great opportunity to add a widget. The recommended way is to add a widget at the top or bottom of each post. You can do it <a href="?page=infoxicate/infoxicate.php&tab=settings">here</a>.
   					<br/><br/>Go to <a href="/wp-admin/widgets.php">Appearance->Widgets</a> page and drag the Infoxicate widget to your preferred location</h3>
   				</div>
   			<?php }elseif($isJustAddedPing){ ?>
   				<div class="updated below-h2">
   					<h3>Great! Now what happens is this: your ping has been delivered to our workshop where our craftsmen will develop it. We will let you know once they're done.</h3>
   				</div>
   			<?php }elseif(!infoxicate_is_widget_installed()) { ?>
   				<div class="updated below-h2">
   					<h3>Infoxicate Widget is not yet installed which means your users have no access to it. Now is a great opportunity to add a widget. 
   					<br/><br/>The recommended way is to add a widget at the bottom of each post. You can do it <a href="?page=infoxicate/infoxicate.php&tab=settings">here</a>.
   					<br/><br/>You can also add the widget to any sidebar. Go to <a href="/wp-admin/widgets.php">Appearance->Widgets</a> page and drag the Infoxicate widget to your preferred location</h3>
   				</div>
   			<?php } ?>
   			
			<h4>Add Ping</h4>
			<form method="post" action="">
			<?php wp_nonce_field('update-options'); ?>
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Offer my website users to be pinged when...</th>
			<td>
				<textarea rows="3" cols="100" name="description"></textarea>
			</td>
			</tr>
			
			
			
			</table>
			
			<input type="hidden" name="action" value="add-ping" />
			<input type="hidden" name="option_page" value="options" />
			<input type="hidden" name="page_options" value="infoxicate" />
			
			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php _e('Add Ping') ?>" />
			</p>
			
			</form>
   			</div>
   			
   			
   			<h4>My Pings</h4>
			<table class="widefat">
			<thead>
			    <tr>
				    <th>Description</th>
				    <th># Subscribers</th>
			    </tr>
			</thead>
			<tfoot>
			    <tr>
				    <th>Description</th>
				    <th># Subscribers</th>
			    </tr>
			</tfoot>
			<tbody>
			
			<?php
			if(count($decoded) > 0)
			{
				foreach($decoded as $ping)
				{
					?>
					<tr>
						<td><?php echo $ping->event_description ?></td>
						<td><?php echo $ping->no_of_subscribers ?></td>
					</tr>
					<?php
				}
			}
			else
			{
				?>
					<tr>
						<td colspan="2">
							Holy ping! You have no pings yet. Start by creating one using the form above.
						</td>
					</tr>
				<?php
			}   		
	   		?>
			</tbody>
			</table>
   			
   			
   			<?php
   			break;
   		case 'pending':
   		
			$response = wp_remote_post('http://infoxicate.me/publishers-api/websites/pending-pings',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
				)
			));		
			
			$decoded = json_decode($response['body']);

			?>
			<table class="widefat">
			<thead>
			    <tr>
				    <th>Description</th>
				    <th>Pending Since</th>
			    </tr>
			</thead>
			<tfoot>
			    <tr>
				    <th>Description</th>
				    <th>Pending Since</th>
			    </tr>
			</tfoot>
			<tbody>
			
			<?php
			if(count($decoded) > 0)
			{
				foreach($decoded as $ping)
				{
					?>
					<tr>
						<td><?php echo $ping->event_description ?></td>
						<td><?php echo $ping->event_create_date ?></td>
					</tr>
					<?php
				}
			}
			else
			{
				?>
					<tr>
						<td colspan="2">
							No pending pings for now. Either we were fast at work, or you didn't give us any.
						</td>
					</tr>
				<?php
			}   		
   			?>
			</tbody>
			</table>
   			<?php
   		break;
   		case 'stats':
			$response = wp_remote_post('http://infoxicate.me/publishers-api/stats/clicks',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
				)
			));		
			
			$clicks = $response['body'];
			$decoded = json_decode($clicks);
			$hasClicks = (count($decoded) > 1);

			$response = wp_remote_post('http://infoxicate.me/publishers-api/stats/receivers',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
				)
			));		
			
			$receivers = $response['body'];
			$decoded = json_decode($receivers);
			$hasReceivers = (count($decoded) > 1);
			
			$response = wp_remote_post('http://infoxicate.me/publishers-api/stats/subscribers',array(
				'body'	=> array(
					'client_id'				=> get_option('infoxicate_client_id'),
					'client_secret'			=> get_option('infoxicate_client_secret'),
					'publisher_website_id'	=> get_option('infoxicate_website_id'),
				)
			));		
			
			$subscribers = $response['body'];
			$decoded = json_decode($subscribers);
			$hasSubscribers = (count($decoded) > 1);
			
			?>

			<script type="text/javascript" src="https://www.google.com/jsapi"></script>
			<style>
			.chart {width:100%;height:500px;}
			</style>
			<script type="text/javascript">
			
			function drawChart(data,target,title) {
			
				data = google.visualization.arrayToDataTable(data);
				
				var options = {
					title: title,
					colors: ['#3366cc','#90ee90']
				};
			
				var chart = new google.visualization.LineChart(document.getElementById(target));
			
				chart.draw(data, options);
			}
			
			
			
			
			google.load("visualization", "1", {packages:["corechart"]});
			google.setOnLoadCallback(function(){
				<?php if($hasSubscribers){ ?>
					drawChart(<?=$subscribers?>,'chart_div_subscribers','Daily New Subscribers');
				<?php } ?>
				<?php if($hasReceivers){ ?>
					drawChart(<?=$receivers?>,'chart_div_distribution','Daily Distribution');
				<?php } ?>
				<?php if($hasClicks){ ?>
					drawChart(<?=$clicks?>,'chart_div_clicks','Daily Clicks');
				<?php } ?>
			});
			</script>
			
			
			<div class="row-fluid">
				<div class="span12">
					<div class="well">
						<h3>New Subscribers</h3>
						<div id="chart_div_subscribers" class="chart"></div>
					</div>
					<div class="well">
						<h3>Distribution</h3>
						<div id="chart_div_distribution" class="chart"></div>
					</div>
					<div class="well">
						<h3>Clicks</h3>
						<div id="chart_div_clicks" class="chart"></div>
					</div>
				</div>
			</div>

   			
   			<?php
   		break;
		case 'settings':
			if($isJustUpdatedSettings)
			{
				?>
   				<div class="updated below-h2">
   					<h3>Settings saved.</h3>
   				</div>
				<?php
			}
			?>
   			<div class="wrap">
			<form method="post" action="">
			<?php wp_nonce_field('update-options'); ?>
			<ul>
		        <li>
		        	<input name="is_widget_in_content_top" type="checkbox" <?php if(get_option('infoxicate_is_widget_in_content_top')) echo 'checked'; ?> />
		        	<label for="is_widget_in_content_top">Add infoxicate widget to the top of each post (best option)</label>
		        </li>    
		        <li>
		        	<input name="is_widget_in_content_bottom" type="checkbox" <?php if(get_option('infoxicate_is_widget_in_content_bottom')) echo 'checked'; ?> />
		        	<label for="is_widget_in_content_bottom">Add infoxicate widget to the bottom of each post (better than on a sidebar)</label>
		        </li>    
		        <li>
		        	<input name="use_default_css_in_content" type="checkbox" <?php if(get_option('infoxicate_use_default_css_in_content')) echo 'checked'; ?> />
		        	<label for="use_default_css_in_content">Use infoxicate css on widget if placed in content</label>
		        </li>    
		             
		    </ul>			
			<input type="hidden" name="action" value="settings" />
			<input type="hidden" name="option_page" value="options" />
			<input type="hidden" name="page_options" value="infoxicate" />
			
			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save') ?>" />
			</p>
			
			</form>
   			</div>
			<?php
		break; 
   		}
		
	}
	
}

function infoxicate_admin_tabs( $current = 'pings' ) {
    $tabs = array( 'pings' => 'My Pings', 'pending' => 'Pending Pings', 'stats' => 'Stats', 'settings' => 'Settings' );
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=infoxicate&tab=$tab'>$name</a>";

    }
    echo '</h2>';
}

function infoxicate_is_widget_installed()
{
	$isInstalled = false;
	
	if(get_option('infoxicate_is_widget_in_content_bottom'))
	{
		$isInstalled = true;
	}
	else
	{
		foreach(wp_get_sidebars_widgets() as $sidebar)
		{
			foreach($sidebar as $widget)
			{
				if(strpos($widget,'infoxicate_widget') !== false)
				{
					$isInstalled = true;
					break;
				}
			}
		}
	}	
	return $isInstalled;
}

function infoxicate_current_url() 
{
     $pageURL = 'http';
     if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
     $pageURL .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
     } else {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     }
     return $pageURL;
}

function infoxicate_admin_styles() {
	wp_enqueue_style( 'infoxicate', plugins_url( basename( dirname( __FILE__ ) ) . '/infoxicate.css' ));
}
add_action( 'admin_head', 'infoxicate_admin_styles' );