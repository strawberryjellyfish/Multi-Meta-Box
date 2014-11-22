/**
 * Multi Meta Box Class JS
 *
 * JavaScript required by multi meta boxes.
 * Robert Miller <rob@strawberryjellyfish.com>
 *
 * Derived from work on All Types Meta Box Class
 * by Ohad Raz <admin@bainternet.info>
 * @since 1.0
 */

var $ = jQuery.noConflict();

var e_d_count = 0;
var Ed_array = Array;
//fix editor on window resize
jQuery(document).ready(function($) {
  //editor resize fix
  $(window).resize(function() {
    $.each(Ed_array, function() {
      var ee = this;
      $(ee.getScrollerElement()).width(100); // set this low enough
      width = $(ee.getScrollerElement()).parent().width();
      $(ee.getScrollerElement()).width(width); // set it to
      ee.refresh();
    });
  });
});

function update_repeater_fields() {
  _metabox_fields.init();
}
//metabox fields object
var _metabox_fields = {
  oncefancySelect: false,
  init: function() {
    if (!this.oncefancySelect) {
      this.fancySelect();
      this.oncefancySelect = true;
    }
    this.load_code_editor();
    this.load_conditinal();
    this.load_time_picker();
    this.load_date_picker();
    this.load_color_picker();
    this.load_slider();

    // repeater Field
    $(".mmb-re-toggle").unbind('click').on('click', function() {
      $(this).parent().find('.mmb-repeater-table').toggle('fast');
    });
    $('.mmb-repeater-remove-button').unbind('click').on('click', function() {
      if (jQuery(this).parent().hasClass("mmb-repeater-control")) {
        jQuery(this).parent().parent().remove();
      } else {
        jQuery(this).parent().remove();
      }
    });
    $('.mmb-repeater-sortable').sortable({
      opacity: 0.6,
      revert: true,
      cursor: 'move',
      handle: '.mmb-repeater-sort-handle',
      placeholder: 'mmb-repeater-sort-highlight'
    });
  },
  fancySelect: function() {
    if ($().select2) {
      $(".mmb-select, .mmb-posts-select, .mmb-tax-select").each(function() {
        if (!$(this).hasClass('no-fancy'))
          $(this).select2();
      });
    }
  },
  get_query_var: function(name) {
    var match = RegExp('[?&]' + name + '=([^&#]*)').exec(location.href);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
  },
  load_code_editor: function() {
    $(".code_text").each(function() {

      // if a code editor is already present, do nothing... #94
      if ($(this).next('.CodeMirror').length) return;

      var lang = $(this).attr("data-lang");
      //php application/x-httpd-php
      //css text/css
      //html text/html
      //javascript text/javascript
      switch (lang) {
        case 'php':
          lang = 'application/x-httpd-php';
          break;
        case 'css':
          lang = 'text/css';
          break;
        case 'html':
          lang = 'text/html';
          break;
        case 'javascript':
          lang = 'text/javascript';
          break;
        default:
          lang = 'application/x-httpd-php';
      }
      var theme = $(this).attr("data-theme");
      switch (theme) {
        case 'default':
          theme = 'default';
          break;
        case 'light':
          theme = 'solarizedLight';
          break;
        case 'dark':
          theme = 'solarizedDark';;
          break;
        default:
          theme = 'default';
      }

      var editor = CodeMirror.fromTextArea(document.getElementById($(this).attr('id')), {
        lineNumbers: true,
        matchBrackets: true,
        mode: lang,
        indentUnit: 4,
        indentWithTabs: true,
        enterMode: "keep",
        tabMode: "shift"
      });
      editor.setOption("theme", theme);
      $(editor.getScrollerElement()).width(100); // set this low enough
      width = $(editor.getScrollerElement()).parent().width();
      $(editor.getScrollerElement()).width(width); // set it to
      editor.refresh();
      Ed_array[e_d_count] = editor;
      e_d_count++;
    });
  },
  load_conditinal: function() {
    $(".conditinal_control").click(function() {
      if ($(this).is(':checked')) {
        $(this).next().show('fast');
      } else {
        $(this).next().hide('fast');
      }
    });
  },
  load_time_picker: function() {
    $('.mmb-time').each(function() {

      var $this = $(this),
        format = $this.attr('rel'),
        aampm = $this.attr('data-ampm');
      if ('true' == aampm)
        aampm = true;
      else
        aampm = false;

      $this.timepicker({
        showSecond: true,
        timeFormat: format,
        ampm: aampm
      });

    });
  },
  load_date_picker: function() {
    $('.mmb-date').each(function() {

      var $this = $(this),
        format = $this.attr('rel');

      $this.datepicker({
        showButtonPanel: true,
        dateFormat: format
      });

    });
  },

  load_slider: function() {
    $('.mmb-slider').each(function() {
      var sliderdiv = $(this),
        options = sliderdiv.data(),
        slider_input = sliderdiv.next('input');

      sliderdiv.slider({
        value: options['value'],
        min: options['min'],
        max: options['max'],
        step: options['step'],
        animate: "fast",
        slide: function(event, ui) {
          $(slider_input).val(ui.value);
        }
      });
      $(slider_input).on('change', function() {
        sliderdiv.slider('value', this.value);
      });
    });
  },

  load_color_picker: function() {
    if ($('.mmb-color-iris').length > 0)
      $('.mmb-color-iris').wpColorPicker();
  },
};
//call object init in delay
window.setTimeout('_metabox_fields.init();', 2000);

//upload fields handler
var simplePanelmedia;
jQuery(document).ready(function($) {
  var simplePanelupload = (function() {
    var inited;
    var file_id;
    var file_url;
    var file_type;

    function init() {
      return {
        image_frame: new Array(),
        file_frame: new Array(),
        hooks: function() {
          $(document).on('click', '.simplePanelimageUpload,.simplePanelfileUpload,.simplePanelimageUploadclear,.simplePanelfileUploadclear', function(event) {
            event.preventDefault();
            event.stopPropagation();
            if ($(this).hasClass('simplePanelfileUpload') || $(this).hasClass('simplePanelimageUpload')) {
              if ($(this).hasClass('simplePanelfileUpload')) {
                inited.upload($(this), 'file');
              } else {
                inited.upload($(this), 'image');
              }
            } else {
              inited.setFields($(this));
              $(inited.file_url).val("");
              $(inited.file_id).val("");
              if ($(this).hasClass('simplePanelimageUploadclear')) {
                inited.setPreview('image', false);
                inited.replaceImageUploadClass($(this));
              } else {
                inited.setPreview('file', false);
                inited.replaceFileUploadClass($(this));
              }
            }
          });
        },
        setFields: function(el) {
          inited.file_url = $(el).prev();
          inited.file_id = $(inited.file_url).prev();
        },
        upload: function(el, utype) {
          inited.setFields(el)
          if (utype == 'image')
            inited.uploadImage($(el));
          else
            inited.uploadFile($(el));
        },
        uploadFile: function(el) {
          // If the media frame already exists, reopen it.
          var mime = $(el).attr('data-mime_type') || '';
          var ext = $(el).attr("data-ext") || false;
          var name = $(el).attr('id');
          var multi = ($(el).hasClass("multiFile") ? true : false);

          if (typeof inited.file_frame[name] !== "undefined") {
            if (ext) {
              inited.file_frame[name].uploader.uploader.param('uploadeType', ext);
              inited.file_frame[name].uploader.uploader.param('uploadeTypecaller', 'multi_meta_box');
            }
            inited.file_frame[name].open();
            return;
          }
          // Create the media frame.

          inited.file_frame[name] = wp.media({
            library: {
              type: mime
            },
            title: jQuery(this).data('uploader_title'),
            button: {
              text: jQuery(this).data('uploader_button_text'),
            },
            multiple: multi // Set to true to allow multiple files to be selected
          });


          // When an image is selected, run a callback.
          inited.file_frame[name].on('select', function() {
            // We set multiple to false so only get one image from the uploader
            attachment = inited.file_frame[name].state().get('selection').first().toJSON();
            // Do something with attachment.id and/or attachment.url here
            $(inited.file_id).val(attachment.id);
            $(inited.file_url).val(attachment.url);
            inited.replaceFileUploadClass(el);
            inited.setPreview('file', true);
          });
          // Finally, open the modal

          inited.file_frame[name].open();
          if (ext) {
            inited.file_frame[name].uploader.uploader.param('uploadeType', ext);
            inited.file_frame[name].uploader.uploader.param('uploadeTypecaller', 'multi_meta_box');
          }
        },
        uploadImage: function(el) {
          var name = $(el).attr('id');
          var multi = ($(el).hasClass("multiFile") ? true : false);
          // If the media frame already exists, reopen it.
          if (typeof inited.image_frame[name] !== "undefined") {
            inited.image_frame[name].open();
            return;
          }
          // Create the media frame.
          inited.image_frame[name] = wp.media({
            library: {
              type: 'image'
            },
            title: jQuery(this).data('uploader_title'),
            button: {
              text: jQuery(this).data('uploader_button_text'),
            },
            multiple: multi // Set to true to allow multiple files to be selected
          });
          // When an image is selected, run a callback.
          inited.image_frame[name].on('select', function() {
            // We set multiple to false so only get one image from the uploader
            attachment = inited.image_frame[name].state().get('selection').first().toJSON();
            // Do something with attachment.id and/or attachment.url here
            $(inited.file_id).val(attachment.id);
            $(inited.file_url).val(attachment.url);
            inited.replaceImageUploadClass(el);
            inited.setPreview('image', true);
          });
          // Finally, open the modal
          inited.image_frame[name].open();
        },
        replaceImageUploadClass: function(el) {
          if ($(el).hasClass("hideRemove")) {
            $(el).hide();
          } else if ($(el).hasClass("simplePanelimageUpload")) {
            $(el).removeClass("simplePanelimageUpload").addClass('simplePanelimageUploadclear').val('Remove Image');
          } else {
            $(el).removeClass("simplePanelimageUploadclear").addClass('simplePanelimageUpload').val('Upload Image');
          }
        },
        replaceFileUploadClass: function(el) {
          if ($(el).hasClass("simplePanelfileUpload")) {
            $(el).removeClass("simplePanelfileUpload").addClass('simplePanelfileUploadclear').val('Remove File');
          } else {
            $(el).removeClass("simplePanelfileUploadclear").addClass('simplePanelfileUpload').val('Upload File');
          }
        },
        setPreview: function(stype, ShowFlag) {
          ShowFlag = ShowFlag || false;
          var fileuri = $(inited.file_url).val();
          if (stype == 'image') {
            if (ShowFlag)
              $(inited.file_id).prev().find('img').attr('src', fileuri).show();
            else
              $(inited.file_id).prev().find('img').attr('src', '').hide();
          } else {
            if (ShowFlag)
              $(inited.file_id).prev().find('ul').append('<li><a href="' + fileuri + '" target="_blank">' + fileuri + '</a></li>');
            else
              $(inited.file_id).prev().find('ul').children().remove();
          }
        }
      }
    }
    return {
      getInstance: function() {
        if (!inited) {
          inited = init();
        }
        return inited;
      }
    }
  })()
  simplePanelmedia = simplePanelupload.getInstance();
  simplePanelmedia.hooks();
});