---
layout: post
title: Flyte - a new Twitter experience
---

For the past few months, I have been helping my friend [Brian Tung](https://twitter.com/iPlop) and his team beta-test their new premium Twitter client for iOS, [Flyte](https://itunes.apple.com/us/app/flyte-for-twitter/id880520420?mt=8), which aims to be a more personal and customizable Twitter experience. Now that Flyte has been released, the following is an overview of my experiences, observations, and opinion of the app after having beta-tested it from early on in development to release day.

### Summary

[Flyte](https://twitter.com/getflyte), as mentioned before, is a brand-new Twitter client for iOS, optimized for iPhone 5 and up and developed for iOS 7 (although the app does scale down for 3.5" devices). As per the App Store description:

>It's built to fit your personal style, whatever that may be. Are you tired of client upon client all looking the same? Then leave those boring black and white apps behind and discover a world of color!

And Brian has really hit the nail on the head with that one, seeing as Flyte adapts its colors and UI elements to any custom wallpaper you put behind them. For example:

![Example 1](https://cloud.githubusercontent.com/assets/3195522/3098386/3c1e0ada-e5eb-11e3-9e34-724eef600086.png)

![Example 2](https://cloud.githubusercontent.com/assets/3195522/3098385/3c1b3ff8-e5eb-11e3-8331-1c63f1a0e407.png)

Flyte's navigation and Twitter elements are all centered around gestures, a model which has become really popular lately as a way to control UI elements and invoke actions within apps.

![Gesture Example](https://cloud.githubusercontent.com/assets/3195522/3098447/35d1c512-e5ec-11e3-954e-54e24316fed1.png)

On any tweet, a long swipe to the right invokes a reply view for it, a short swipe to the right retweets it, a short swipe to the left favorites it, and a long swipe to the left presents the conversation. It's an incredibly intuitive way to accomplish all of these actions and harkens back to the days of Tweetie (now [Twitter for iPhone](https://appstore.com/twitter)), when one could swipe across a tweet and view all of these actions inline. In additions to accomplishing these actions by swiping, panning over each of the cards will move you to the next one. It's a stark contrast from the button-pushing and tab-bar foundation that Twitter for iPhone and [Tweetbot](appstore.com/tweetbot), but a welcome one, making the whole experience feel more fluid and connected. That is the core of the Flyte experiences: to make browsing Twitter a more fluid and interconnected experience.

### Minor gripes

With all of its great features and enchanting UI elements, Flyte still does have a few issues.

* The bubble that denotes new direct messages, next to the current user's profile image in the top left of the screen, appears even if there are no new messages to be read.
* Speaking of direct messages, the view for direct messages only loads threads in which the current user has responded and displays the last message in the thread that was not from the current user (although I'm sure these were both design decisions).
* Sometimes I hit the Twitter rate limit with normal, every-day use. I don't know if that's a sign I should stop using Twitter for a while or if that's a bug, but it's a bit of a gripe all the same.
* The edges of the end cards aren't very elastic (or rather, their animations aren't). If you try to swipe past them, they'll force themselves back into place quite awkwardly.
* Flyte doesn't have push notifications (yet, see below for more).


### Future updates

* On push notifications: It takes quite a bit of infrastructure to be in place to support push notifications through Twitter's Streaming API. This requires funding, which will come from the sales of Flyte. When Flyte garners enough support and makes enough money to make this feature feasible, Brian will begin adding support for it.
* On TweetMarker support: Same as with push notifications, this feature will appear when it becomes economically feasible.

Those are the main features left out of 1.0, but if you want to view the full list of features planned for 1.1, [go here](https://itunes.apple.com/us/app/flyte-for-twitter/id880520420?mt=8).

### Final Verdict

Flyte is a great Twitter experience that will only improve and become more refined with future updates. At $0.99 (USD), it is a cheaper option than the more premier Twitter clients, such as Tweetbot and [Echofon Pro](http://itunes.apple.com/us/app/echofon-pro-for-twitter/id315577859?mt=8), and has a smaller feature set than both of those, but the customizability and UI design of Flyte trump their somewhat-old, rigid, tab-based UIs. Some say gestures are the future of iOS design, and Flyte has embraced that philosophy whole-heartedly, creating a finger's paradise in the palm of your hand.
But don't take my word for it; [buy Flyte](https://itunes.apple.com/us/app/flyte-for-twitter/id880520420?mt=8) today and try it for yourself.
