<?php
/* SVN FILE: $Id: default.ctp 5318 2007-06-20 09:01:21Z phpnut $ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.templates.pages
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision: 5318 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2007-06-20 04:01:21 -0500 (Wed, 20 Jun 2007) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
		<?php echo Configure::read('simpleflan.title') ?> 
		|
		<?php echo $title_for_layout;?>
	</title>

	<?php echo $html->charset();?>

	<link rel="icon" href="<?php echo $this->webroot;?>favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="<?php echo $this->webroot;?>favicon.ico" type="image/x-icon" />
	<?php echo $javascript->link('themed/'.$this->theme.'/jquery');?>	
	<?php echo $javascript->link('themed/'.$this->theme.'/jquery.lightbox-0.3');?>	
	<?php echo $javascript->link('themed/'.$this->theme.'/common');?>	
	<?php echo $html->css('themed/'.$this->theme.'/styles');?>
	<?php echo $html->css('jquery.lightbox-0.3');?>
</head>
<body>
	<div id="header">
		<h1><?php echo $html->link(Configure::read('simpleflan.title'), '/');?></h1>
		<?php echo $this->renderElement("random_quote"); ?>
	</div>
	<div id="container">
		<?php echo $this->renderElement("navigation"); ?>
		<div id="content">
			<?php
				if ($session->check('Message.flash')):
						$session->flash();
				endif;
			?>

			<?php echo $content_for_layout;?>

			<div id="footer">
				<address>
				Leonidas 3157, Col. Vallarta San Jorge<br/>
				44690 Guadalajara Jalisco, México<br/>
				Tel. 33 3120 46 28 / 33 3120 46 29
				</address>
			</div>

		</div>

	</div>
	<?php echo $cakeDebug?>
</body>
</html>