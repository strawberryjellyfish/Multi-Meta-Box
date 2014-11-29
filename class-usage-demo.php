<?php
/*
Plugin Name: Demo Multi MetaBox
Plugin URI:
Description: Multi Meta Box Class usage demo
Version: 3.2.1
Author:
Author URI:
*/

//include the main class file
require_once "meta-box-class/my-meta-box-class.php";
if ( is_admin() ) {
  /*
   * prefix of meta keys, optional
   * use underscore (_) at the beginning to make keys hidden, for example $prefix = '_ba_';
   *  you also can make prefix empty to disable it
   *
   */
  $prefix = 'mmb_';
  /*
   * configure your meta box
   */
  $config = array(
    'id'             => 'demo_meta_box',          // meta box id, unique per meta box
    'title'          => 'Simple Meta Box fields', // meta box title
    'scopes'         => array( 'category' ),      // taxonomy types
    'taxonomy'       => true,                     // this should apply to a taxonomy not post type
    'context'        => '',                       // Not used for taxonomy
    'priority'       => '',                       // Not used for taxonomy
    'fields'         => array(),                  // list of meta fields (can be added by field arrays)
    'local_images'   => false,                    // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => false                     //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  );


  /*
   * Initiate your meta box
   */
  $my_meta =  new Multi_Meta_Box( $config );

  /*
   * Add fields to your meta box
   */

  //text field
  $my_meta->add_text( $prefix.'text_field_id', array( 'name'=> 'Some Text ', 'std'=> 'blah' ) );
  //textarea field
  $my_meta->add_textarea( $prefix.'textarea_field_id', array( 'name'=> 'My Textarea ' ) );
  //checkbox field
  $my_meta->add_checkbox( $prefix.'checkbox_field_id', array( 'name'=> 'My Checkbox ' ) );
  //select field
  $my_meta->add_select( $prefix.'select_field_id', array( 'selectkey1'=>'Select Value1', 'selectkey2'=>'Select Value2' ), array( 'name'=> 'My select ', 'std'=> array( 'selectkey2' ) ) );
  //radio field
  $my_meta->add_radio( $prefix.'radio_field_id', array( 'radiokey1'=>'Radio Value1', 'radiokey2'=>'Radio Value2' ), array( 'name'=> 'My Radio Filed', 'std'=> array( 'radionkey2' ) ) );
  //Image field
  $my_meta->add_image( $prefix.'image_field_id', array( 'name'=> 'My Image ' ) );
  //file upload field
  $my_meta->add_file( $prefix.'file_field_id', array( 'name'=> 'My File' ) );
  //file upload field with type limitation
  $my_meta->add_file( $prefix.'file_pdf_field_id', array( 'name'=> 'My File limited to PDF Only', 'ext' =>'pdf', 'mime_type' => 'application/pdf' ) );
  /*
   * Don't Forget to Close up the meta box Declaration
   */
  //Finish Meta Box Declaration
  $my_meta->finish();

  /**
   * Create a second metabox
   */
  /*
   * configure your meta box
   */
  $config2 = array(
    'id'             => 'demo_meta_box2',           // meta box id, unique per meta box
    'title'          => 'Advanced Meta Box fields', // meta box title
    'scopes'         => array( 'post' ),            // post types, accept custom post types as well, default is array('post'); optional
    'taxonomy'       => false,                      // where the meta box appear: normal (default), advanced, side; optional
    'context'        => 'normal',                   // where the meta box appear: normal (default), advanced, side; optional
    'priority'       => 'high',                     // order of meta box: high (default), low; optional
    'fields'         => array(),                    // list of meta fields (can be added by field arrays)
    'local_images'   => false,                      // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => false                       // change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  );


  /*
   * Initiate your 2nd meta box
   */
  $my_meta2 =  new Multi_Meta_Box( $config2 );

  /*
   * Add fields to your 2nd meta box
   */
  //add checkboxes list
  $my_meta2->add_checkbox_list( $prefix.'CheckboxList_field_id', array( 'checkboxkey1'=>'checkbox Value1', 'checkboxkey2'=>'checkbox Value2' ), array( 'name'=> 'My checkbox list ', 'std'=> array( 'checkboxkey2' ) ) );
  //slider field
  $my_meta2->add_slider( $prefix.'slider_field_id', array( 'name'=> 'Slider ', 'min'=>0, 'max'=> 100, 'std' => 50 ) );
  //date field
  $my_meta2->add_date( $prefix.'date_field_id', array( 'name'=> 'My Date ', 'desc' => 'You can have some helpful text here' ) );
  //Time field
  $my_meta2->add_time( $prefix.'time_field_id', array( 'name'=> 'My Time ' ) );
  //Color field
  $my_meta2->add_color( $prefix.'color_field_id', array( 'name'=> 'My Color ' ) );
  //wysiwyg field
  $my_meta2->add_wysiwyg( $prefix.'wysiwyg_field_id', array( 'name'=> 'My wysiwyg Editor ' ) );
  //taxonomy field
  $my_meta2->add_taxonomy( $prefix.'taxonomy_field_id', array( 'taxonomy' => 'category' ), array( 'name'=> 'My Taxonomy ' ) );
  //posts field
  $my_meta2->add_posts( $prefix.'posts_field_id', array( 'post_type' => 'post' ), array( 'name'=> 'My Posts ' ) );
  //add Code editor field
  $my_meta2->add_code( $prefix.'code_field_id', array(
      'name'   => 'Code editor Field',
      'syntax' => 'php',
      'theme'  => 'light'
    ) );

  /*
   * To Create a repeater Block first create an array of fields
   * use the same functions as above but add true as a last param
   */
  $repeater_fields[] = $my_meta2->add_text( $prefix.'re_text_field_id', array( 'name'=> 'My Text ' ), true );
  $repeater_fields[] = $my_meta2->add_textarea( $prefix.'re_textarea_field_id', array( 'name'=> 'My Textarea ' ), true );
  $repeater_fields[] = $my_meta2->add_checkbox( $prefix.'re_checkbox_field_id', array( 'name'=> 'My Checkbox ' ), true );
  $repeater_fields[] = $my_meta2->add_image( $prefix.'image_field_id', array( 'name'=> 'My Image ' ), true );
  /*
   * Then just add the fields to the repeater block
   */
  //repeater block
  $my_meta2->add_repeater_block( $prefix.'re_', array(
      'inline'   => true,
      'name'     => 'This is a Repeater Block',
      'fields'   => $repeater_fields,
      'sortable' => true
    ) );

  /*
   * To Create a conditional Block first create an array of fields
   * use the same functions as above but add true as a last param (like the repeater block)
   */
  $Conditinal_fields[] = $my_meta2->add_text( $prefix.'con_text_field_id', array( 'name'=> 'My Text ' ), true );
  $Conditinal_fields[] = $my_meta2->add_textarea( $prefix.'con_textarea_field_id', array( 'name'=> 'My Textarea ' ), true );
  $Conditinal_fields[] = $my_meta2->add_checkbox( $prefix.'con_checkbox_field_id', array( 'name'=> 'My Checkbox ' ), true );
  $Conditinal_fields[] = $my_meta2->add_color( $prefix.'con_color_field_id', array( 'name'=> 'My color ' ), true );

  /*
   * Then just add the fields to the repeater block
   */
  //repeater block
  $my_meta2->add_condition( 'conditinal_fields',
    array(
      'name'   => __( 'Enable conditional fields? ', 'mmb' ),
      'desc'   => __( '<small>Turn ON if you want to enable the <strong>conditional fields</strong>.</small>', 'mmb' ),
      'fields' => $Conditinal_fields,
      'std'    => false
    ) );

  /*
   * Don't Forget to Close up the meta box Declaration
   */
  //Finish Meta Box Declaration

  $my_meta2->finish();


  $prefix = "_groupped_";
  $config3 = array(
    'id'             => 'demo_meta_box3',           // meta box id, unique per meta box
    'title'          => 'Groupped Meta Box fields', // meta box title
    'scopes'         => array( 'post', 'page' ),    // post types, accept custom post types as well, default is array('post'); optional
    'context'        => 'normal',                   // where the meta box appear: normal (default), advanced, side; optional
    'priority'       => 'low',                      // order of meta box: high (default), low; optional
    'fields'         => array(),                    // list of meta fields (can be added by field arrays)
    'local_images'   => false,                      // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => false                       // change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  );


  /*
   * Initiate your 3rd meta box
   */
  $my_meta3 =  new Multi_Meta_Box( $config3 );
  //first field of the group has 'group' => 'start' and last field has 'group' => 'end'

  //text field
  $my_meta3->add_text( $prefix.'text_field_id', array( 'name'=> 'My Text ', 'group' => 'start' ) );
  //textarea field
  $my_meta3->add_textarea( $prefix.'textarea_field_id', array( 'name'=> 'My Textarea ' ) );
  //checkbox field
  $my_meta3->add_checkbox( $prefix.'checkbox_field_id', array( 'name'=> 'My Checkbox ' ) );
  //select field
  $my_meta3->add_select( $prefix.'select_field_id', array( 'selectkey1'=>'Select Value1', 'selectkey2'=>'Select Value2' ), array( 'name'=> 'My select ', 'std'=> array( 'selectkey2' ) ) );
  //radio field
  $my_meta3->add_radio( $prefix.'radio_field_id', array( 'radiokey1'=>'Radio Value1', 'radiokey2'=>'Radio Value2' ), array( 'name'=> 'My Radio Filed', 'std'=> array( 'radionkey2' ), 'group' => 'end' ) );

  /*
   * Don't Forget to Close up the meta box Declaration
   */
  //Finish Meta Box Declaration
  $my_meta3->finish();

  $config4 = array(
    'id'             => 'demo_meta_box4',         // meta box id, unique per meta box
    'title'          => 'Geocoder',               // meta box title
    'scopes'         => array( 'post', 'page' ),  // post types, accept custom post types as well, default is array('post'); optional
    'context'        => 'normal',                 // where the meta box appear: normal (default), advanced, side; optional
    'priority'       => 'high',                   // order of meta box: high (default), low; optional
    'fields'         => array(),                  // list of meta fields (can be added by field arrays)
    'local_images'   => false,                    // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => false                     //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  );


  /*
   * Initiate your 4th meta box
   */
  $my_meta4 =  new Multi_Meta_Box( $config4 );
  $my_meta4->add_geocoder('place_one', array( 'name' => 'Location', 'description' => 'this is the description' ) );
  $my_meta4->add_geocoder('place_two', array( 'name' => 'Location 2') );

  $my_meta4->finish();
}