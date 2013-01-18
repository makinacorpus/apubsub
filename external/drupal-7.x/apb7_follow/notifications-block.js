
// Globally visible for potential overrides or external use
var NotificationBlockList = [];

(function (jQuery) { 
"use strict";

/**
 * Notification block AJAX refresher
 */
var NotificationBlock = function (url, element) {
    this.element = element;
    this.url = url;
};

NotificationBlock.prototype = {

    url:          null,
    element:      null,
    defaultDelay: 8,
    delay:        8,
    threshold:    320,
    factor:       1.5,
    running:      false,

    startTimer: function (fromStart) {
        var self  = this,
            delay = this.delay;

        // Already running
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
    },

    refresh: function () {
        var self = this;

        jQuery.ajax({
            url: this.url,
            async: true,
            cache: false,
            success: function (data, textStatus, jqXHR) {
                self.element.innerHTML = data;
                Drupal.behaviors.NotificationDropDown.attach(self.element.parentNode);
            },
            type: 'GET'
        });
    }
};

/**
 * Enable notification drop down on click
 */
Drupal.behaviors.NotificationDropDown = {
    // Shocked? Drupal 2 tabs indent sucks. If there is too many imbricated if
    // statements, just produce a better algorithm instead of reducing tab size
    attach: function (context) {

        var jContainer = jQuery(context).find("#notifications"),
            jTop       = jContainer.find(".top"),
            jList      = jContainer.find(".list"),
            displayed  = false,
            first      = true,
            jDocument  = jQuery(document);

        jList.hide();

        jTop.click(function () {
            if (displayed) {
                jList.hide();
                displayed = false;
            } else {
                jList.show();

                // Hide the list when clicking everywhere else
                jDocument.mouseup(function (event) {
                    if (jList.has(event.target).length === 0) {
                        jList.hide();
                    }

                    jDocument.mouseup(null);
                    displayed = false;
                });

                displayed = true;

                // Remove the unread (red color) status on the unread count
                // on first display.
                if (first) {
                    jTop.find(".unread").removeClass("unread");
                    first = false;
                }
            }
        });
    }
};

/**
 * Enable AJAX refresh
 */
Drupal.behaviors.NotificationBlock = {
    attach: function (context) {

        if (Drupal.settings.notifications &&
            Drupal.settings.notifications.enabled)
        {
            var url          = Drupal.settings.notifications.refreshUrl,
                element      = null,
                notification = null;

            jQuery(context)
                .find('#notifications')
                .each(function () {
                    element = this;
                    jQuery(element).once('notifications', function () {
                        notification = new NotificationBlock(url, element);
                        notification.startTimer(true);
                        NotificationBlockList.push(notification);
                    });
                });
        }
    }
};

// End of strict mode
})(jQuery);
