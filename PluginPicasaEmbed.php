<?php
/*
Plugin Name: PluginPicasaEmbed
Plugin URI: http://www.dimgoto.com/open-source/wordpress/plugins/plugin-picasaembed
Description: Display Embed Picasa Album.
Version: 1.0.1
Author: Dimitri GOY
Author URI: http://www.dimgoto.com
*/

/*  Copyright 2009  DimGoTo  (email : wordpress@dimgoto.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Classe PluginPicasaEmbed.
 *
 * Diplay embed Picasa Album.
 *
 * @package Plugins
 * @subpackage PicasaEmbed
 * @version 1.0.0
 * @author Dimitri GOY
 * @copyright 2009 - DimGoTo
 * @link http://www.dimgoto.com/
 */
class PluginPicasaEmbed {

	protected $_pluginname = null;
	protected $_plugindir = null;
	protected $_langdir = null;
	protected $_jsdir = null;
	protected $_cssdir = null;
	protected $_pluginfile = null;
	protected $_imgdir = null;
	protected $_docdir = null;

	function __construct() {

		add_action('init', array($this, 'init'));
	}

	public function init() {

		$this->_pluginname = get_class($this);
		$this->_pluginfile = plugin_basename(__FILE__);
		$this->_plugindir = '/' . PLUGINDIR . '/' . str_replace('\\', '/', dirname(plugin_basename(__FILE__)));
		$this->_langdir = $this->_plugindir . '/lang';
		$this->_jsdir = $GLOBALS['wpbase'] . $this->_plugindir . '/js';
		$this->_cssdir = get_bloginfo('wpurl') . $GLOBALS['wpbase'] . $this->_plugindir . '/css';
		$this->_imgdir = '.' . $GLOBALS['wpbase'] . $this->_plugindir . '/img';
		$this->_docdir = '..' . $this->_plugindir . '/doc';

		load_plugin_textdomain($this->_pluginname, null, $this->_langdir);

		if (is_admin()) {

			register_activation_hook($this->_pluginfile, array($this, 'activate'));
			register_deactivation_hook($this->_pluginfile, array($this, 'deactivate'));

			if (function_exists('register_uninstall_hook')) {
	    		register_uninstall_hook($this->_pluginfile, array($this, 'uninstall'));
			}

			add_action('wp_ajax_picasaembed_authenticate', array($this, 'ajax_authenticate'));
			add_action('wp_ajax_picasaembed_list', array($this, 'ajax_list'));
			add_action('wp_ajax_picasaembed_configure', array($this, 'ajax_configure'));

			if (isset($_GET)
			&& isset($_GET['page'])
			&& $_GET['page'] == $this->_pluginname) {

				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-resizable');
				wp_enqueue_script('jquery-ui-dialog');

				wp_enqueue_script(strtolower($this->_pluginname) . '-jquery-json', $this->_jsdir . '/jquery.json-2.2.js', array(), false);
				wp_enqueue_script(strtolower($this->_pluginname) . '-admin', $this->_jsdir . '/pluginpicasaembed.js', array(), false);

				wp_enqueue_style(strtolower($this->_pluginname) . '-jquery-ui', $this->_cssdir . '/jquery-ui-1.0.0.css', array(), false, 'screen');
				wp_enqueue_style(strtolower($this->_pluginname) . '-style', $this->_cssdir . '/style.css', array(), false, 'screen');

				add_action('admin_head', array($this, 'head'));
				add_filter('contextual_help', array($this, 'help'));
			}

			add_action('admin_menu', array($this, 'menu'));

		} else {

			add_shortcode(strtolower($this->_pluginname), array($this, 'shortcode'));
		}

	}

	public function head() {

		$html = '<script type="text/javascript">';

		if (is_admin()
		&& isset($_GET)
		&& isset($_GET['page'])
		&& $_GET['page'] == $this->_pluginname) {
			$html .= 'var label_ok = "' . __('Ok', $this->_pluginname) . '";';
			$html .= 'var label_cancel = "' . __('Annuler', $this->_pluginname) . '";';
			$html .= 'var label_private = "' . __('Privé', $this->_pluginname) . '";';
			$html .= 'var label_public = "' . __('Public', $this->_pluginname) . '";';
			$html .= 'var label_protected = "' . __('Protégé', $this->_pluginname) . '";';
			$html .= 'var message_no_album = "' . __('Aucun Album.', $this->_pluginname) . '";';
			$html .= 'var message_authentication_required = "' . __('Authentification requise!', $this->_pluginname) . '";';
			$html .= 'var message_select_least = "' . __('Sélectionnez au moins un élément!', $this->_pluginname) . '";';
			$html .= 'var message_select_single = "' . __('Sélectionnez un seul élément!', $this->_pluginname) . '";';
		}
		$html .= '</script>';

		echo $html;
	}

	public function menu() {

		add_submenu_page('plugins.php',
				__('Picasa Embed', $this->__pluginname),
				__('Picasa Embed', $this->__pluginname),
				'activate_plugins',
				$this->_pluginname,
				array($this, 'control'));
	}

	public function activate() {

		$albums = get_option(strtolower($this->_pluginname));
		if (empty($albums)) {
			add_option(strtolower($this->_pluginname), '');
		}
	}

	public function deactivate() {

	}

	public function uninstall() {

		delete_option(strtolower($this->_pluginname));
	}

	public function help($context = '') {

		$help = '';
		if (file_exists($this->_docdir . '/' . $this->_pluginname . '-' . WPLANG . '.html')) {
			$help .= file_get_contents($this->_docdir . '/' . $this->_pluginname . '-' . WPLANG . '.html');
		} else if (file_exists($this->_docdir . '/' . $this->_pluginname . '-fr_FR.html')) {
			$help .= file_get_contents($this->_docdir . '/' . $this->_pluginname . '-fr_FR.html');
		}
		$help .= $context;

		return $help;
	}

	public function control() {

		$options = get_option(strtolower($this->_pluginname));
		$configuration = null;
		if (!empty($options)
		&& array_key_exists('configuration', $options)) {
			$configuration = $options['configuration'];
		}
		$html = '';

		// begin wrap > title
		$html .= '<div class="wrap" id="' . strtolower($this->_pluginname) . '">';
		$html .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$html .= '<h2>' . __('Configuration PicasaEmbed', $this->pluginname) . '</h2>';
		$html .= '<br/>';

		// list picasa albums
		$html .= '<h3>' . __('Liste des Picasa Albums', $this->_pluginname) . '</h3>';
		$html .= '<table class="widefat fixed" cellspacing="0" id="' . strtolower($this->_pluginname) . '-albums">';
		$html .= '<thead>';
		$html .= '<tr class="thead">';
		$html .= '<th id="hcb" class="manage-column column-cb check-column" style="" scope="col">';
		$html .= '<input type="checkbox" name="checkbox[]" id="' . strtolower($this->_pluginname) . '-cb0"/>';
		$html .= '</th>';
		$html .= '<th id="htitle" class="manage-column" style="" scope="col">' . __('Titre', $this->_pluginname) . '</th>';
		$html .= '<th id="hnumphotos" class="manage-column" style="" scope="col">' . __('Nb Photos', $this->_pluginname) . '</th>';
		$html .= '<th id="hupdated" class="manage-column" style="" scope="col">' . __('Révision', $this->_pluginname) . '</th>';
		$html .= '<th id="hauthor" class="manage-column" style="" scope="col">' . __('Auteur', $this->_pluginname) . '</th>';
		$html .= '<th id="hvisibility" class="manage-column" style="" scope="col">' . __('Visibilité', $this->_pluginname) . '</th>';
		$html .= '<th id="hshortcode" class="manage-column" style="" scope="col">' . __('ShortCode', $this->_pluginname) . '</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tfoot>';
		$html .= '<tr class="thead">';
		$html .= '<th id="fcb" class="manage-column column-cb check-column" style="" scope="col">';
		$html .= '<input type="checkbox" name="checkbox[]" id="' . strtolower($this->_pluginname) . '-cb1"/>';
		$html .= '</th>';
		$html .= '<th id="ftitle" class="manage-column" style="" scope="col">' . __('Titre', $this->_pluginname) . '</th>';
		$html .= '<th id="fnumphotos" class="manage-column" style="" scope="col">' . __('Nb Photos', $this->_pluginname) . '</th>';
		$html .= '<th id="fupdated" class="manage-column" style="" scope="col">' . __('Révision', $this->_pluginname) . '</th>';
		$html .= '<th id="fauthor" class="manage-column" style="" scope="col">' . __('Auteur', $this->_pluginname) . '</th>';
		$html .= '<th id="fvisibility" class="manage-column" style="" scope="col">' . __('Visibilité', $this->_pluginname) . '</th>';
		$html .= '<th id="fshortcode" class="manage-column" style="" scope="col">' . __('ShortCode', $this->_pluginname) . '</th>';
		$html .= '</tr>';
		$html .= '</foot>';
		$html .= '<tbody id="' . strtolower($this->_pluginname) . '-list">';
		$html .= '</tbody>';
		$html .= '</table>';

		// buttons
		$html .= '<p class="submit">';
		$html .= '<input class="button-secondary" type="button" value="' . __('Actualiser', $this->_pluginname) . '" id="' . strtolower($this->_pluginname) . '-refresh" name="' . strtolower($this->_pluginname) . '-refresh"/>';
		$html .= '<input class="button-secondary" type="button" value="' . __('Configurer', $this->_pluginname) . '" id="' . strtolower($this->_pluginname) . '-configure" name="' . strtolower($this->_pluginname) . '-configure"/>';
		$html .= '<input class="button-secondary" type="button" value="' . __('M\'identifier', $this->_pluginname) . '" id="' . strtolower($this->_pluginname) . '-auth" name="' . strtolower($this->_pluginname) . '-auth"/>';
		$html .= '</p>';

		$html .= '</div>'; // end wrap

		// dialog notification:
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-notification" title="' . __('Notification', $this->_pluginname) . '">';
		$html .= '<div class="ui-state-highlight ui-corner-all">';
		$html .= '<p class="ui-icon ui-icon-info" style="float: left; margin: 1em;"/>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-notification-message"/>';
		$html .= '</div>';
		$html .= '</div>';

		// dialog error:
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-error" title="' . __('Erreur', $this->_pluginname) . '">';
		$html .= '<div  class="ui-state-error ui-corner-all">';
		$html .= '<p class="ui-icon ui-icon-alert" style="float: left; margin: 1em;"/>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-error-message"/>';
		$html .= '</div>';
		$html .= '</div>';

		// dialog login:
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-login" title="' . __('Authentification', $this->_pluginname) . '">';
		$html .= '<p class="description">' . __('Identification requise, veuille saisir les informations de votre compte Google.', $this->_pluginname) . '</p>';
		$html .= '<p class="description"><span class="required">*</span> : ' . __('Champ obligatoire', $this->_pluginname) . '</p>';
		$html .= '<form>';
		$html .= '<table class="form-table">';
		$html .= '<tbody>';
		$html .= '<tr>';
		$html .= '<th scope="row"><label for="' . strtolower($this->_pluginname) . '-email">' . __('Email', $this->_pluginname) . '<span class="required">*</span></label></th>';
		$html .= '<td><input type="text" id="' . strtolower($this->_pluginname) . '-email" name="' . strtolower($this->_pluginname) . '-email"/></td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<th scope="row"><label for="' . strtolower($this->_pluginname) . '-password">' . __('Mot de passe', $this->_pluginname) . '<span class="required">*</span></label></th>';
		$html .= '<td><input type="password" id="' . strtolower($this->_pluginname) . '-password" name="' . strtolower($this->_pluginname) . '-password"/></td>';
		$html .= '</tr>';
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</form>';
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-login-error" class="ui-state-error ui-corner-all" style="margin-top: 0.4em; display: none;">';
		$html .= '<p class="ui-icon ui-icon-alert" style="float: left; margin-right: 2em;"/>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-dialog-login-error-message"/>';
		$html .= '</div>';
		$html .= '</div>';

		// dialog configure:
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-configure" title="' . __('Configuration', $this->_pluginname) . '">';
		$html .= '<p><strong>' . __('Album:', $this->_pluginname) . '</strong></p>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-configure-album"/>';
		$html .= '<form>';
		$html .= '<table class="form-table">';
		$html .= '<tbody>';
		$html .= '<tr>';
		$html .= '<th scope="row"><label for="' . strtolower($this->_pluginname) . '-configure-width">' . __('Largeur', $this->_pluginname) . '<span class="required">*</span></label></th>';
		$html .= '<td><input type="text" id="' . strtolower($this->_pluginname) . '-configure-width" name="' . strtolower($this->_pluginname) . '-configure-width"/></td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<th scope="row"><label for="' . strtolower($this->_pluginname) . '-configure-height">' . __('Hauteur', $this->_pluginname) . '<span class="required">*</span></label></th>';
		$html .= '<td><input type="text" id="' . strtolower($this->_pluginname) . '-configure-height" name="' . strtolower($this->_pluginname) . '-configure-height"/></td>';
		$html .= '</tr>';
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</form>';
		$html .= '<div id="' . strtolower($this->_pluginname) . '-dialog-configure-error" class="ui-state-error ui-corner-all" style="margin-top: 0.4em; display: none;">';
		$html .= '<p class="ui-icon ui-icon-alert" style="float: left; margin-right: 2em;"/>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-dialog-configure-error-message"/>';
		$html .= '</div>';
		$html .= '</div>';

		// thumbnail
		$html .= '<div id="' . strtolower($this->_pluginname) . '-thumbnail">';
		$html .= '<img id="' .strtolower($this->_pluginname) . '-thumbnail-img" src="noimg.jpg" width="160" height="160" alt="Thumbnail Picasa Album"/>';
		$html .= '<p id="' . strtolower($this->_pluginname) . '-thumbnail-info"/>';
		$html .= '</div>';

		echo $html;
	}

	public function shortcode($attributes, $content = '') {
		extract(shortcode_atts(array(
												'id'			=> ''
											),
											$attributes));

		if (isset($id)) {
			$thepicasa = null;
			$options = get_option(strtolower($this->_pluginname));
			$configuration = $options['configuration'];
			if (!empty($options)
			&& array_key_exists('albums', $options)) {
				$picasaembeds = $options['albums'];
				foreach ($picasaembeds as $picasaembed) {
					if ($picasaembed['id_album'] == $id) {
						$thepicasa = $picasaembed;
						break;
					}
				}
				if (!is_null($thepicasa)) {
					$html = '';

					$html .= '<h2>' . $thepicasa['title'] . '</h2>';
					$html .= '<embed type="application/x-shockwave-flash" src="http://picasaweb.google.com/s/c/bin/slideshow.swf" width="' . $thepicasa['width'] . '" height="' . $thepicasa['height'] . '"';
					$html .= ' flashvars="host=picasaweb.google.com&hl=fr&feat=flashalbum&RGB=0x000000&feed=http%3A%2F%2Fpicasaweb.google.com%2Fdata%2Ffeed%2Fapi%2Fuser%2F' . $thepicasa['user'] . '%2Falbumid%2F' . $thepicasa['id_album'] . '%3Falt%3Drss%26kind%3Dphoto';
					if (!is_null($authey)) {
						$html .= '%26authkey%3D' . $thepicasa['authkey'];
					}
					$html .= '%26hl%3Dfr" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>';

					$content .= $html;
				}
			}
		}

		return $content;
	}

	public function ajax_authenticate() {
		if (!isset($_POST)) {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètres PicasEmbed manquants!', $this->_pluginname));
		} else if (!isset($_POST['email'])
		|| empty($_POST['email'])) {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètre Email manquant!', $this->_pluginname));
		} else if (!isset($_POST['password'])
		|| empty($_POST['password'])) {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètre Mot de passe manquant!', $this->_pluginname));
		} else {

			$params = array(
								'accountType'						=> 'HOSTED_OR_GOOGLE',
								'Email'									=>  $_POST['email'],
								'Passwd'								=>  $_POST['password'],
								'service'								=> 'lh2',
								'source'								=>	'DimGoTo-' . $this->_pluginname . '-v1',);

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/accounts/ClientLogin');
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

			$response	= curl_exec($ch);
			$err     		= curl_errno($ch);
			$errmsg  	= curl_error($ch) ;
			$info			= curl_getinfo($ch);

			if($info['http_code'] != '200') {
				curl_close($ch);
				header("Status: 400 Bad Request", true, 400);
				if (strstr($response, 'BadAuthentication') !== false) {
					die(__('Erreur authentification Google ClientLogin: BadAuthentication!', $this->_pluginname));
				} else {
					die(sprintf(__('Erreur authentification Google ClientLogin: %s!', $this->_pluginname), $errmsg));
				}
			} else {
				preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matchesauth);
				preg_match("/SID=([a-z0-9_\-]+)/i", $response, $matchessid);
				preg_match("/LSID=([a-z0-9_\-]+)/i", $response, $matcheslsid);

				$Auth	= $matchesauth[1];
				$SID	= $matchessid[1];
				$LSID	= $matcheslsid[1];

				$configuration = array(
									'email'		=> $_POST['email'],
									'Auth'		=> $Auth,
									'SID'		=> $SID,
									'LSID'		=> $LSID);

				$options = get_option(strtolower($this->_pluginname));
				if (empty($options)) {
					$options = array(
										'configuration'	=> null,
										'albums'			=> null);
				}
				$options['configuration'] = $configuration;
				update_option(strtolower($this->_pluginname), $options);
				echo 'Authenticated';
			}
		}
	}
	public function ajax_configure() {
		if (!isset($_POST)) {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètres PicasEmbed manquants!', $this->_pluginname));
		} else if (!isset($_POST['id_album'])
		|| empty($_POST['id_album'])) {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètre Album ID manquant!', $this->_pluginname));
		} else {
			$width = (!isset($_POST['width']) || empty($_POST['width'])) ? 400 : $_POST['width'];
			$height = (!isset($_POST['height']) || empty($_POST['height'])) ? 400 : $_POST['height'];
			$options = get_option(strtolower($this->_pluginname));
			if (array_key_exists('albums', $options)) {
				$albums = $options['albums'];
				if (!empty($albums)) {
					$tmpalbums = array();
					foreach ($albums as $album) {
						if ($album['id_album'] == $_POST['id_album']) {
							$album['width'] = $width;
							$album['height'] = $height;
							array_push($tmpalbums, $album);
						} else {
							array_push($tmpalbums, $album);
						}
					}
					$albums = $tmpalbums;
					$options['albums'] = $albums;
					update_option(strtolower($this->_pluginname), $options);
					echo sprintf(__('Album ID : %s modifié!', $this->_pluginname), $_POST['id_album']);
				} else {
					header("Status: 400 Bad Request", true, 400);
					die(__('Aucun Album en base!', $this->_pluginname));
				}
			} else {
				header("Status: 400 Bad Request", true, 400);
				die(__('Paramètres Plugin invalide!', $this->_pluginname));
			}
		}
	}
	public function ajax_list() {

		$options = get_option(strtolower($this->_pluginname));
		if (empty($options)) {
			header("Status: 400 Bad Request", true, 400);
			die('AuthenticationRequired');
		}

		$configuration = $options['configuration'];
		$params = array(
								'kind'		=> 'album',
								'access'	=> 'all'
								);
		$header[] = 'Authorization: GoogleLogin auth=' . $configuration['Auth'];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'http://picasaweb.google.com/data/feed/api/user/' . $configuration['email'] . '?kind=album&access=all');
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$response	= curl_exec($ch);
		$err     		= curl_errno($ch);
		$errmsg  	= curl_error($ch) ;
		$info			= curl_getinfo($ch);

		curl_close($ch);

		if($info['http_code'] != '200') {
			header("Status: 400 Bad Request", true, 400);
			die(sprintf(__('Erreur PicasaWeb Feed Album: %s!', $this->_pluginname), $errmsg));
		} else {
			$feed = simplexml_load_string($response);
			$albumlist = array();
			foreach ($feed->entry as $entry) {
				$gphoto = $entry->children('http://schemas.google.com/photos/2007');
				$media = $entry->children('http://search.yahoo.com/mrss/');
				$id_album = (string) $gphoto->id;
				$name = (string) $gphoto->name;
				$numphotos = (integer) $gphoto->numphotos;
				$user = (string) $gphoto->user;
				$published = (string) $entry->published;
				$dateupdated = new DateTime((string) $entry->updated);
				$updated = $dateupdated->format('Y-m-d h:i:s');
				$title = (string) $entry->title;
				$access = (string) $gphoto->access;
				$author = (string) $entry->author->name;
				$thelink = null;
				$authkey = null;
				foreach ($entry->link as $link) {
					$found = false;
					foreach ($link->attributes() as $attrname => $attrvalue) {
						if ($attrname == 'rel'
						&& $found == false) {
							$found = true;
						} else if ($attrname == 'href'
						&& $found == true) {
							$thelink = (string) $attrvalue;
							break;
						}
					}
					if ($found == true) {
						break;
					}
				}
				if (!is_null($thelink)) {
					if (preg_match('/authkey=(.*)$/', $thelink, $matches) !== false) {
						$authkey = $matches[1];
					}
				}
				$thumbnail_attributes = $media->group->thumbnail->attributes();
				$thumbnail = array(
										'url'		=> (string) $thumbnail_attributes['url'],
										'width'	=> (string) $thumbnail_attributes['width'],
										'height'	=> (string) $thumbnail_attributes['height']
										);
				$album = array(
									'id_album'	=> $id_album,
									'name'		=> $name,
									'numphotos'	=> $numphotos,
									'published'	=> $published,
									'updated'	=> $updated,
									'title'			=> $title,
									'access'		=> $access,
									'author'		=> $author,
									'link'			=> $thelink,
									'thumbnail'	=> $thumbnail,
									'height'		=> 400,
									'width'		=> 400,
									'authkey'		=> $authkey,
									'user'			=> $user);
				array_push($albumlist, $album);
			}
			$albums = $options['albums'];
			if (empty($albums)) {
				$options['albums'] = $albumlist;
			} else {
				$tmpalbums = array();
				foreach ($albums as $album) {
					$ondelete = true;
					foreach ($albumlist as $albump) {
						if ($albump['id_album'] == $album['id_album']) {
							$ondelete = false;
							break;
						}
					}
					if ($ondelete == false) {
						array_push($tmpalbums, $album);
					}
				}
				$albums = $tmpalbums;
				$tmpalbums = array();
				foreach ($albumlist as $albump) {
					$onupdate = false;
					foreach ($albums as $album) {
						if ($album['id_album'] == $albump['id_album']) {
							$onupdate = true;
							break;
						}
					}
					if ($onupdate == true) {
						$albump['width'] = $album['width'];
						$albump['height'] = $album['height'];
					}
					array_push($tmpalbums, $albump);
				}
				$albumlist = $tmpalbums;
				$options['albums'] = $albumlist;
			}
			update_option(strtolower($this->_pluginname), $options);
			if (!is_null($albumlist)) {
				usort($albumlist, array($this, 'sort'));
			}
			echo json_encode($albumlist);
		}
	}
	private function sort($arr1, $arr2) {
		$ret = strnatcmp($arr1['title'], $arr2['title']);
		if (!$ret) {
			$ret = strnatcmp($arr1['name'], $arr2['name']);
		}
		return $ret;
	}
}
new PluginPicasaEmbed();
?>