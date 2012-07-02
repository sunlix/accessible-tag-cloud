<?php
/**
 * Plugin Name: Accessible Tag Cloud
 * Plugin URI: https://github.com/sunlix/accessible-tag-cloud
 * Description: Accessible Tag Cloud Widget
 * Version: 1.4
 * Author: Toon Van de Putte | Sven Schüring
 * Author URI: http://www.automaton.be/ | http://www.sunlix.de
 *
 * Copyright (c) 2012 Sven Schüring
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action('widgets_init', 'acc_tag_cloud_load_widgets');

/**
 * Register our widget.
 *
 * @since 0.1
 */
function acc_tag_cloud_load_widgets ()
{
	register_widget('Acc_tag_cloud');
}


/*
 * function to add css to the hidden parts in the tag cloud
 */
function acc_tag_cloud_hidecss ($styling = 'default', $code = 'class="acc_tag_cloud_screenreader"')
{
    if ($styling == 'inline')
    {
    	return 'style="height:0;left:-9000px;position:absolute;width:0;"';
    }
    elseif ($styling == 'owncode')
    {
        return $code;
    }
    else
    {
        return 'class="acc_tag_cloud_screenreader"';
    }
}
/*
 * function to add default screenreader class to head
 */
function acc_tag_cloud_defaultcss ()
{
   $strHtml = '<style type="text/css">
      .acc_tag_cloud_screenreader {
        height:0;
        left:-9000px;
        position:absolute;
        width:0;
      }
   </style>';

   echo $strHtml;
}
add_action('wp_head', 'acc_tag_cloud_defaultcss');

/**
 * Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class Acc_tag_cloud extends WP_Widget
{
	/**
	 * Widget setup.
	 */
	function Acc_tag_cloud ()
	{
		/* Widget settings. */
		$widget_ops = array(
			'classname'		=> 'widget_acc_tag_cloud',
			'description'	=> __('Display an accessible tag cloud in the sidebar', 'acc-tag-cloud')
		);

		/* Widget control settings. */
		$control_ops = array(
			'width'		=> 300,
			'height'	=> 350,
			'id_base'	=> 'acc-tag-cloud-widget'
		);

		/* Create the widget. */
		$this->WP_Widget('acc-tag-cloud-widget', __('Accessible Tag Cloud Widget', 'acc-tag-cloud'), $widget_ops, $control_ops);
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget ($args, $instance)
	{
		extract($args);

		/* Our variables from the widget settings. */
		$title		= apply_filters('widget_title', $instance['title']);
		$smallest	= $instance['smallest'];
		$biggest	= $instance['biggest'];
		$unit		= $instance['unit'];
		$taxonomy	= $instance['taxonomy'];
		$count		= $instance['count'];

		if (!$count)
		{
			$count=10;
		}

		if (!empty($instance['title']))
		{
			$title = $instance['title'];
		}
		else
		{
			if ('post_tag' == $current_taxonomy)
			{
				$title = __('Tags');
			}
			else
			{
				$tax	= get_taxonomy($current_taxonomy);
				$title	= $tax->label;
			}
		}

		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		$terms = get_terms($taxonomy, array(
			'number'	=> $count,
			'orderby'	=> 'count',
			'order'		=> 'DESC'
		));

		// wild cloud
		asort($terms);

		$sizerange		= $biggest - $smallest;
		$lowestcount	= $terms[0]->count;
		$highestcount	= 0;

		foreach ($terms as $term)
		{
			if ($term->count < $lowestcount)
			{
				$lowestcount = $term->count;
			}
			elseif ($term->count > $highestcount)
			{
				$highestcount = $term->count;
			}
		}

		$countdiff = $highestcount - $lowestcount;

		if ($countdiff == 0)
		{
			foreach ($terms as $term)
			{
				$term->size = $smallest + ($sizerange / 2);
			}
		}
		else
		{
			foreach ($terms as $term)
			{
				$term->size = $smallest + (($term->count - $lowestcount) / $countdiff) * $sizerange;
			}
		}

		$tagcloud = '';

		foreach ($terms as $term)
		{
			$tagcloud .= '<li style="font-size:'. $term->size . $unit .';"><a href="'. get_term_link($term, $taxonomy) .'">';
			$tagcloud .= '<span class="hideme">Zum Schlagwort &#8216;</span>'. $term->name .'<span class="hideme">&#8217; gibt es '. $term->count .' Beiträge</span></a></li> ';
		}
		// Before widget (defined by themes).
		echo $before_widget;

		if ($title)
		{
			echo $before_title . $title . $after_title;
		}

		echo('<ul class="tagcloud tagcloud-accessible tagcloud-tax-'. $taxonomy .'">');
		echo($tagcloud);
		echo('</ul>');

		// After widget (defined by themes).
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		/* Strip tags to remove HTML (important for text inputs). */
		$instance['title']		= strip_tags($new_instance['title']);
		$instance['smallest']	= strip_tags($new_instance['smallest']);
		$instance['biggest']	= strip_tags($new_instance['biggest']);
		$instance['taxonomy']	= strip_tags($new_instance['taxonomy']);
		$instance['unit']		= strip_tags($new_instance['unit']);
		$instance['count']		= strip_tags($new_instance['count']);

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function _get_current_taxonomy($instance)
	{
		if (!empty($instance['taxonomy']) && is_taxonomy($instance['taxonomy']))
		{
			return $instance['taxonomy'];
		}

		return 'post_tag';
	}

	function form( $instance )
	{

?>
	<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('smallest'); ?>"><?php _e('Smallest:') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('smallest'); ?>" name="<?php echo $this->get_field_name('smallest'); ?>" value="<?php echo $instance['smallest']; ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('biggest'); ?>"><?php _e('Biggest:') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('biggest'); ?>" name="<?php echo $this->get_field_name('biggest'); ?>" value="<?php echo $instance['biggest']; ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of terms:') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" value="<?php echo $instance['count']; ?>" />
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('unit'); ?>"><?php _e('Size unit:') ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('unit'); ?>" name="<?php echo $this->get_field_name('unit'); ?>">
			<option value="em" <?php selected('em', $instance['unit']) ?>>em</option>
			<option value="percentage" <?php selected('percentage', $instance['unit']) ?>>percentage</option>
			<option value="px" <?php selected('px', $instance['unit']) ?>>px</option>
		</select>
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:') ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
		<?php
		foreach (get_object_taxonomies('post') as $taxonomy) :
			$tax = get_taxonomy($taxonomy);

			if (!$tax->show_tagcloud || empty($tax->label))
			{
				continue;
			}
		?>
			<option value="<?php echo esc_attr($taxonomy) ?>" <?php selected(esc_attr($taxonomy), $instance['taxonomy']) ?>><?php echo $tax->label ?></option>
		<?php
		endforeach;
		?>
		</select>
	</p>
<?php
	}
}

?>
