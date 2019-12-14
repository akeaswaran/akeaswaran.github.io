---
layout: post
title: Exploring College Football Analytics
description: "AKA: should I have been an industrial engineer?"
permalink: /exploring-cfb-analytics/
published: true
---

_[Skip to the actual data analysis.](#markdown-toc)_

Despite being a freshly-minted adult, this is a continuing-education hill I will die on:

> Taking a training course can really open your eyes to modern techniques and how certain things work, but taking said course means virtually nothing if you can't use your newly-acquired skills in a tangible way.

Picking up a new skill is tough, but what makes it _harder_ is not being able to **practice using it**. I've always had trouble retaining skills that I never use (Spanish, cursive, ~~soccer~~, etc.), so whenever I pick up something new nowadays, I try to marry it to something familiar to ease myself into it.

In a roundabout, semi-didatic way, that brings us to when I signed up for a one-week crash course on data science in Python through [work](https://salesforce.com/careers/). Data science is, of course, today's [new hotness](https://www.youtube.com/watch?v=jn_P13FkQYw), and as a new grad in the software industry that didn't pick up much in the way of statistics or math knowledge as part of my education[^1] (but had [dabbled a little with R in the past](/epg)), I felt it best to learn a thing or two about the field.

But the real reason I signed up for the course and took copious amounts of notes during it is simple: _I wanted to understand college football better_.

Now, this seems a little simplistic, so let's diverge[^2]:

I've been an ardent reader of any and all material from [Bill Connelly](https://twitter.com/ESPN_BillC), a resident statistician, senior writer, and college football data nerd at ESPN (and formerly at SBNation). I've always admired the job he's done explaining the statistics he uses (which he also developed) in his writing (especially in his book _[Study Hall](https://www.amazon.com/Study-Hall-College-Football-Stories/dp/1484989961)_), which usually follows these steps:

1. **He defines the concept**, whether that be mathematically (with a formula) or semantically (with words and other, more familiar concepts). He's (usually) speaking to a college football crazed audience, but he makes things easy for even a neutral to understand.

2. **He provides the data or results that back the concept**, so readers can draw their own conclusions or come to his conclusions themselves.

3. **He ties the concept back to the real world**, providing a relevant example of how the data he's presented can be applied.

These points seem very simple, and you'd think that a lot of other writers follow them -- sadly, I've found a number of sources that just do the first two[^3]: while neglecting the third. Being able to apply a conclusion to a relevant example in the problem space is critical to understanding the conclusion itself and what it represents[^4] (do you see where I'm going with this now?)

Back once more to the purpose of the data science course at hand[^5]:

Connelly puts out [advanced box scores](https://twitter.com/ESPN_BillC/status/1204109548304424961?s=20) every week, relying on play-by-play data from...somewhere (probably ESPN, now) to put together a variety of efficiency, drive progression, turnover, and explosiveness statistics that, when applied in unison, tell the story of a college football game more holistically than [the usual box score from ESPN](https://www.espn.com/college-football/boxscore?gameId=401132981). A number of the metrics he includes are very straightforward and intuitive to pick up and use, and others are even cribbed directly from the original box score (since they were incredibly useful already).

But there are two (well, really three...maybe four) things that Connelly provides that are really his secret sauce (and as such, incredibly proprietary):

1. SP+[^6] (neé S&P+[^7]) and the post-game win expectancies that he generates via it
2. IsoPPP and the expected point values for each yard line on the field.

When it comes to things I'm interested in, I don't like taking things for granted: it wasn't enough for me to read Connelly's numbers -- I really wanted to understand them, and to do that, I had to derive them myself (or, at least, cobble together approximations that I'm happy with the accuracy of).

Thus, with the skills I picked up that data science course, I've been tinkering _a lot_ with college football data (via the creatively-named [College Football Data](https://collegefootballdata.com) site and API) in my spare time to put together my own advanced box scores that have guided my post-game breakdowns for _[From the Rumble Seat](https://fromtherumbleseat.com)_. This exercise has been immensely rewarding and helpful: primarily in that I've gotten to learn a new skill, but also in that learning said new skill has helped me become a more educated fan and "student of the game" (as pretentious as that may sound).

I haven't come close to replicating Connelly's numbers[^8], but that's not really the goal anymore[^9] -- I want to use data to help others see "the game inside the game". College football is an inherently human and physical endeavor, but quantifying and analyzing each and every play helps us question and verify football conventions ("establishing the running game", "punting is always good", "don't risk fourth down attempt in no-man's-land") and discover new ways to optimize and evaluate performance in the sport.

Enough of the high-level philosophical talk -- let's get to the data. The following are a few of the explorations/experiments/problems/whatever I've looked at over the past few months. I'm providing Connelly's "three principles" where appropriate because:

1. He's one of the few media personalities that explains their stats methodologies in any capacity, so I want to do the same
2. It would be cool for this to be a collaborative venture to build a more educated college football fandom[^10] (not that I have any clout to do that, per se).
3. This stuff can get really interesting and I like writing about it, even when it's not Tech-related.

Without further ado, the list so far:

* TOC
{:toc}
---

### Halftime Adjustments

From a comment left on _[From the Rumble Seat](https://www.fromtherumbleseat.com/2019/12/13/21004327/georgia-tech-football-the-numbers-game-d-st-college-football-advanced-stats-cfp-nola-clemson-ohio-st#comments)_:

> I'd like to see a comparison of how the defense did between the first and second halves of each game.
COFH was an extreme case, but our lack of depth and ability to play at a high level for a solid 60 minutes was apparent even in the NC State game.
It’s not a new problem of ours, either (see the Tennessee game). Not sure if it’s a S&C issue or an issue with not having a good bench, or both.

If you look at non-garbage-time situations and break down the plays Tech faced defensively this season:

**First Half:**
* Rush Success Rate Allowed: 47% (national avg for full game: 41%)
* Pass Success Rate Allowed: 41% (national avg for full game: 41%)

**Second Half:**
* Rush Success Rate Allowed: 39%
* Pass Success Rate Allowed: 43%

It seems like the defense is tightening up on rushing situations and giving up more ground on passing situations in the second half when compared to the first. If we consider the play distribution at play in each half (again, non-garbage-time only)…

**First Half:**
* Rush: 56%
* Pass: 44%
* Other (usually ST plays): 0%

**Second Half:**
* Rush: 63%
* Pass: 36%
* Other: 1%

…we can add some context to those numbers.

[Firstly, w]ith respect to [the] original point, Tech defends the rush 6% better and the pass 2% worse in the second half. Given that pass plays are usually much more explosive and we’re looking at numbers that have had garbage time removed, I’m tempted to say these margins aren’t particularly significant in terms of improvement (or lack thereof, in our case) between halves.

BUT: if you consider these improvements significant, it’s clear that halftime adjustments matter ("duh," you might also say — but bear with me). If you’re looking at the success rates, the Tech defense settles in the second half and keeps the Jackets in games. On the flip side, opponents adjust their play calling to maximize what they can do versus what has been a porous Tech run defense in the second half. If I had to guess further, opponents’ passing success rate is lower in the second half than in the first because if you’re running the ball and playing conservatively, you’re only going to pass in riskier situations. Therefore, it follows that you’d succeed less often from those situations.

---

### Expected Points Added

---

### Adapted Advanced Box Scores

---

### Post-Game Win Expectancy and Matchup Predictions

---

### 2019 Georgia Tech Stat Breakdowns

These are based on my version of the advanced box score and are ~2000 words each, so it might be best if you just read both of them: [Offense](https://www.fromtherumbleseat.com/2019/12/12/21002278/georgia-tech-football-the-numbers-game-offense-advanced-statistics-college-football-stats-cfp-nola) and [Defense/Special Teams](https://www.fromtherumbleseat.com/2019/12/13/21004327/georgia-tech-football-the-numbers-game-d-st-college-football-advanced-stats-cfp-nola-clemson-ohio-st).

---
<h5>Footnotes</h5>

[^1]: Well, I did -- I just never bothered to remember any of it because _I never used it again_.
[^2]: This is a detour you can really ignore. Skip to [here](#fnref:5).
[^3]: They do even just that poorly, simply providing the data and a relevant formula and expecting you to figure things out. This horse wants to be led to water!
[^4]: This is something I strive to do in my own (albeit much more limited) statistical writing -- I want readers to understand the stat I'm presenting both as a number and as what it looks like on a football field.
[^5]: If you want to read the blurb above, skip to [here](#fnref:2).
[^6]: As a cousin to baseball's OPS and OPS+, S&P+ consisted of a sum of a team's success rate and [Equivalent Points per Play](https://www.footballstudyhall.com/2014/1/27/5349762/five-factors-college-football-efficiency-explosiveness-isoppp) (weighted at 86% and 14% respectively) that had been adjusted based on the national average. As time went on, the calculations got more complex and relied more heavily opponent-adjusted metrics as part of the "[Five Factors](https://www.footballstudyhall.com/2018/2/2/16963820/college-football-advanced-stats-glossary#clvsDo)" of Football.
[^7]: Rumor has it that lawyers from the S&P 500 forced him to drop the ampersand when he moved to ESPN. #RIPAmpersand
[^8]: Nor might I ever, considering the statistical methods that may be involved, the differences in his data source and mine, difference in weighting various factors, etc.
[^9]: Well, at least not the primary goal. I've made [a lot of progress](https://github.com/akeaswaran/cfb-pbp-analysis) towards that, but just need to tinker more (and maybe learn some more math) to get it just right.
[^10]: [But I do dabble in the dark art of college basketball too](https://github.com/akeaswaran/cbb_pbp_analysis).
