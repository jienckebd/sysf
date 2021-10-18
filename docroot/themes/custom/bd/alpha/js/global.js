(function ($, window, Drupal, drupalSettings) {

  /**
   *
   * @type {{detach: Drupal.behaviors.AJAX.detach, attach: Drupal.behaviors.AJAX.attach}}
   */
  Drupal.behaviors.alphaDomEventApi = {
    attach: function (context, settings) {

      $("[data-dom-event]").once('dom-event-api').each(function () {

        var domEvent = $(this).attr('data-dom-event');
        var domTarget = $(this).attr('data-dom-event-target');
        var domCallback = $(this).attr('data-dom-event-callback');
        var domModifier = $(this).attr('data-dom-event-modifier');

        $(this).on(domEvent, function(e) {

          $(domTarget)[domCallback](domModifier);

        });

      });

    },
    detach: function (context, settings, trigger) {
    }
  };

})(jQuery, window, Drupal, drupalSettings);
