(function ($, Drupal) {

  $(document).ready(function() {
    $('#more_options').click(function() {
      $('.layout-node-form .layout-region-node-secondary').toggle();
    });
  });

  // Init - show Media image for current input.
  $('input[name="field_preview_media[0][target_id]"], input[name="field_poster[0][target_id]"]').each(function(i) {
    // Attach loader...
    $(this).parent().append('<h3 class="loader-img-media" id="' + $(this).attr('name') + '">Loading image...</h3>');
    $('h3.loader-img-media').hide();
    if ($(this).val()) {
      getMediaImageAutocomplete($(this).val(), $(this).attr('name'));
    }
  });
  // Get preview image for media entity autocomplete.
  $('input[name="field_preview_media[0][target_id]"], input[name="field_poster[0][target_id]"]').on('change', function (e) {
    getMediaImageAutocomplete($(this).val(), $(this).attr('name'));
  });

  // Get media image for autocomplete input.
  function getMediaImageAutocomplete(value, field) {
    $.ajax({
      url: Drupal.url('discovery_adjustments/get_image_media'),
      type: 'POST',
      data: {
        input: value,
        name:  field
      },
      dataType: 'json',
      beforeSend: function() {
        // Show loader...
        $('input.form-autocomplete[name="' + field + '"]').parent().find('h3').show();
      },
      success: function success(jsonResponse) {
        if (jsonResponse !== false) {
          var current_input = "input[name='" + jsonResponse.field_name + "']";
          $(current_input).parent().parent().find('img').remove();
          $(current_input).parent().parent().append(jsonResponse.image);
        }
      },
      complete:function(data) {
        // Hide loader...
        $('input.form-autocomplete[name="' + field + '"]').parent().find('h3').hide();
      }
    });
  }

  // Get node data for carousels.
  function getNodeData(title, field_name) {
    $.ajax({
      url: Drupal.url('discovery_adjustments/get_node_data'),
      type: 'POST',
      data: {
        node_title: title,
      },
      dataType: 'json',
      success: function success(jsonResponse) {
        if (jsonResponse !== false) {
          var current_input = "input[name='" + field_name + "']";
          $(current_input).parent().parent().parent().find('span.node-status').replaceWith('<span class="node-status">' + jsonResponse.status + '</span>');
          $(current_input).parent().parent().parent().find('span.node-region').replaceWith('<span class="node-region">' + jsonResponse.regions + '</span>');
          $(current_input).parent().parent().parent().find('span.node-type').replaceWith('<span class="node-type">' + jsonResponse.type + '</span>');
          $(current_input).parent().parent().parent().find('a.node-edit').replaceWith('<a class="node-edit" target="_blank" href="' + jsonResponse.edit + '">Edit</a>');
        }
      },
    });
  }

  // Save the language of the current node.
  localStorage.setItem('currentNodeLangcode', $("select[name='langcode[0][value]']").val());
  // Add the query content_langcode to the url of all browser buttons.
  $("input[data-drupal-selector*='entity-browser-path']").each(function() {
     var browser_url = $(this).val();
    $(this).val(browser_url + '&content_langcode=' + localStorage.getItem('currentNodeLangcode'));
  });

  // Update browser urls when changing language.
  $("select[name='langcode[0][value]']").on('change', function (e) {
    var newNodeLangcode = $(this).val();
    $("input[data-drupal-selector*='entity-browser-path']").each(function() {
      var currentNodeLangcode = localStorage.getItem('currentNodeLangcode');
      var url_replaced = $(this).val().replace('&content_langcode=' + currentNodeLangcode, '&content_langcode=' + newNodeLangcode);
      $(this).val(url_replaced);
    });
    localStorage.setItem('currentNodeLangcode', newNodeLangcode);
  });

  Drupal.behaviors.component_behaviors = {
    attach: function (context, settings) {
      // Show field to defiend color in slider for video inline component.
      $('.field--name-field-slider-bg-color', context).hide();
      if ($('tr.paragraph-type--video-inline', context).length) {
        $('.field--name-field-slider-bg-color').show();
      }

      // Get data from node entity in carouseles.
      $('input.form-autocomplete').on('change', function (e) {
        getNodeData($(this).val(), $(this).attr('name'));
      });

    }
  };
})(jQuery, Drupal);
