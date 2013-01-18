APubSub Follow
==============

Sample usage of APubSub module that allows any user to follow any other user
content creation or modification.

This module allows the user to "follow" and "unfollow" node and users. Followed
users actions will trigger notifications to users following him, and editions
of the followed node will do also.

Install
=======

 * Enable the module.
 * Place the new blocks into the desired regions.

Note: due to quick and dirty theming for sample purposes only it is recommended
to put the "Current user notifications" block into the "header" regions and make
it float left if possible.

Future
======

This module is now almost ready for production use, therefore there is still
some problems down the road:

 * Notifications formatting will probably do *a lot* of SQL queries, due to
   the fact that they will load entities in order to display there titles.

 * StockIcon integration is still parsing a lot the file system and will
   cause you some serious trouble if you enable it.

