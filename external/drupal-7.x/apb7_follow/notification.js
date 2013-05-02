
// Globally visible for potential overrides or external use
var NotificationBlockList = [];

(function (jQuery) { 
"use strict";

/**
 * Notification block AJAX refresher
 *
 * @param string url     URL used for refreshing
 * @param object element DOM element that carries the block
 */
var NotificationBlock = function (url, element, options) {
    this.defaultDelay  = 30;
    this.delay         = 30;
    this.threshold     = 320;
    this.factor        = 1.5;
    this.running       = false;
    this.element       = element;
    this.url           = url;
    this.options       = options || {};
    this.neverUnfolded = true;
};

/**
 * Starts timer and run the block content refresh loop
 *
 * @param bool fromStart Restart timer to default value
 */
NotificationBlock.prototype.startTimer = function (fromStart) {

    var self  = this,
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
NotificationBlock.prototype.stopTimer = function () {
    this.running = false;
    this.element.style.display = 'none';
    this.element.style.visibility = 'hidden';
};

/**
 * Refresh current block content
 */
NotificationBlock.prototype.refresh = function () {
    var self = this;

    jQuery.ajax({
        url: this.url,
        async: true,
        cache: false,
        success: function (data, textStatus, jqXHR) {
            self.element.innerHTML = data;
            self.neverUnfolded = true;
            Drupal.behaviors.NotificationDropDown.attach(self.element.parentNode);
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

        var jContainer = jQuery(context).find("#notifications"),
            jTop       = jContainer.find(".top"),
            jList      = jContainer.find(".list"),
            displayed  = false,
            first      = true,
            jDocument  = jQuery(document),
            options    = Drupal.settings.notification;

        jList.hide();

        jTop.mouseup(function (event) {
            jTop.toggleClass('open');
            
            if (displayed) {
                jList.hide();
                displayed = false;
            } else {
                jList.show();

                // Hide the list when clicking everywhere else
                jDocument.unbind('mouseup').one('mouseup', function (event) {
                    event.stopPropagation();

                    if (jList.has(event.target).length === 0 || jTop.has(event.target).length) {
                        jList.hide();
                        jTop.removeClass('open');
                        displayed = false;
                    }
                });

                displayed = true;

                // Remove the unread (red color) status on the unread count
                // on first display.
                if (first) {

                    if (options.unfoldAction && options.unfoldUrl) {
                        jQuery.ajax({
                            url: options.unfoldUrl,
                            async: true,
                            cache: false,
                            success: function (data, textStatus, jqXHR) {},
                            error: function () {},
                            type: 'GET'
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

        if (Drupal.settings.notification &&
            Drupal.settings.notification.enabled)
        {
            var url          = Drupal.settings.notification.refreshUrl,
                element      = null,
                notification = null;

            jQuery(context)
                .find('#notifications')
                .each(function () {
                    element = this;
                    jQuery(element).once('notification', function () {
                        notification = new NotificationBlock(url, element, Drupal.settings.notification);
                        notification.startTimer(true);
                        NotificationBlockList.push(notification);
                    });
                });
        }
    }
};

// End of strict mode
})(jQuery);
