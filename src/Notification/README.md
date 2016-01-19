# Notification API

This namespace contains an additional and optional API to handle user
notifications using the APubSub library.

This namespace also serves as a good example of APubSub usage.

## Concepts

Notifications always happen arround a particular object, for which the
notification system is aware of the following properties:

 *  **type**: a string, for example, a user *group*;
 *  **identifiers**: integer or string list, for exemple *42,43,44*.

For practical reasons, the actions might be done on multiple objects at once
hence the fact that the identifier information is a list, formatter
implementation will then be responsible for dealing with single or multiple
variations.

Every notifications concerns a certain object, and have the following
additionnal properties:

 *  **action**: a string, action being done, for exemple following our user
    group exemple: **member_add**;

 *  **data**: an abitrary hasmap of values which the notifications might need
    to format the notification text.

Subscribers are being used for users or technical other concept to follow the
notifications thread about certain objects, each subscriber has the following
properties:

 *  **id**: integer or string, the business identifier of the business object
    in the site using this API;

 *  **suber_type**: a string, per default *_u* (means user) - you may ignore
    this property for sites where you'd manage only user subscriptions, but you
    may use it for other business purposes (for exemple if you want to build a
    public user activity stream for users to see).

## Achitecture

Behind the scene, every object will have its own channel, named ```TYPE:ID```,
for example our group would be *group:42*.

Every subscriber is a subscriber named ```SUBER_TYPE:ID``` for exemple a user
in our exemple would have the *_u:123* name, and it's public activity stream
would have the *public:123*.

Whenever a subscriber subscribes to an object, the channel is automatically
created seamlessly.

Every notification sent is a message containing as data the notification *data*
hashmap, including the *action* property.
