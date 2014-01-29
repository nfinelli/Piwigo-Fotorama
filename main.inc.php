<?php
/*
Plugin Name: Fotorama
Version: 2.6.a
Description: Fotorama based full-screen slideshow
Author: JanisV
*/

global $conf;

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

if (mobile_theme()) return;

define('FOTORAMA_PATH' , PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/'); 

add_event_handler('init', 'Fotorama_init');

function Fotorama_init()
{
  global $conf, $user, $page;
  
  add_event_handler('loc_end_picture', 'Fotorama_end_picture');
  add_event_handler('loc_end_page_header', 'Fotorama_end_page_header');
}
function Fotorama_end_picture()
{
  global $template, $conf, $user, $page, $refresh;

  if ($page['slideshow'] and $conf['light_slideshow'])
  {
    $query = '
    SELECT *
      FROM '.IMAGES_TABLE.'
      WHERE id IN ('.implode(',', $page['items']).')
      ORDER BY FIELD(id, '.implode(',', $page['items']).')
    ;';

    $result = pwg_query($query);

    $current = $template->get_template_vars('current');
    $type = $current['selected_derivative']->get_type();
    $defined = ImageStdParams::get_defined_type_map();
    if (!isset($defined[$type]))
    {
      $type = pwg_get_session_var('picture_deriv', $conf['derivative_default_size']);
    }
    
    $skip = -1;
    $big_type = $type;
    foreach (ImageStdParams::get_defined_type_map() as $def_type => $params)
    {
      if ($def_type == $type)
        $skip = 2;
      if ($skip >= 0)
        $big_type = $def_type;
      if ($skip == 0)
        break;
      $skip = $skip - 1;
    }
    
    $picture = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $row['src_image'] = new SrcImage($row);
//      $row['derivatives'] = DerivativeImage::get_all($row['src_image']);
      $row['derivative'] = DerivativeImage::get_one($type, $row['src_image']);
      if ($row['derivative'] == null)
      {
        $row['derivative'] = $row['src_image'];
      }
      $row['derivative_big'] = DerivativeImage::get_one($big_type, $row['src_image']);
      if ($row['derivative_big'] == null)
      {
        $row['derivative'] = $row['src_image'];
      }
/*
      $row['url'] = duplicate_picture_url(
        array(
          'image_id' => $row['id'],
          'image_file' => $row['file'],
          ),
        array(
          'start',
          )
        );
*/
      $row['TITLE'] = render_element_name($row);
      $row['TITLE_ESC'] = str_replace('"', '&quot;', $row['TITLE']);
      $picture[] = $row;
    }
    
    $template->assign('item_height', ImageStdParams::get_by_type($type)->max_height());
    $template->assign('items', $picture);
    $template->assign('current_rank', $page['current_rank']);
    $template->set_filenames( array('slideshow' => realpath(FOTORAMA_PATH.'template/slideshow.tpl')));
  }
}

function Fotorama_end_page_header()
{
  global $template;

  $template->clear_assign('page_refresh');
  $template->clear_assign('first');
  $template->clear_assign('previous');
  $template->clear_assign('next');
  $template->clear_assign('last');
}

?>