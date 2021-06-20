---
layout: post
title: "RCA: Save File Corruption Issues on CFC for iOS v5.0.4+"
description: "A detailed analysis of what went wrong."
permalink: /college-football-coach-rca-20-jun-2021/
---

_Originally posted [here](https://www.reddit.com/r/FootballCoach/comments/o3vxg1/rca_save_file_corruption_issues_on_v504506/) on [/r/FootballCoach](https://reddit.com/r/footballcoach). Edited slightly to improve Markdown formatting and remove more time-sensitive details (ex: beta links)._

## Executive Summary

On versions 5.0.4 through 5.0.6, users reported repeated and regular corruption messages within 2-4 seasons of use of a save file, regardless of if it was brand new or imported from a previous version. These issues occurred seemingly at random and regardless of save file configuration. This issue was seen in production for over a month across three different versions of the app, and it may have also occurred in limited numbers with the initial release of 5.0, based on analytics from Sentry.

  

Some investigation of the issue can be viewed [here](https://www.reddit.com/r/FootballCoach/comments/n3jdkq/cfc_for_ios_save_file_corruption_investigation/).

  

## Technical Details

### Why did the save file corruption message appear?

  

Specifically, this section of code triggered the message.

```objc  
if (self.currentWeek != 15 
    && [t getCurrentHC].year != 0 
    && [t getCurrentHC].year != [t getCurrentHC].coachingHistoryDictionary.count) {
    // ... irrelevant logging code omitted ...
    return YES;
}
```

  

In this section, the game lays out the requirements for active head coaches to be in a valid game state. In English, they are:

At any point during the season (`self.currentWeek != 15`, where 15 is the week after the national championship), a team’s head coach must not:

-   Have a career length of 0 (`[t getCurrentHC].year != 0`),
-   AND have a career length that does not match the length of their history records (`[t getCurrentHC].year != [t getCurrentHC].coachingHistoryDictionary.count`)


When this final portion fails — as it did to trigger the message in-game, it typically means that something is wrong with how coaches are created and assigned to teams in game.

  

### Why did this occur?

  

The save file corruption message was a symptom of a deeper problem with hiring ex-head coaches as coordinators. This feature was built on top of existing infrastructure used to support coordinator hiring. In 5.0 to 5.0.6, the problematic code looked something like this:

```objc
for (HeadCoach *hc in self.coachList) {
    Coordinator *newHC;
    if (![hc isKindOfClass:[Coordinator class]]) {
        newHC = [[Coordinator alloc] initWithCoach:hc];
        // ... logging omitted ...
    } else {
        newHC = (Coordinator *)hc;
    }

    if ([newHC.position isEqualToString:@"HC"] 
        && newHC.ratOff >= newHC.ratDef 
        && type == CoordinatorTypeOffensive 
        && ![coordsStarList containsObject:(Coordinator *)hc]) {
        [coords addObject:newHC];
    } else if ([newHC.position isEqualToString:@"HC"] 
                && newHC.ratOff < newHC.ratDef 
                && type == CoordinatorTypeDefensive 
                && ![coordsStarList containsObject:(Coordinator *)hc]) {
        [coords addObject:newHC];
    } else if (newHC.type == type 
                && ![coordsStarList containsObject:(Coordinator *)hc]) {
        [coordsFAs addObject:newHC];
    }
}

for (HeadCoach *hc in self.coachFreeAgents) {
    Coordinator *newHC;

    if (![hc isKindOfClass:[Coordinator class]]) {
        newHC = [[Coordinator alloc] initWithCoach:hc];
        // ... logging omitted ...
    } else {
        newHC = (Coordinator *)hc;
    }

    if ([newHC.position isEqualToString:@"HC"] 
        && newHC.ratOff >= newHC.ratDef 
        && type == CoordinatorTypeOffensive 
        && ![coordsStarList containsObject:(Coordinator *)hc] 
        && ![coords containsObject:(Coordinator *)hc]) {
        [coordsFAs addObject:newHC];
    } else if ([newHC.position isEqualToString:@"HC"] 
        && newHC.ratOff < newHC.ratDef 
        && type == CoordinatorTypeDefensive 
        && ![coordsStarList containsObject:(Coordinator *)hc] 
        && ![coords containsObject:(Coordinator *)hc]) {
        [coordsFAs addObject:newHC];
    } else if (newHC.type == type 
            && ![coords containsObject:(Coordinator *)hc] 
            && ![coordsStarList containsObject:(Coordinator *)hc]) {
        [coordsFAs addObject:newHC];
    }
}
```

The game does a couple of things here for the list of fired coaches (and those whose contracts have expired), repeating the same sequence of actions for the list of free agents:

1.  If the coach in question was originally created as a head coach (IE: they were a HeadCoach object), then they were converted into a Coordinator object for the purposes of coordinator hiring/firing.
2.  Based on their ratings and type, the head-coach-turned-coordinator was included or excluded from the list of considered coordinators. Head coaches with higher (or equal) offensive than defensive ratings were considered offensive coordinators, while head coaches with higher defensive ratings were considered defensive coordinators.


However, the aforementioned underlying problem occurred during this process and the subsequent hiring process and during the subsequent fixes: creating a new Coordinator object for a coach did not remove the old HeadCoach object from the lists and therefore from hiring eligibility. This meant a couple of things:

1.  The coach was duplicated and could be hired by multiple teams at multiple positions. When extrapolated across multiple seasons, this could result in multiple copies of a single coach existing in the free agents list and at multiple positions on multiple coaching staffs across the league.
2.  When the coach was duplicated, the pointer for their coach history records was also copied over to the new Coordinator object. Thus, at the end of a season, when history records for each coach were updated, updates to the same list of history records were made from multiple places.

  

\#2 here triggered the save file corruption message seen by many users.

  

### How was the issue found?

  

I was able to finally identify a set of steps on version 5.0.6 that seemed to consistently produce the bug within three seasons of play:

  

1.  Start a career mode save file.
2.  Sim a season.
3.  Change jobs.
4.  Go to settings panel, tap “Switch Save Files”, and try to reopen that save file.
5.  Reopen save file and advance season.
6.  Go to settings panel, tap “Switch Save Files”, and try to reopen that save file.
7.  Repeat steps 2-5 until save file is marked corrupt.

  

This allowed me to better diagnose the issue, see duplicated coaches in logs and the debugger, and conclude the above root cause.

  

### What measures were taken to fix this in versions 5.0.4, 5.0.5, and 5.0.6, and why didn’t they work?

  

1.  Changing and separating the order of end-of-season coach history operations

    - Change: Initially, based on insight from added coach hiring history/actions tracking code, the root cause of the save corruption issue was thought to be a sequencing problem between coach hiring/firing and history recording. Thus, changes were made to guarantee these operations ran in a specific sequence: coaches would have their history records updated to reflect their teams/performances during the completed season, and then they would be processed by the hiring/firing algorithms.
    - Problem: Sequencing was not the actual root cause of the issue — it was a red herring.

2.  Object equality checking between Coordinators and HeadCoaches

    - Change: The `-[HeadCoach isEqual:]` method was overridden to properly detect duplicates.
    - Problem: The first few iterations of this change did not actually work. The overridden `isEqual` continued to use the superclass’s (NSObject) implementation, which compared the hashes of a Coordinator object and a HeadCoach object, which would always be different because they are technically different objects in memory.

3.  Transitioning coach collections from Arrays to Sets

    - Change: The idea here was to build off of the changes to object equality and use sets instead of arrays as the collection implementation of choice to manage lists of coaches. Conceptually (in programming parlance), sets are different from arrays because sets guarantee the uniqueness of the objects they contain (IE: a set would not contain duplicates of a specific object).
    - Problem: Because the changes to object equality were bugged, this change didn’t work as expected. A Coordinator object and its corresponding original HeadCoach object were not considered equal, and therefore coaches were duplicated in the ensuing set.

4.  Cleaning up invalid coaches

    - Change: Part of the original remediation for the above changes was to clean up coaches in invalid states, detected based on their career lengths and coaching record details.
    - Problem: This approach failed in a few capacities as updates were made: it didn’t consider that coaches could have been duplicated multiple times; at one point, it didn’t remove coaches from hiring eligibility lists; and at another point, it didn’t consider active coaches on coaching staffs.


### Why did this take so long to fix?

  

There were three major reasons for the two months required to fix this high severity issue (listed in order of impact, highest to lowest):

1.  Lack of time: I am the only developer on this project, and thus any fixes are dependent on my schedule. The last two months of real life have been very, very busy, and solid stretches of free time to work on this game have been few and far between.
2.  Lack of reproducibility: This issue was immensely difficult to reproduce sustainably due to the partially-random nature of coordinator hiring; coordinators must meet minimum requirements to be hired by a CPU team AND pass a random number check (which simulates allowing for promotions and internal hires). The only seemingly common thread in various user reports was a higher use of career mode, but since the issue occurred in both school and career mode, that did not seem particularly salient. It also took a different number of seasons played every time to produce the issue, and every season that is simmed through without incident gives credence to the idea that the issue does not exist in the save file, only for it to show up multiple seasons later after multiple coaching positions and the free agents list are populated with coach duplicates.
3.  Lack of logging and reporting: in earlier versions of 5.0, the method that triggered the save file corruption message was not backed by reporting to Sentry and did not provide any context on coach hiring history to determine a root cause. Later versions added more detailed tracking to collect more data and identify a root cause, but the lack of reproducibility of this issue meant that the data collected only revealed so much.
4.  Red herrings and false causes: the data collected by logs and from user reports seemed to indicate a variety of potential causes. Fixes were applied to save files but seemed to do nothing because the issue that they seemed to fix was a symptom of the underlying problem OR non-existent.

  

## Remediation and Prevention

  

### How was this issue fixed once and for all? What steps were taken to make sure this will this not happen again?

  

1.  CPU hiring of ex-head coaches as coordinators was disabled. It is possible it may not return.
2.  5.0.7’s save file update searches for and removes duplicate coaches from two places: all coach hiring eligibility lists and all active coaching staffs. The logic used to do this search loops through these places until all of the duplicates (and the original coach, unless the original is the user coach in career mode) are removed.
3.  Head coach storage in memory was modified to look more like coordinator storage.
4.  Various methods of tracking, reporting, and logging of coach hiring/firing/progression processes were added for debugging purposes, especially to the save file corruption checks.

  

### How are you sure this won’t happen again?

  

1.  The source of coach object duplication was removed.
2.  Any place that I can think of that could include a duplicated coach object was cleaned up.
3.  I was able to play through 10 seasons on career mode without incident even when following the steps I used to reproduce the issue reliably on 5.0.6.
4.  I was able to hire/fire/promote coordinators without issue on career and school mode without incident.

  

## Next Steps

  

First, an apology: I am sorry. The game should not have been broken this long. I’ve gotten a lot of reviews and email about the issue over the last few weeks, and I want to emphasize: I understand and sympathize with those of you who are frustrated that the game has been unplayable. This situation didn’t happen out of any sort of neglect for the game, but I wasn’t as transparent as I should have been about the issue, even if I didn’t have any sort of update to provide. I hope this post helps clarify what happened.


Secondly, I have the update ready to submit to Apple, but I am waiting for word from testers I enlisted on [this post](https://www.reddit.com/r/FootballCoach/comments/n3jdkq/cfc_for_ios_save_file_corruption_investigation/) before proceeding. I am also conducting some more testing over the next few days to ensure that I don’t run into any further issues even in unorthodox save file configurations. 

Thank you for your patience over the last few weeks. Hopefully, this saga for the game is now mercifully over, and everyone can enjoy their time playing once again.

