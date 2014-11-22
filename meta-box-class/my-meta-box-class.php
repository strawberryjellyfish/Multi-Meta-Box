<?php
/**
 * Multi Meta Box Class
 *
 * The Meta Box Class is intended to be included by WordPress plugins or themes
 * to provide an easy way to create meta box input in a variety of formats.
 *
 * It has evolved from it's origins to suit my own needs, which may or may not
 * be useful to you. Class, style and function names have changed to avoid
 * collisions with the original All Types Metabox. There has also been a fair
 * amount of re-factoring and code clean-up to give a more consistent coding
 * style and naming conventions.
 *
 * Derived from All Types Meta Box by
 * Ohad Raz (email: admin@bainternet.info)
 * which in turn was derived from a Meta Box script by
 * Rilwis<rilwis@gmail.com> version 3.2. and forked by
 * Cory Crowley (email: cory.ivan@gmail.com).
 * All credit to all previous authors for laying down the groundwork and
 * providing inspiration.
 *
 * @version 3.2.3
 * @copyright 2014
 * @author Robert Miller (email: rob@strawberryjellyfish.com)
 * @link http://strawberryjellyfish.com
 *
 * @license GNU General Public LIcense v3.0 - license.txt
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package Multi Meta Box Class
 */

if ( ! class_exists( 'Multi_Meta_Box' ) ) :

  /**
   * All Types Meta Box class.
   *
   * @package All Types Meta Box
   * @since 1.0
   *
   * @todo Nothing.
   */
  class Multi_Meta_Box {

  /**
   * Holds meta box object
   *
   * @var object
   * @access protected
   */
  protected $_meta_box;

  /**
   * Holds meta box fields.
   *
   * @var array
   * @access protected
   */
  protected $_prefix;

  /**
   * Holds Prefix for meta box fields.
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * Use local images.
   *
   * @var bool
   * @access protected
   */
  protected $_local_images;

  /**
   * class_path to allow themes as well as plugins.
   *
   * @var string
   * @access protected
   * @since 1.6
   */
  protected $class_path;

  /**
   * $field_types  holds used field types
   *
   * @var array
   * @access public
   * @since 2.9.7
   */
  public $field_types = array();

  /**
   * $in_group  holds grouping boolean
   *
   * @var boolean
   * @access public
   * @since 2.9.8
   */
  public $in_group = false;

  /**
   * Constructor
   *
   * @since 1.0
   * @access public
   *
   * @param array   $meta_box
   */
  public function __construct( $meta_box ) {

    // If we are not in admin area exit.
    if ( ! is_admin() )
      return;

    //load translation
    add_filter( 'init', array( $this, 'load_textdomain' ) );

    // Assign meta box values to local variables and add it's missed values.
    $this->_meta_box = $meta_box;
    $this->_prefix = ( isset( $meta_box['prefix'] ) ) ? $meta_box['prefix'] : '';
    $this->_fields = $this->_meta_box['fields'];
    $this->_local_images = ( isset( $meta_box['local_images'] ) ) ? true : false;
    $this->add_missed_values();
    if ( isset( $meta_box['use_with_theme'] ) )
      if ( $meta_box['use_with_theme'] === true ) {
        $this->class_path = get_stylesheet_directory_uri() . '/meta-box-class';
      } elseif ( $meta_box['use_with_theme'] === false ) {
      $this->class_path = plugins_url( 'meta-box-class', plugin_basename( dirname( __FILE__ ) ) );
    } else {
      $this->class_path = $meta_box['use_with_theme'];
    } else {
      $this->class_path = plugins_url( 'meta-box-class', plugin_basename( dirname( __FILE__ ) ) );
    }

    // Add metaboxes
    add_action( 'add_meta_boxes', array( $this, 'add' ) );
    //add_action( 'wp_insert_post', array( $this, 'save' ) );
    add_action( 'save_post', array( $this, 'save' ) );
    // Load common js, css files
    // Must enqueue for all pages as we need js for the media upload, too.
    add_action( 'admin_print_styles', array( $this, 'load_scripts_styles' ) );
    //limit File type at upload
    add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_upload_file_type' ) );

  }


  /**
   * Load all JavaScript and CSS
   *
   * @since 1.0
   * @access public
   */
  public function load_scripts_styles() {

    // Get Plugin Path
    $plugin_path = $this->class_path;

    //only load styles and js when needed
    /*
     * since 1.8
     */
    global $typenow;
    if ( in_array( $typenow, $this->_meta_box['pages'] ) && $this->is_edit_page() ) {
      // Enqueue Meta Box Style
      wp_enqueue_style( 'mmb-meta-box', $plugin_path . '/css/meta-box.css' );

      // Enqueue Meta Box Scripts
      wp_enqueue_script( 'mmb-meta-box', $plugin_path . '/js/meta-box.js', array( 'jquery' ), null, true );

      // Make upload feature work event when custom post type doesn't support 'editor'
      if ( $this->has_field( 'image' ) || $this->has_field( 'file' ) ) {
        wp_enqueue_script( 'media-upload' );
        add_thickbox();
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-sortable' );
      }
      // Check for special fields and add needed actions for them.

      //this replaces the ugly check fields methods calls
      foreach ( array( 'upload', 'color', 'date', 'time', 'code', 'select', 'slider' ) as $type ) {
        call_user_func( array( $this, 'check_field_' . $type ) );
      }
    }

  }


  /**
   * Check the Field select, Add needed Actions
   *
   * @since 2.9.8
   * @access public
   */
  public function check_field_select() {

    // Check if the field is an image or file. If not, return.
    if ( ! $this->has_field( 'select' ) )
      return;
    $plugin_path = $this->class_path;
    // Enqueue JQuery select2 library, use proper version.
    wp_enqueue_style( 'mmb-multiselect-select2-css', $plugin_path . '/js/select2/select2.css', array(), null );
    wp_enqueue_script( 'mmb-multiselect-select2-js', $plugin_path . '/js/select2/select2.js', array( 'jquery' ), false, true );
  }


  /**
   * Check the Field Upload, Add needed Actions
   *
   * @since 1.0
   * @access public
   */
  public function check_field_upload() {

    // Check if the field is an image or file. If not, return.
    if ( ! $this->has_field( 'image' ) && ! $this->has_field( 'file' ) )
      return;

    // Add data encoding type for file uploading.
    add_action( 'post_edit_form_tag', array( $this, 'add_enctype' ) );

  }


  /**
   * Add data encoding type for file uploading
   *
   * @since 1.0
   * @access public
   */
  public function add_enctype() {
    printf( ' enctype="multipart/form-data" encoding="multipart/form-data" ' );
  }


  /**
   * Check Field Color
   *
   * @since 1.0
   * @access public
   */
  public function check_field_color() {

    if ( $this->has_field( 'color' ) && $this->is_edit_page() ) {
      wp_enqueue_style( 'wp-color-picker' );
      wp_enqueue_script( 'wp-color-picker' );
    }
  }


  /**
   * Check Field Date
   *
   * @since 1.0
   * @access public
   */
  public function check_field_date() {

    if ( $this->has_field( 'date' ) && $this->is_edit_page() ) {
      // Enqueue JQuery UI, use proper version.
      $plugin_path = $this->class_path;
      wp_enqueue_style( 'mmb-jquery-ui-css', $plugin_path .'/js/jquery-ui/jquery-ui.css' );
      wp_enqueue_script( 'jquery-ui' );
      wp_enqueue_script( 'jquery-ui-datepicker' );
    }
  }


  /**
   * Check Field Time
   *
   * @since 1.0
   * @access public
   */
  public function check_field_time() {

    if ( $this->has_field( 'time' ) && $this->is_edit_page() ) {
      $plugin_path = $this->class_path;
      // Enqueue JQuery UI, use proper version.
      wp_enqueue_style( 'mmb-jquery-ui-css', $plugin_path .'/js/jquery-ui/jquery-ui.css' );
      wp_enqueue_script( 'jquery-ui' );
      wp_enqueue_script( 'mmb-timepicker', $plugin_path .'/js/jquery-ui/jquery-ui-timepicker-addon.js', array( 'jquery-ui-slider', 'jquery-ui-datepicker' ), false, true );
    }
  }


  /**
   * Check Field code editor
   *
   * @since 2.1
   * @access public
   */
  public function check_field_code() {

    if ( $this->has_field( 'code' ) && $this->is_edit_page() ) {
      $plugin_path = $this->class_path;
      // Enqueue codemirror js and css
      wp_enqueue_style( 'mmb-code-css', $plugin_path .'/js/codemirror/codemirror.css', array(), null );
      wp_enqueue_style( 'mmb-code-css-dark', $plugin_path .'/js/codemirror/solarizedDark.css', array(), null );
      wp_enqueue_style( 'mmb-code-css-light', $plugin_path .'/js/codemirror/solarizedLight.css', array(), null );
      wp_enqueue_script( 'mmb-code-js', $plugin_path .'/js/codemirror/codemirror.js', array( 'jquery' ), false, true );
      wp_enqueue_script( 'mmb-code-js-xml', $plugin_path .'/js/codemirror/xml.js', array( 'jquery' ), false, true );
      wp_enqueue_script( 'mmb-code-js-javascript', $plugin_path .'/js/codemirror/javascript.js', array( 'jquery' ), false, true );
      wp_enqueue_script( 'mmb-code-js-css', $plugin_path .'/js/codemirror/css.js', array( 'jquery' ), false, true );
      wp_enqueue_script( 'mmb-code-js-clike', $plugin_path .'/js/codemirror/clike.js', array( 'jquery' ), false, true );
      wp_enqueue_script( 'mmb-code-js-php', $plugin_path .'/js/codemirror/php.js', array( 'jquery' ), false, true );

    }
  }


  /**
   * Check Field Slider
   *
   * @author Robert Miller
   * @since 3.2.0
   * @access public
   */
  public function check_field_slider() {

    if ( $this->has_field( 'slider' ) && $this->is_edit_page() ) {
      $plugin_path = $this->class_path;
      wp_enqueue_style( 'mmb-jquery-ui-css', $plugin_path .'/js/jquery-ui/jquery-ui.css' );
      wp_enqueue_script( 'jquery-ui-slider' );
    }
  }


  /**
   * Add Meta Box for multiple post types.
   *
   * @since 1.0
   * @access public
   */
  public function add( $postType ) {
    if ( in_array( $postType, $this->_meta_box['pages'] ) ) {
      add_meta_box( $this->_meta_box['id'], $this->_meta_box['title'], array( $this, 'show' ), $postType, $this->_meta_box['context'], $this->_meta_box['priority'] );
    }
  }

  /**
   * Callback function to show fields in meta box.
   *
   * @since 1.0
   * @access public
   */
  public function show() {
    $this->in_group = false;
    global $post;

    wp_nonce_field( basename( __FILE__ ), 'multi_meta_box_nonce' );
    echo '<table class="widefat">';
    foreach ( $this->_fields as $field ) {
      $field['multiple'] = isset( $field['multiple'] ) ? $field['multiple'] : false;
      $meta = get_post_meta( $post->ID, $field['id'], !$field['multiple'] );
      $meta = ( $meta !== '' ) ? $meta : @$field['std'];

      if ( !in_array( $field['type'], array( 'image', 'repeater', 'file' ) ) )
        $meta = is_array( $meta ) ? array_map( 'esc_attr', $meta ) : esc_attr( $meta );

      if ( $this->in_group !== true )
        echo '<tr>';

      if ( isset( $field['group'] ) && $field['group'] == 'start' ) {
        $this->in_group = true;
        echo '<td><table class="form-table"><tr>';
      }

      // Call Separated methods for displaying each type of field.
      call_user_func( array( $this, 'show_field_' . $field['type'] ), $field, $meta );

      if ( $this->in_group === true ) {
        if ( isset( $field['group'] ) && $field['group'] == 'end' ) {
          echo '</tr></table></td></tr>';
          $this->in_group = false;
        }
      }else {
        echo '</tr>';
      }
    }
    echo '</table>';
  }


  /**
   * Show Repeater Fields.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_repeater( $field, $meta ) {
    global $post;
    // Get Plugin Path
    $plugin_path = $this->class_path;
    $this->show_field_begin( $field, $meta );
    $meta = get_post_meta( $post->ID, $field['id'], true );
    $class = $field['sortable'] ? " mmb-repeater-sortable" : '';

    echo "<div class='mmb-repeat". $class ."' id='{$field['id']}'>";

    if ( count( $meta ) > 0 && is_array( $meta ) ) {
      $count = 0;
      foreach ( $meta as $me ) {
        //for labelling toggles
        $repeater_preview =  isset( $me[$field['fields'][0]['id']] ) ? $me[$field['fields'][0]['id']] : "";
        $table_class = ( count( $field['fields'] ) == 1 ) ? "mmb-repeater-table-expanded" : "mmb-repeater-table-collapsed";

        echo '<div class="mmb-repeater-block">';
        if ( count( $field['fields'] ) > 1 )
          echo $repeater_preview.'<br/>';
        echo '<table class="mmb-repeater-table ' . $table_class . '"';
        if ( $field['inline'] ) {
          echo '<tr class="mmb-inline" VALIGN="top">';
        }
        foreach ( $field['fields'] as $child_field ) {
          //reset var $id for repeater
          $id = $field['id'] . '[' . $count. '][' . $child_field['id'] . ']';
          $child_meta = isset( $me[$child_field['id']] ) ? $me[$child_field['id']] : '';
          $child_meta = ( $child_meta !== '' ) ? $child_meta : $child_field['std'];
          if ( 'image' != $child_field['type'] && $child_field['type'] != 'repeater' )
            $child_meta = is_array( $child_meta ) ? array_map( 'esc_attr', $child_meta ) : esc_attr( $child_meta );
          //set new id for field in array format
          $child_field['id'] = $id;
          if ( !$field['inline'] ) {
            echo '<tr>';
          }
          call_user_func( array( $this, 'show_field_' . $child_field['type'] ), $child_field, $child_meta );
          if ( !$field['inline'] ) {
            echo '</tr>';
          }
        }
        if ( $field['inline'] ) {
          echo '</tr>';
        }
        echo '</table>';
        if ( $field['sortable'] )
          echo '<span class="mmb-repeater-control dashicons dashicons-randomize mmb-repeater-sort-handle"></span>';
        if ( count( $field['fields'] ) > 1 )
          echo'<span class="mmb-repeater-control mmb-re-toggle dashicons dashicons-welcome-write-blog"></span>';
        echo '
          <span class="mmb-repeater-control dashicons dashicons-no mmb-repeater-remove-button"></span>
          <span class="mmb-repeater-control-clear"></span></div>';
        $count = $count + 1;
      }
    }

    echo '<div class="button-secondary repeater-add" id="add-'.$field['id'].'"><span class="dashicons dashicons-plus mmb-button-icon"></span>Add</div></div>';

    //create all fields once more for js function and catch with object buffer
    ob_start();
    echo '<div class="mmb-repeater-block"><table class="mmb-repeater-table">';
    if ( $field['inline'] ) {
      echo '<tr class="mmb-inline" VALIGN="top">';
    }
    foreach ( $field['fields'] as $f ) {
      //reset var $id for repeater
      $id = '';
      $id = $field['id'].'[CurrentCounter]['.$f['id'].']';
      $f['id'] = $id;
      if ( !$field['inline'] ) {
        echo '<tr>';
      }
      if ( $f['type'] != 'wysiwyg' )
        call_user_func( array( $this, 'show_field_' . $f['type'] ), $f, '' );
      else
        call_user_func( array( $this, 'show_field_' . $f['type'] ), $f, '', true );
      if ( !$field['inline'] ) {
        echo '</tr>';
      }
    }
    if ( $field['inline'] ) {
      echo '</tr>';
    }
    echo '</table>';
    if ( $field['sortable'] )
      echo '<span class="mmb-repeater-control dashicons dashicons-randomize mmb-repeater-sort-handle"></span>';
    if ( count( $field['fields'] ) > 1 )
      echo'<span class="mmb-repeater-control mmb-re-toggle dashicons dashicons-welcome-write-blog"></span>';
    echo '<span class="mmb-repeater-control dashicons dashicons-no mmb-repeater-remove-button" id="remove-'.$field['id'].'"></span><span class="mmb-repeater-control-clear"></span></div>';
    $counter = 'countadd_'.$field['id'];
    $js_code = ob_get_clean();
    $js_code = str_replace( "\n", "", $js_code );
    $js_code = str_replace( "\r", "", $js_code );
    $js_code = str_replace( "'", "\"", $js_code );
    $js_code = str_replace( "CurrentCounter", "' + ".$counter." + '", $js_code );
    echo '<script>
        jQuery(document).ready(function() {
          var '.$counter.' = '.$count.';
          jQuery("#add-'.$field['id'].'").on(\'click\', function() {
            '.$counter.' = '.$count.' + 1;
            jQuery(this).before(\''.$js_code.'\');
            update_repeater_fields();
          });
        });
        </script>';
    $this->show_field_end( $field, $meta );
  }


  /**
   * Begin Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_begin( $field, $meta ) {
    if ( $field['name'] != '' || $field['name'] != FALSE ) {
      echo "<th class='mmb-label'>";
      echo "<label for='{$field['id']}'>{$field['name']}</label>";
      echo "</th>";
    }
    echo "<td class='mmb-field'".( ( $this->in_group === true )? " valign='top'": "" ).">";
  }


  /**
   * End Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_end( $field, $meta=NULL , $group = false ) {
    //print description
    if ( isset( $field['desc'] ) && $field['desc'] != '' )
      echo "<div class='desc-field'>{$field['desc']}</div>";
    echo "</td>";
  }


  /**
   * Show Field Text.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_text( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    echo "<input type='text' class='mmb-text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='30' ".( isset( $field['style'] )? "style='{$field['style']}'" : '' )."/>";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Field number.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_number( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    $step = ( isset( $field['step'] ) || $field['step'] != '1' )? "step='".$field['step']."' ": '';
    $min = isset( $field['min'] )? "min='".$field['min']."' ": '';
    $max = isset( $field['max'] )? "max='".$field['max']."' ": '';
    echo "<input type='number' class='mmb-number".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='30' ".$step.$min.$max.( isset( $field['style'] )? "style='{$field['style']}'" : '' )."/>";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Field Slider
   *
   * @author Robert Miller
   * @param string  $field
   * @param string  $meta
   * @since 3.2.0
   * @access public
   */
  public function show_field_slider( $field, $meta ) {
    $meta = is_numeric( $meta ) ? $meta : $field['std'];
    $this->show_field_begin( $field, $meta );
    echo "<div id='" . $field['id'] . "-slider' class='mmb-slider' data-value='".$meta."' data-min='".$field['min']."' data-max='".$field['max']."' data-step='".$field['step']."'></div>";
    echo "<input type='text' class='mmb-text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='5' ".( isset( $field['style'] )? "style='{$field['style']}'" : '' )."/>";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Field code editor.
   *
   * @param string  $field
   * @author Ohad Raz
   * @param string  $meta
   * @since 2.1
   * @access public
   */
  public function show_field_code( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    echo "<textarea class='code_text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' data-lang='{$field['syntax']}' ".( isset( $field['style'] )? "style='{$field['style']}'" : '' )." data-theme='{$field['theme']}'>{$meta}</textarea>";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Field hidden.
   *
   * @param string  $field
   * @param string|mixed $meta
   * @since 0.1.3
   * @access public
   */
  public function show_field_hidden( $field, $meta ) {
    //$this->show_field_begin( $field, $meta );
    echo "<input type='hidden' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )."class='mmb-text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' value='{$meta}'/>";
    //$this->show_field_end( $field, $meta );
  }


  /**
   * Show Field Paragraph.
   *
   * @param string  $field
   * @since 0.1.3
   * @access public
   */
  public function show_field_paragraph( $field ) {
    //$this->show_field_begin( $field, $meta );
    echo '<p>'.$field['value'].'</p>';
    //$this->show_field_end( $field, $meta );
  }


  /**
   * Show Field Textarea.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_textarea( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    echo "<textarea class='mmb-textarea large-text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." cols='60' rows='10'>{$meta}</textarea>";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Field Select.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_select( $field, $meta ) {

    if ( ! is_array( $meta ) )
      $meta = (array) $meta;

    $this->show_field_begin( $field, $meta );
    echo "<select ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-select".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}" . ( $field['multiple'] ? "[]' id='{$field['id']}' multiple='multiple'" : "'" ) . ">";
    foreach ( $field['options'] as $key => $value ) {
      echo "<option value='{$key}'" . selected( in_array( $key, $meta ), true, false ) . ">{$value}</option>";
    }
    echo "</select>";
    $this->show_field_end( $field, $meta );

  }


  /**
   * Show Radio Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_radio( $field, $meta ) {

    if ( ! is_array( $meta ) )
      $meta = (array) $meta;

    $this->show_field_begin( $field, $meta );
    foreach ( $field['options'] as $key => $value ) {
      echo "<input type='radio' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-radio".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' value='{$key}'" . checked( in_array( $key, $meta ), true, false ) . " /> <span class='mmb-radio-label'>{$value}</span>";
    }
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Checkbox Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_checkbox( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    echo "<input type='checkbox' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='rw-checkbox".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}'" . checked( !empty( $meta ), true, false ) . " />";
    $this->show_field_end( $field, $meta );

  }


  /**
   * Show Wysiwig Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_wysiwyg( $field, $meta, $in_repeater = false ) {
    $this->show_field_begin( $field, $meta );

    if ( $in_repeater )
      echo "<textarea class='mmb-wysiwyg theEditor large-text".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' cols='60' rows='10'>{$meta}</textarea>";
    else {
      // Use new wp_editor() since WP 3.3
      $settings = ( isset( $field['settings'] ) && is_array( $field['settings'] )? $field['settings']: array() );
      $settings['editor_class'] = 'mmb-wysiwyg'.( isset( $field['class'] )? ' ' . $field['class'] : '' );
      $id = str_replace( "_", "", $this->strip_numeric( strtolower( $field['id'] ) ) );
      wp_editor( html_entity_decode( $meta ), $id, $settings );
    }
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show File Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_file( $field, $meta ) {
    wp_enqueue_media();
    $this->show_field_begin( $field, $meta );

    $std      = isset( $field['std'] )? $field['std'] : array( 'id' => '', 'url' => '' );
    $multiple = isset( $field['multiple'] )? $field['multiple'] : false;
    $multiple = ( $multiple )? "multiFile '" : "";
    $name     = esc_attr( $field['id'] );
    $value    = isset( $meta['id'] ) ? $meta : $std;
    $has_file = ( empty( $value['url'] ) )? false : true;
    $type     = isset( $field['mime_type'] )? $field['mime_type'] : '';
    $ext      = isset( $field['ext'] )? $field['ext'] : '';
    $type     = ( is_array( $type )? implode( "|", $type ) : $type );
    $ext      = ( is_array( $ext )? implode( "|", $ext ) : $ext );
    $id       = $field['id'];
    $li       = ( $has_file )? "<li><a href='{$value['url']}' target='_blank'>{$value['url']}</a></li>": "";

    echo "<span class='simplePanelfilePreview'><ul>{$li}</ul></span>";
    echo "<input type='hidden' name='{$name}[id]' value='{$value['id']}'/>";
    echo "<input type='hidden' name='{$name}[url]' value='{$value['url']}'/>";
    if ( $has_file )
      echo "<input type='button' class='{$multiple} button simplePanelfileUploadclear' id='{$id}' value='Remove File' data-mime_type='{$type}' data-ext='{$ext}'/>";
    else
      echo "<input type='button' class='{$multiple} button simplePanelfileUpload' id='{$id}' value='Add File' data-mime_type='{$type}' data-ext='{$ext}'/>";

    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Image Field.
   *
   * @param array   $field
   * @param array   $meta
   * @since 3.2.0
   * @access public
   */
  public function show_field_image( $field, $meta ) {
    wp_enqueue_media();
    $this->show_field_begin( $field, $meta );

    $std          = isset( $field['std'] ) ? $field['std'] : array( 'id' => '', 'url' => '' );
    $name         = esc_attr( $field['id'] );
    $value        = isset( $meta['id'] ) ? $meta : $std;

    $value['url'] = isset( $meta['src'] ) ? $meta['src'] : $value['url']; //backwards capability
    $has_image    = empty( $value['url'] ) ? false : true;

    $size         = isset( $field['size'] ) ? $field['size'] : 'thumbnail';
    $class        = isset( $field['class'] ) ? $field['class'] : '';
    $id           = $field['id'];
    $multiple     = isset( $field['multiple'] ) ? $field['multiple'] : false;
    $multiple     = ( $multiple )? "multiFile " : "";
    $hide_remove  = isset( $field['hide_remove'] ) ? $field['hide_remove'] : false;
    $remove_class  = $hide_remove ? 'hideRemove' : '';

    if ( ! is_array( $size ))
      $size = $this->get_width_height($size);

    $width = $size[0];
    $height = $size[1];
    $image = $has_image ? wp_get_attachment_image_src( $value['id'], $size ) : array( $this->class_path . '/images/photo.png', 150, 150 );
    echo "<span class='simplePanelImagePreview'>";
    echo "<img class='{$class}' src='{$image[0]}' style='height: auto; max-height: {$height}px; width: auto; max-width: {$width}px;' />";
    echo "</span>";
    echo "<input type='hidden' name='{$name}[id]' value='{$value['id']}'/>";
    echo "<input type='hidden' name='{$name}[url]' value='{$value['url']}'/>";
    if ( ! $has_image ) {
      echo "<input class='{$multiple} {$remove_class} button simplePanelimageUpload' id='{$id}' value='Add Image' type='button'/>";
    } elseif ( ! $hide_remove ) {
      echo "<input class='{$multiple} button simplePanelimageUploadclear' id='{$id}' value='Remove Image' type='button'/>";
    }
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Color Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_color( $field, $meta ) {

    if ( empty( $meta ) )
      $meta = '#';

    $this->show_field_begin( $field, $meta );
    if ( wp_style_is( 'wp-color-picker', 'registered' ) ) { //iris color picker since 3.5
      echo "<input class='mmb-color-iris".( isset( $field['class'] )? " {$field['class']}": "" )."' type='text' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='8' />";
    } else {
      echo "<input class='mmb-color".( isset( $field['class'] )? " {$field['class']}": "" )."' type='text' name='{$field['id']}' id='{$field['id']}' value='{$meta}' size='8' />";
      echo "<input type='button' class='mmb-color-select button' rel='{$field['id']}' value='" . __( 'Select a color' , 'apc' ) . "'/>";
      echo "<div style='display:none' class='mmb-color-picker' rel='{$field['id']}'></div>";
    }
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Checkbox List Field
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_checkbox_list( $field, $meta ) {

    if ( ! is_array( $meta ) )
      $meta = (array) $meta;

    $this->show_field_begin( $field, $meta );

    $html = array();

    foreach ( $field['options'] as $key => $value ) {
      $html[] = "<input type='checkbox' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )."  class='mmb-checkbox_list".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}[]' value='{$key}'" . checked( in_array( $key, $meta ), true, false ) . " /> {$value}";
    }

    echo implode( '<br />' , $html );

    $this->show_field_end( $field, $meta );

  }


  /**
   * Show Date Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_date( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    echo "<input type='text'  ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-date".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' rel='{$field['format']}' value='{$meta}' size='30' />";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show time field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_time( $field, $meta ) {
    $this->show_field_begin( $field, $meta );
    $ampm = ( $field['ampm'] )? 'true' : 'false';
    echo "<input type='text'  ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-time".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}' id='{$field['id']}' data-ampm='{$ampm}' rel='{$field['format']}' value='{$meta}' size='30' />";
    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Posts field.
   * used creating a posts/pages/custom types checkboxlist or a select dropdown
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   */
  public function show_field_posts( $field, $meta ) {
    global $post;

    if ( !is_array( $meta ) ) $meta = (array) $meta;
    $this->show_field_begin( $field, $meta );
    $options = $field['options'];
    $posts = get_posts( $options['args'] );
    // checkbox_list
    if ( 'checkbox_list' == $options['type'] ) {
      foreach ( $posts as $p ) {
        echo "<input type='checkbox' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-posts-checkbox".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}[]' value='$p->ID'" . checked( in_array( $p->ID, $meta ), true, false ) . " /> $p->post_title<br/>";
      }
    }
    // select
    else {
      echo "<select ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-posts-select".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}" . ( $field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'" ) . ">";
      if ( isset( $field['emptylabel'] ) )
        echo '<option value="-1">'.( isset( $field['emptylabel'] )? $field['emptylabel']: __( 'Select ...', 'mmb' ) ).'</option>';
      foreach ( $posts as $p ) {
        echo "<option value='$p->ID'" . selected( in_array( $p->ID, $meta ), true, false ) . ">$p->post_title</option>";
      }
      echo "</select>";
    }

    $this->show_field_end( $field, $meta );
  }


  /**
   * Show Taxonomy field.
   * used creating a category/tags/custom taxonomy checkboxlist or a select dropdown
   *
   * @param string  $field
   * @param string  $meta
   * @since 1.0
   * @access public
   *
   * @uses get_terms()
   */
  public function show_field_taxonomy( $field, $meta ) {
    global $post;

    if ( !is_array( $meta ) ) $meta = (array) $meta;
    $this->show_field_begin( $field, $meta );
    $options = $field['options'];
    $terms = get_terms( $options['taxonomy'], $options['args'] );

    // checkbox_list
    if ( 'checkbox_list' == $options['type'] ) {
      foreach ( $terms as $term ) {
        echo "<input type='checkbox' ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-tax-checkbox".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}[]' value='$term->slug'" . checked( in_array( $term->slug, $meta ), true, false ) . " /> $term->name<br/>";
      }
    }
    // select
    else {
      echo "<select ".( isset( $field['style'] )? "style='{$field['style']}' " : '' )." class='mmb-tax-select".( isset( $field['class'] )? ' ' . $field['class'] : '' )."' name='{$field['id']}" . ( $field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'" ) . ">";
      foreach ( $terms as $term ) {
        echo "<option value='$term->slug'" . selected( in_array( $term->slug, $meta ), true, false ) . ">$term->name</option>";
      }
      echo "</select>";
    }

    $this->show_field_end( $field, $meta );
  }


  /**
   * Show conditinal Checkbox Field.
   *
   * @param string  $field
   * @param string  $meta
   * @since 2.9.9
   * @access public
   */
  public function show_field_cond( $field, $meta ) {

    $this->show_field_begin( $field, $meta );
    $checked = false;
    if ( is_array( $meta ) && isset( $meta['enabled'] ) && $meta['enabled'] == 'on' ) {
      $checked = true;
    }
    echo "<input type='checkbox' class='conditinal_control' name='{$field['id']}[enabled]' id='{$field['id']}'" . checked( $checked, true, false ) . " />";
    //start showing the fields
    $display = ( $checked )? '' :  ' style="display: none;"';

    echo '<div class="conditinal_container"'.$display.'><table>';
    foreach ( (array)$field['fields'] as $f ) {
      //reset var $id for cond
      $id = '';
      $id = $field['id'].'['.$f['id'].']';
      $m = '';
      $m = ( isset( $meta[$f['id']] ) ) ? $meta[$f['id']]: '';
      $m = ( $m !== '' ) ? $m : ( isset( $f['std'] )? $f['std'] : '' );
      if ( 'image' != $f['type'] && $f['type'] != 'repeater' )
        $m = is_array( $m ) ? array_map( 'esc_attr', $m ) : esc_attr( $m );
      //set new id for field in array format
      $f['id'] = $id;
      echo '<tr>';
      call_user_func( array( $this, 'show_field_' . $f['type'] ), $f, $m );
      echo '</tr>';
    }
    echo '</table></div>';
    $this->show_field_end( $field, $meta );
  }


  /**
   * Save Data from Metabox
   *
   * @param string  $post_id
   * @since 1.0
   * @access public
   */
  public function save( $post_id ) {

    global $post_type;

    $post_type_object = get_post_type_object( $post_type );

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                      // Check Autosave
      || ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] )        // Check Revision
      || ( ! in_array( $post_type, $this->_meta_box['pages'] ) )                  // Check if current post type is supported.
      || ( ! check_admin_referer( basename( __FILE__ ), 'multi_meta_box_nonce' ) )    // Check nonce - Security
      || ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) )  // Check permission
      {
      return $post_id;
    }

    foreach ( $this->_fields as $field ) {

      $name = $field['id'];
      $type = $field['type'];
      $old = get_post_meta( $post_id, $name, ! $field['multiple'] );
      $new = ( isset( $_POST[$name] ) ) ? $_POST[$name] : ( ( $field['multiple'] ) ? array() : '' );


      // Validate meta value
      if ( class_exists( 'at_Meta_Box_Validate' ) && method_exists( 'at_Meta_Box_Validate', $field['validate_func'] ) ) {
        $new = call_user_func( array( 'at_Meta_Box_Validate', $field['validate_func'] ), $new );
      }

      //skip on Paragraph field
      if ( $type != "paragraph" ) {

        // Call defined method to save meta value, if there's no methods, call common one.
        $save_func = 'save_field_' . $type;
        if ( method_exists( $this, $save_func ) ) {
          call_user_func( array( $this, 'save_field_' . $type ), $post_id, $field, $old, $new );
        } else {
          $this->save_field( $post_id, $field, $old, $new );
        }
      }

    } // End foreach
  }

  /**
   * Common function for saving fields.
   *
   * @param string  $post_id
   * @param string  $field
   * @param string  $old
   * @param string|mixed $new
   * @since 1.0
   * @access public
   */
  public function save_field( $post_id, $field, $old, $new ) {
    $name = $field['id'];
    delete_post_meta( $post_id, $name );
    if ( $new === '' || $new === array() )
      return;
    if ( $field['multiple'] ) {
      foreach ( $new as $add_new ) {
        add_post_meta( $post_id, $name, $add_new, false );
      }
    } else {
      update_post_meta( $post_id, $name, $new );
    }
  }

  /**
   * function for saving image field.
   *
   * @param string  $post_id
   * @param string  $field
   * @param string  $old
   * @param string|mixed $new
   * @since 1.7
   * @access public
   */
  public function save_field_image( $post_id, $field, $old, $new ) {
    $name = $field['id'];
    delete_post_meta( $post_id, $name );
    if ( $new === '' || $new === array() || $new['id'] == '' || $new['url'] == '' )
      return;

    update_post_meta( $post_id, $name, $new );
  }


  /*
   * Save Wysiwyg Field.
   *
   * @param string $post_id
   * @param string $field
   * @param string $old
   * @param string $new
   * @since 1.0
   * @access public
   */
  public function save_field_wysiwyg( $post_id, $field, $old, $new ) {
    $id = str_replace( "_", "", $this->strip_numeric( strtolower( $field['id'] ) ) );
    $new = ( isset( $_POST[$id] ) ) ? $_POST[$id] : ( ( $field['multiple'] ) ? array() : '' );
    $this->save_field( $post_id, $field, $old, $new );
  }


  /**
   * Save repeater Fields.
   *
   * @param string  $post_id
   * @param string  $field
   * @param string|mixed $old
   * @param string|mixed $new
   * @since 1.0
   * @access public
   */
  public function save_field_repeater( $post_id, $field, $old, $new ) {
    if ( is_array( $new ) && count( $new ) > 0 ) {
      foreach ( $new as $n ) {
        foreach ( $field['fields'] as $f ) {
          $type = $f['type'];
          switch ( $type ) {
          case 'wysiwyg':
            $n[$f['id']] = wpautop( $n[$f['id']] );
            break;
          default:
            break;
          }
        }
        if ( !$this->is_array_empty( $n ) )
          $temp[] = $n;
      }
      if ( isset( $temp ) && count( $temp ) > 0 && !$this->is_array_empty( $temp ) ) {
        update_post_meta( $post_id, $field['id'], $temp );
      }else {
        //  remove old meta if exists
        delete_post_meta( $post_id, $field['id'] );
      }
    }else {
      //  remove old meta if exists
      delete_post_meta( $post_id, $field['id'] );
    }
  }


  /**
   * Save File Field.
   *
   * @param string  $post_id
   * @param string  $field
   * @param string  $old
   * @param string  $new
   * @since 1.0
   * @access public
   */
  public function save_field_file( $post_id, $field, $old, $new ) {

    $name = $field['id'];
    delete_post_meta( $post_id, $name );
    if ( $new === '' || $new === array() || $new['id'] == '' || $new['url'] == '' )
      return;

    update_post_meta( $post_id, $name, $new );
  }

  /**
   * Save repeater File Field.
   *
   * @param string  $post_id
   * @param string  $field
   * @param string  $old
   * @param string  $new
   * @since 1.0
   * @access public
   * @deprecated 3.0.7
   */
  public function save_field_file_repeater( $post_id, $field, $old, $new ) {}

  /**
   * Add missed values for meta box.
   *
   * @since 1.0
   * @access public
   */
  public function add_missed_values() {

    // Default values for meta box
    $this->_meta_box = array_merge( array( 'context' => 'normal', 'priority' => 'high', 'pages' => array( 'post' ) ), (array)$this->_meta_box );

    // Default values for fields
    foreach ( $this->_fields as &$field ) {

      $multiple = in_array( $field['type'], array( 'checkbox_list', 'file', 'image' ) );
      $std = $multiple ? array() : '';
      $format = 'date' == $field['type'] ? 'yy-mm-dd' : ( 'time' == $field['type'] ? 'hh:mm' : '' );

      $field = array_merge( array( 'multiple' => $multiple, 'std' => $std, 'desc' => '', 'format' => $format, 'validate_func' => '' ), $field );

    } // End foreach

  }


  /**
   * Check if field with $type exists.
   *
   * @param string  $type
   * @since 1.0
   * @access public
   */
  public function has_field( $type ) {
    //faster search in single dimention array.
    if ( count( $this->field_types ) > 0 ) {
      return in_array( $type, $this->field_types );
    }

    //run once over all fields and store the types in a local array
    $temp = array();
    foreach ( $this->_fields as $field ) {
      $temp[] = $field['type'];
      if ( 'repeater' == $field['type']  || 'cond' == $field['type'] ) {
        foreach ( (array)$field["fields"] as $repeater_field ) {
          $temp[] = $repeater_field["type"];
        }
      }
    }

    //remove duplicates
    $this->field_types = array_unique( $temp );
    //call this function one more time now that we have an array of field types
    return $this->has_field( $type );
  }


  /**
   * Check if current page is edit page.
   *
   * @since 1.0
   * @access public
   */
  public function is_edit_page() {
    global $pagenow;
    return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
  }


  /**
   * Fixes the odd indexing of multiple file uploads.
   *
   * Goes from the format:
   * $_FILES['field']['key']['index']
   * to
   * The More standard and appropriate:
   * $_FILES['field']['index']['key']
   *
   * @param string  $files
   * @since 1.0
   * @access public
   */
  public function fix_file_array( &$files ) {

    $output = array();

    foreach ( $files as $key => $list ) {
      foreach ( $list as $index => $value ) {
        $output[$index][$key] = $value;
      }
    }

    return $output;

  }


  /**
   * Get proper JQuery UI version.
   *
   * Used in order to not conflict with WP Admin Scripts.
   *
   * @since 1.0
   * @access public
   */
  public function get_jqueryui_ver() {

    global $wp_version;

    if ( version_compare( $wp_version, '3.1', '>=' ) ) {
      return '1.8.10';
    }

    return '1.7.3';

  }


  /**
   *  Add Field to meta box (generic function)
   *  @author Ohad Raz
   *  @since 1.2
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   */
  public function add_field( $id, $args ) {
    $new_field = array(
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>''
    );
    $new_field = array_merge( $new_field, $args );
    $this->_fields[] = $new_field;
  }


  /**
   *  Add Text Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'validate_func' => // validate function, string optional
   *   @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_text( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'text',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Text Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Slider Field to meta box
   *  @author Robert Miller
   *  @since 3.2.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'min' => // lowest allowed value, string optional
   *    'max' => // highest allowed value, string optional
   *    'step' => // how much to increment on each slider movement, string optional
   *    'style' =>   // custom style for field, string optional
   *  @param $repeater bool  is this a field inside a repeater? true|false(default)
   */
  public function add_slider( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'slider',
      'id'=> $id,
      'std' => '50',
      'min' => '0',
      'max' => '255',
      'step' => '1',
      'desc' => '',
      'style' =>'',
      'name' => 'Slider Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Number Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'validate_func' => // validate function, string optional
   *   @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_number( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'number',
      'id'=> $id,
      'std' => '0',
      'desc' => '',
      'style' =>'',
      'name' => 'Number Field',
      'step' => '1',
      'min' => '0'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add code Editor to meta box
   *  @author Ohad Raz
   *  @since 2.1
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'syntax' =>   // syntax language to use in editor (php,javascript,css,html)
   *    'validate_func' => // validate function, string optional
   *   @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_code( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'code',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Code Editor Field',
      'syntax' => 'php',
      'theme' => 'defualt'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Hidden Field to meta box
   *  @author Ohad Raz
   *  @since 0.1.3
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'validate_func' => // validate function, string optional
   *   @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_hidden( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'hidden',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Text Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Paragraph to meta box
   *  @author Ohad Raz
   *  @since 0.1.3
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $value  paragraph html
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_paragraph( $id, $args, $repeater = false ) {
    $new_field = array( 'type' => 'paragraph', 'id'=> $id, 'value' => '' );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Checkbox Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_checkbox( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'checkbox',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Checkbox Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add CheckboxList Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $options (array)  array of key => value pairs for select options
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   *
   *   @return : remember to call: $checkbox_list = get_post_meta(get_the_ID(), 'meta_name', false);
   *   which means the last param as false to get the values in an array
   */
  public function add_checkbox_list( $id, $options, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'checkbox_list',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Checkbox List Field',
      'options' =>$options,
      'multiple' => true
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Textarea Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_textarea( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'textarea',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'',
      'name' => 'Textarea Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Select Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string field id, i.e. the meta key
   *  @param $options (array)  array of key => value pairs for select options
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, (array) optional
   *    'multiple' => // select multiple values, optional. Default is false.
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_select( $id, $options, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'select',
      'id'=> $id,
      'std' => array(),
      'desc' => '',
      'style' =>'',
      'name' => 'Select Field',
      'multiple' => false,
      'options' => $options
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Radio Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string field id, i.e. the meta key
   *  @param $options (array)  array of key => value pairs for radio options
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_radio( $id, $options, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'radio',
      'id'=> $id,
      'std' => array(),
      'desc' => '',
      'style' =>'',
      'name' => 'Radio Field',
      'options' => $options
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Date Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *    'format' => // date format, default yy-mm-dd. Optional. Default "'d MM, yy'"  See more formats here: http://goo.gl/Wcwxn
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_date( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'date',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'format'=>'d MM, yy',
      'name' => 'Date Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Time Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string- field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *    'format' => // time format, default hh:mm. Optional. See more formats here: http://goo.gl/83woX
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_time( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'time',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'format'=>'hh:mm',
      'name' => 'Time Field',
      'ampm' => false
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Color Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_color( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'color',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'name' => 'ColorPicker Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Image Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_image( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'image',
      'id'=> $id,
      'desc' => '',
      'name' => 'Image Field',
      'std' => array(
        'id' => '',
        'url' => ''
      ),
      'multiple' => false
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add File Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_file( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'file',
      'id'=> $id,
      'desc' => '',
      'name' => 'File Field',
      'multiple' => false,
      'std' => array(
        'id' => '',
        'url' => ''
      )
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add WYSIWYG Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional Default 'width: 300px; height: 400px'
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_wysiwyg( $id, $args, $repeater = false ) {
    $new_field = array(
      'type' => 'wysiwyg',
      'id'=> $id,
      'std' => '',
      'desc' => '',
      'style' =>'width: 300px; height: 400px',
      'name' => 'WYSIWYG Editor Field'
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add Taxonomy Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $options mixed|array options of taxonomy field
   *    'taxonomy' =>    // taxonomy name can be category,post_tag or any custom taxonomy default is category
   *    'type' =>  // how to show taxonomy? 'select' (default) or 'checkbox_list'
   *    'args' =>  // arguments to query taxonomy, see http://goo.gl/uAANN default ('hide_empty' => false)
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_taxonomy( $id, $options, $args, $repeater = false ) {
    $temp = array(
      'args' => array( 'hide_empty' => 0 ),
      'tax' => 'category',
      'type' => 'select'
    );

    $options = array_merge( $temp, $options );

    $new_field = array(
      'type' => 'taxonomy',
      'id' => $id,
      'desc' => '',
      'name' => 'Taxonomy Field',
      'options' => $options
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add posts Field to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $options mixed|array options of taxonomy field
   *    'post_type' =>    // post type name, 'post' (default) 'page' or any custom post type
   *    'type' =>  // how to show posts? 'select' (default) or 'checkbox_list'
   *    'args' =>  // arguments to query posts, see http://goo.gl/is0yK default ('posts_per_page' => -1)
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'validate_func' => // validate function, string optional
   *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
   */
  public function add_posts( $id, $options, $args, $repeater = false ) {
    $post_type = isset( $options['post_type'] ) ? $options['post_type'] : ( isset( $args['post_type'] ) ? $args['post_type'] : 'post' );
    $type = isset( $options['type'] ) ? $options['type'] : 'select';
    $q = array( 'posts_per_page' => -1, 'post_type' => $post_type );

    if ( isset( $options['args'] ) )
      $q = array_merge( $q, (array)$options['args'] );

    $options = array(
      'post_type' => $post_type,
      'type' => $type,
      'args' => $q
    );

    $new_field = array(
      'type' => 'posts',
      'id' => $id,
      'desc' => '',
      'name' => 'Posts Field',
      'options' => $options,
      'multiple' => false
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    } else {
      return $new_field;
    }
  }


  /**
   *  Add repeater Field Block to meta box
   *  @author Ohad Raz
   *  @since 1.0
   *  @access public
   *  @param $id string  field id, i.e. the meta key
   *  @param $args mixed|array
   *    'name' => // field name/label string optional
   *    'desc' => // field description, string optional
   *    'std' => // default value, string optional
   *    'style' =>   // custom style for field, string optional
   *    'fields' => //fields to repeater
   */
  public function add_repeater_block( $id, $args ) {
    $new_field = array(
      'type'     => 'repeater',
      'id'       => $id,
      'name'     => 'Reapeater Field',
      'fields'   => array(),
      'inline'   => false,
      'sortable' => false
    );

    $new_field = array_merge( $new_field, $args );

    $this->_fields[] = $new_field;
  }


  /**
   *  Add Checkbox conditional Field to Page
   *  @author Ohad Raz
   *  @since 2.9.9
   *  @access public
   *  @param $id string  field id, i.e. the key
   *  @param $args mixed|array
   *    'name' =>  field name/label string optional
   *    'desc' =>  field description, string optional
   *    'std' =>  default value, string optional
   *    'fields' =>  list of fields to show conditionally.
   */
  public function add_condition( $id, $args, $repeater = false ) {
    $new_field = array(
      'type'   => 'cond',
      'id'     => $id,
      'name'   => 'Conditional Field',
      'desc'   => '',
      'std'    => '',
      'style'  =>'',
      'fields' => array()
    );

    $new_field = array_merge( $new_field, $args );

    if ( false === $repeater ) {
      $this->_fields[] = $new_field;
    }else {
      return $new_field;
    }
  }


  /**
   * Finish Declaration of Meta Box
   *
   * @author Ohad Raz
   * @since 1.0
   * @access public
   */
  public function finish() {
    $this->add_missed_values();
  }


  /**
   * Helper function to check for empty arrays
   *
   * @author Ohad Raz
   * @since 1.5
   * @access public
   * @param unknown $args mixed|array
   */
  public function is_array_empty( $array ) {
    if ( !is_array( $array ) )
      return true;

    foreach ( $array as $a ) {
      if ( is_array( $a ) ) {
        foreach ( $a as $sub_a ) {
          if ( !empty( $sub_a ) && $sub_a != '' )
            return false;
        }
      } else {
        if ( !empty( $a ) && $a != '' )
          return false;
      }
    }
    return true;
  }


  /**
   * validate_upload_file_type
   *
   * Checks if the uploaded file is of the expected format
   *
   * @author Ohad Raz <admin@bainternet.info>
   * @since 3.0.7
   * @access public
   * @uses get_allowed_mime_types() to check allowed types
   * @param array   $file uploaded file
   * @return array file with error on mismatch
   */
  function validate_upload_file_type( $file ) {
    if ( isset( $_POST['uploadeType'] ) && !empty( $_POST['uploadeType'] )
      && isset( $_POST['uploadeType'] ) && $_POST['uploadeType'] == 'my_meta_box' ) {

      $allowed = explode( "|", $_POST['uploadeType'] );
      $ext =  substr( strrchr( $file['name'], '.' ), 1 );

      if ( !in_array( $ext, (array)$allowed ) ) {
        $file['error'] = __( "Sorry, you cannot upload this file type for this field." );
        return $file;
      }

      foreach ( get_allowed_mime_types() as $key => $value ) {
        if ( strpos( $key, $ext ) || $key == $ext )
          return $file;
      }

      $file['error'] = __( "Sorry, you cannot upload this file type for this field." );
    }
    return $file;
  }


  /**
   * function to sanitize field id
   *
   * @author Ohad Raz <admin@bainternet.info>
   * @since 3.0.7
   * @access public
   * @param string  $str string to sanitize
   * @return string      sanitized string
   */
  public function idfy( $str ) {
    return str_replace( " ", "_", $str );

  }


  /**
   * strip_numeric Strip number from string
   *
   * @author Ohad Raz <admin@bainternet.info>
   * @since 3.0.7
   * @access public
   * @param string  $str
   * @return string number less string
   */
  public function strip_numeric( $str ) {
    return trim( str_replace( range( 0, 9 ), '', $str ) );
  }


  /**
   * get_width_height return numeric width and height from WordPress image size
   *
   * @author Robert Miller <rob@strawberryjellyfish.com>
   * @since 3.2.3
   * @access public
   * @param string  $str
   * @return array(width, height)
   */
  public function get_width_height( $size ) {
    if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
      $sizes['width'] = get_option( $size . '_size_w' );
      $sizes['height'] = get_option( $size . '_size_h' );
      $sizes['crop'] = (bool) get_option( $size . '_crop' );
    } elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
      $sizes = array(
        'width' => $_wp_additional_image_sizes[ $size ]['width'],
        'height' => $_wp_additional_image_sizes[ $size ]['height'],
        'crop' =>  $_wp_additional_image_sizes[ $size ]['crop']
      );
    }
    return array($sizes['width'], $sizes['height']);
  }

  /**
   * load_textdomain
   *
   * @author Ohad Raz
   * @since 2.9.4
   * @return void
   */
  public function load_textdomain() {
    load_textdomain( 'mmb', dirname( __FILE__ ) . '/lang/' . get_locale() .'.mo' );
  }
} // End Class

endif; // End Check Class Exists
