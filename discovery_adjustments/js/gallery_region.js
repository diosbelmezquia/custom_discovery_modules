(function ($, Drupal) {
  Drupal.behaviors.gallery_region = {
    attach: function (context, settings) {

      // Add region by default con create or edit a node.
      $("select[name='langcode[0][value]']", context).on('change', function (e) {
        var optionSelected = $("option:selected", this);
        var valueSelected = this.value;
        if (valueSelected == 'es') {
          $("input[name='field_region_term[16874]']").prop("checked", true);
          // @TODO Change by "Brasil Country" checkbox name.
          $("input[name='field_region_term[16875]']").prop("checked", false);
        }
        if (valueSelected == 'pt-br') {
          $("input[name='field_region_term[16874]']").prop("checked", false);
          // @TODO Change by "Brasil Country" checkbox name.
          $("input[name='field_region_term[16875]']").prop("checked", true);
        }
      });

    }
  };
})(jQuery, Drupal);
