(function () { 
"use strict";

Drupal.behaviors.ApbFollowNotif = {
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

// End of strict mode
})();
