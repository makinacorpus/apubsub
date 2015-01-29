(function ($) {
  "use strict";

  Drupal.settings.notification = Drupal.settings.notification || {};

  /**
   * Constructor
   *
   * @param url
   * @param element
   * @param options
   * @constructor
   */
  Drupal.NotificationBlock = function (url, element, options) {
    this.defaultDelay = 30;
    this.delay = 30;
    this.threshold = 320;
    this.factor = 1.5;
    this.running = false;
    this.element = element;
    this.url = url;
    this.options = options || {};
    this.neverUnfolded = true;
    this.currentlyDisplayed = [];
  };

  /**
   * Starts timer and run the block content refresh loop
   *
   * @param bool fromStart Restart timer to default value
   */
  Drupal.NotificationBlock.prototype.startTimer = function (fromStart) {

    var self = this,
      delay = this.delay;

    if (this.running) {
      return;
    }

    if (fromStart) {
      delay = 1;
      this.delay = this.defaultDelay;
    }

    this.running = true;

    setTimeout(function () {
      // External caller asked for explicit stop
      if (!self.running) {
        return;
      }

      self.refresh();
      self.delay = Math.round(self.delay * self.factor);

      if (self.delay < self.threshold) {
        self.running = false;
        self.startTimer();
      }
    }, delay * 1000);
  };

  /**
   * Stop timer and refresh
   */
  Drupal.NotificationBlock.prototype.stopTimer = function () {
    this.running = false;
    $(this.element).css('display', 'none');
    $(this.element).css('visibility', 'hidden');
  };

  /**
   * Refresh current block content
   */
  Drupal.NotificationBlock.prototype.refresh = function () {
    var self = this;

    $.ajax({
      url: this.url,
      async: true,
      cache: false,
      success: function (data, textStatus, jqXHR) {
        if (data.html && "string" === typeof data.html) {
          self.element.innerHTML = data.html;
          self.neverUnfolded = true;
          Drupal.settings.notification.currentlyDisplayed = data.since_id;
          Drupal.behaviors.NotificationDropDown.attach(self.element.parentNode);
        } else {
          // An error happened, could not reproduce the bug some users
          // actually complain seeing an "undefined" displayed sometime
          self.stopTimer();
        }
      },
      error: function () {
        // Whatever is the error, we cannot let the user with an incomplete
        // or broken UI: just hide the widget
        self.stopTimer();
      },
      type: 'GET'
    });
  };

  Drupal.behaviors.NotificationDropDown = {
    /**
     * Enable notification drop down on click
     */
    attach: function (context) {

      var
        jContainer = jQuery(context).find("#notifications"),
        jTop = jContainer.find(".top"),
        jList = jContainer.find(".list"),
        displayed = false,
        first = true,
        jDocument = jQuery(document),
        options = Drupal.settings.notification;

      jList.hide();

      jTop.click(function (event) {
        event.stopPropagation();
        event.preventDefault();

        jTop.toggleClass('open');

        if (displayed) {
          jList.hide();
          displayed = false;
        } else {
          jList.show();

          // Hide the list when clicking everywhere else
          jDocument.bind('mouseup.notifications', function (event) {
            event.stopPropagation();

            if (jList.has(event.target).length === 0 || jTop.has(event.target).length) {
              jList.hide();
              jTop.removeClass('open');
              displayed = false;

              jDocument.unbind('mouseup.notifications');
            }
          });

          displayed = true;

          // Remove the unread (red color) status on the unread count
          // on first display.
          if (first) {

            if (options.unfoldAction && options.unfoldUrl) {
              jQuery.ajax({
                url: options.unfoldUrl,
                data: {since_id: Drupal.settings.notification.currentlyDisplayed},
                async: true,
                cache: false,
                success: function (data, textStatus, jqXHR) {},
                error: function () {},
                type: 'POST'
              });
            }

            jTop.find(".unread").removeClass("unread");
            first = false;
          }
        }

        return false;
      });
    }
  };

  Drupal.behaviors.NotificationBlock = {
    /**
     * Enable AJAX refresh
     */
    attach: function (context) {

      if (Drupal.settings.notification.enabled) {
        var url = Drupal.settings.notification.refreshUrl,
          element = null,
          notification = null;

        jQuery(context)
          .find('#notifications')
          .each(function () {
            element = this;
            jQuery(element).once('notification', function () {
              notification = new Drupal.NotificationBlock(url, element, Drupal.settings.notification);
              notification.startTimer(true);
            });
          });
      }
    }
  };

  Drupal.behaviors.notificationRefresh = {
    attach: function (context, settings) {

    }
  };

// End of strict mode
})(jQuery);
