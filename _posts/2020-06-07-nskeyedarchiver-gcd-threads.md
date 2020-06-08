---
layout: post
title: "NSKeyedArchiver, GCD, Threads, and You"
description: "Understanding multithreaded encoding and decoding using NSKeyedArchiver"
permalink: /nskeyedarchiver-gcd-threads/
---

_Let me preface this brain-dump with a disclaimer: this solution is what worked for me, and it may not work for your use-case. If it does, great! If it doesn't, hopefully you find something that does._

<h2 style="margin-bottom: 0;">Background</h2>
<small style="margin-top: 0;"><i>If you want to read the original posts, check out <a href="https://stackoverflow.com/questions/61568419/ios-13-nskeyedunarchiver-exc-bad-access">StackOverflow</a> or the <a href="https://forums.developer.apple.com/message/418041">Apple Developer Forums</a>. The following cribs heavily from them.</i></small>

In [College Football Coach for iOS](https://appsto.re/us/5pXtbb.i), I rely on the [AutoCoding](https://github.com/nicklockwood/AutoCoding) library to get objects in my data model to automagically adopt NSCoding (although I did implement the protocol myself in some places -- like I said, I was an novice) and [FCFileManager](https://github.com/fabiocaccamo/FCFileManager) for saving these objects to the local documents directory. The data model being written is fairly simple: custom NSObjects that have various properties of NSString, NSArray, and other custom NSObject classes (but I will note there are a number of circular references; most of them declared as strong and nonatomic in header files). This configuration has its quirks, but historically, it has worked well (and still does, to this day) in the production version of CFC for iOS.

However, in [version 4.1](https://www.reddit.com/r/FootballCoach/comments/giel2q/cfc_for_ios_41_beta/), I'm adding (or added, depending on when you read this) saving and loading save files from iCloud. While building that out, I've been looking to trim down my list of third-party dependencies and update older code to use iOS 13+ APIs. It so happens that FCFileManager relies on the now-deprecated `+[NSKeyedUnarchiver unarchiveObjectWithFile:]` and `+[NSKeyedArchiver archiveRootObject:toFile:]`, so I've focused on rewriting what I need from that library using more modern APIs.

I was able to get saving files working pretty easily using this:

```swift
@objc static func save(_ content: NSCoding, at fileName: String, completion: ((Bool, Error?) -> ())?) {  
    CFCSerialQueue.processingQueue.async { // my own serial queue  
        measureTime(operation: "[LocalService Save] Saving") { // just measures the time it takes for the logic in the closure to process  
            do {  
                let data: Data = try NSKeyedArchiver.archivedData(withRootObject: content, requiringSecureCoding: false)  
                // targetDirectory here is defined earlier in the class as the local documents directory  
                try data.write(to: targetDirectory!.appendingPathComponent(fileName), options: .atomicWrite)  
                if (completion != nil) {  
                    completion!(true, nil)  
                }  
            } catch {  
                if (completion != nil) {  
                    completion!(false, error)  
                }  
            }  
        }  
    }  
}  
```

And this works great -- it's pretty fast and the resulting file on disk can still be loaded back into memory using FCFileManager's minimal wrapper method around `+[NSKeyedUnarchiver unarchiveObjectWithFile:]`.

## Problem

But loading this file _back_ from the local documents directory proved to be a massive challenge. Here's what I started off with:

```swift
@objc static func load(_ fileName: String, completion: @escaping ((Any?, Error?) -> ())) {  
    CFCSerialQueue.processingQueue.async { // my own serial queue  
        measureTime(operation: "[LocalService Load] Loading") { // measures and prints execution time
            do {  
                // targetDirectory here is defined earlier in the class as the local documents directory  
                let combinedUrl: URL = targetDirectory!.appendingPathComponent(fileName)   
                if (FileManager.default.fileExists(atPath: combinedUrl.path)) {  
                    let data: Data = try Data(contentsOf: combinedUrl)  
                    let obj: Any? = try NSKeyedUnarchiver.unarchiveTopLevelObjectWithData(data)  
                    completion(obj, nil)  
                } else {  
                    completion(nil, ServiceError.generic(message: "Data not found at URL \(combinedUrl)"))  
                }  
            } catch {  
                completion(nil, error)  
            }  
        }  
    }  
}  
``` 

I replaced FCFileManager's `+[NSKeyedUnarchiver unarchiveObjectWithFile:]` with the new `+[NSKeyedUnarchiver unarchiveTopLevelObjectWithData:]`, but I ran into `EXC_BAD_ACCESS code=2` crashes when getting execution flowing through that line. The stacktraces were never particularly helpful; each was usually ~1500 frames long and jumped between various custom `-[NSObject initWithCoder:]` implementations. Here's an example (comments added for context, clarity, and conciseness):

```obj_c
@implementation Game 
  
-(id)initWithCoder:(NSCoder *)aDecoder {  
    self = [super init];  
    if (self) {  
        // ...lots of other decoding...  
  
        // stack trace says the BAD_ACCESS is flowing through these decoding lines  
        // @property (atomic) Team *homeTeam;  
        homeTeam = [aDecoder decodeObjectOfClass:[Team class] forKey:@"homeTeam"];  
        // @property (atomic) Team *awayTeam;  
        // there's no special reason for this line using a different decoding method;  
        // I was just trying to test out both  
        awayTeam = [aDecoder decodeObjectForKey:@"awayTeam"];   
  
        // ...lots of other decoding...  
    }  
    return self;  
}  
``` 

Let's back up and clarify something about the decoding method above and the data model in general: each Game object has a reference to a home and away Team; each Team contains an NSMutableArray of Game objects called `gameSchedule`, which is defined as such:

```obj_c
@property (strong, atomic) NSMutableArray<Game*> *gameSchedule;  
```

For reference, here's Team's `initWithCoder:` implementation:

```obj_c
-(id)initWithCoder:(NSCoder *)coder {  
    self = [super initWithCoder:coder];  
    if (self) {  
        if (teamHistory.count > 0) {  
           if (teamHistoryDictionary == nil) {  
               teamHistoryDictionary = [NSMutableDictionary dictionary];  
           }  
           if (teamHistoryDictionary.count < teamHistory.count) {  
               for (int i = 0; i < teamHistory.count; i++) {  
                   [teamHistoryDictionary setObject:teamHistory[i] forKey:[NSString stringWithFormat:@"%ld",(long)([HBSharedUtils currentLeague].baseYear + i)]];  
               }  
           }  
        }  
  
        if (state == nil) {  
           // set the home state here  
        }  
  
        if (playersTransferring == nil) {  
           playersTransferring = [NSMutableArray array];  
        }  
  
        if (![coder containsValueForKey:@"projectedPollScore"]) {  
           if (teamOLs != nil && teamQBs != nil && teamRBs != nil && teamWRs != nil && teamTEs != nil) {  
               FCLog(@"[Team Attributes] Adding Projected Poll Score to %@", self.abbreviation);  
               projectedPollScore = [self projectPollScore];  
           } else {  
               projectedPollScore = 0;  
           }  
        }  
  
        if (![coder containsValueForKey:@"teamStrengthOfLosses"]) {  
           [self updateStrengthOfLosses];  
        }  
  
        if (![coder containsValueForKey:@"teamStrengthOfSchedule"]) {  
           [self updateStrengthOfSchedule];  
        }  
  
        if (![coder containsValueForKey:@"teamStrengthOfWins"]) {  
           [self updateStrengthOfWins];  
        }  
    }  
    return self;  
}  
```

Pretty simple other than for the backfilling of some properties. However, this class imports AutoCoding, which hooks into `-[NSObject initWithCoder:]` like so:
 
```obj_c
- (void)setWithCoder:(NSCoder *)aDecoder  
{  
    BOOL secureAvailable = [aDecoder respondsToSelector:@selector(decodeObjectOfClass:forKey:)];  
    BOOL secureSupported = [[self class] supportsSecureCoding];  
    NSDictionary *properties = self.codableProperties;  
    for (NSString *key in properties)  
    {  
        id object = nil;  
        Class propertyClass = properties[key];  
        if (secureAvailable)  
        {  
            object = [aDecoder decodeObjectOfClass:propertyClass forKey:key]; // where the EXC_BAD_ACCESS seems to be coming from  
        }  
        else  
        {  
            object = [aDecoder decodeObjectForKey:key];  
        }  
        if (object)  
        {  
            if (secureSupported && ![object isKindOfClass:propertyClass] && object != [NSNull null])  
            {  
                [NSException raise:AutocodingException format:@"Expected '%@' to be a %@, but was actually a %@", key, propertyClass, [object class]];  
            }  
            [self setValue:object forKey:key];  
        }  
    }  
}  
  
- (instancetype)initWithCoder:(NSCoder *)aDecoder  
{  
    [self setWithCoder:aDecoder];  
    return self;  
}  
```

I did some preliminary code tracing and found that execution flows through line 12 of this snippet. Based on some logging I added, it seemed like propertyClass somehow gets deallocated before getting passed to -`[NSCoder decodeObjectOfClass:forKey:]`. However, [Xcode shows that propertyClass has a value when the crash occurs](https://imgur.com/a/J0mgrvQ).

The property in question in that frame is defined:
```obj_c
@property (strong, nonatomic) Record *careerFgMadeRecord;  
```

and has the following properties itself:
 
```objective_c
@interface Record : NSObject  
@property (strong, nonatomic) NSString *title;  
@property (nonatomic) NSInteger year;  
@property (nonatomic) NSInteger statistic;  
@property (nonatomic) Player *holder;  
@property (nonatomic) HeadCoach *coachHolder;  
// … some functions  
@end  
```

This class also imports AutoCoding, but has no custom `initWithCoder:` or `setWithCoder:` implementation.

Curiously, replacing the load method I wrote with FCFileManager’s version also crashed in the same fashion, so at first, I also thought this could have been more of an issue with how the data was archived than how it was being retrieved. However, the confounding factor here was that everything works fine when using FCFileManager’s methods to load/save files. My initial guess was that there was some lower-level difference between the implementation of archiving in iOS 11 (when FCFileManager was last updated) and iOS 12+ (when the NSKeyedArchiver APIs were updated).

Per some suggestions I found online at the time (like [this one](https://stackoverflow.com/a/57048596http://)), I also tried this:
```swift
@objc static func load(_ fileName: String, completion: @escaping ((Any?, Error?) -> ())) {  
    CFCSerialQueue.processingQueue.async {  
        measureTime(operation: "[LocalService Load] Loading") {  
            do {  
                let combinedUrl: URL = targetDirectory!.appendingPathComponent(fileName)  
                if (FileManager.default.fileExists(atPath: combinedUrl.path)) {  
                    let data: Data = try Data(contentsOf: combinedUrl)  
                    let unarchiver: NSKeyedUnarchiver = try NSKeyedUnarchiver(forReadingFrom: data)  
                    unarchiver.requiresSecureCoding = false;  
                    let obj: Any? = try unarchiver.decodeTopLevelObject(forKey: NSKeyedArchiveRootObjectKey)  
                    completion(obj, nil)  
                } else {  
                    completion(nil, ServiceError.generic(message: "Data not found at URL \(combinedUrl)"))  
                }  
            } catch {  
                completion(nil, error)  
            }  
        }  
    }  
}
```
However, this still threw the same EXC_BAD_ACCESS while trying to decode a League object. What gives?

## A Date with DTS

StackOverflow and the Apple Dev Forums were not helpful, so I figured it was high time to contact Developer Technical Support (DTS) to find out what part of this puzzle I was missing. I knew it had something to do with the stack and memory corruption, but how that affected NSKeyedArchiver/NSKeyedUnarchiver was beyond me. 

Based on my email thread with DTS, it turns out that I was partially right: the issue had nothing to do with my implementation of NSKeyedArchiver/NSKeyedUnarchiver methods, but it did have something to do with my use of them, specifically in concert with Grand Central Dispatch (GCD).

DTS explained that each queue on an Apple device has a predefined maximium stack memory size. The main thread is capped at 1 MB, but secondary threads (including the various QoS threads provided by DispatchQueue by default) are only allowed 512 kB[^2]. This presents a problem: if you encode a large data model into a large object graph with a number of circular references, the amount of memory you need to decode that graph increases virtually exponentially. At first blush, the stacktrace reads like an infinite loop that crashes because of a lack of stack memory, but that's only part of the answer -- this "infinite loop" _should_ terminate by itself naturally because NSCoding automatically handles circular references for you. It isn't doing so because the decoding process hits the maximum stack size before decoding the object graph reaches its natural conclusion (IE: a valid League object.)

## Solution #1

Intuitively, it seemed like the solution here was obvious: move decoding onto the main thread to take advantage of its expanded stack size. Encoding never seemed to be the problem[^1], so don't worry about it.

Now, we have something that looks like this:

```swift
@objc static func load(_ fileUrl: URL, completion: @escaping ((Any?, Error?) -> ())) {
    CFCSerialQueue.processingQueue.async {
        measureTime(operation: "[iCloudService Load] Loading") {
            do {
                if (FileManager.default.fileExists(atPath: fileUrl.path)) {
                    let data: Data = try Data(contentsOf: fileUrl)
                    let unarchiver: NSKeyedUnarchiver = try NSKeyedUnarchiver(forReadingFrom: data)
                    unarchiver.requiresSecureCoding = false;
                    let obj: Any? = try unarchiver.decodeTopLevelObject(forKey: NSKeyedArchiveRootObjectKey)
                    unarchiver.finishDecoding()
                    completion(obj, nil)
                } else {
                    completion(nil, ServiceError.generic(message: "Data not found at URL \(fileUrl)"))
                }
            } catch {
                completion(nil, error)
            }
        }
    }
}
```

This works as expected -- with more memory available, the object graph can properly reconcile its various circular references. I shipped this to the CFC beta group and things looked good -- I used to see crashes from `[NSObject(AutoCoding) setWithCoder:]` or `-[Game initWithCoder:]` consistently, but with this change in place, they just disappeared.

## Solution #2

But I made a judgment error when shipping that change: encoding _was_ a problem, but I had just been misinterpreting crash reports and shrugging my shoulders, unable to identify a root cause -- until now. Armed with DTS's tip about stack memory size in threads, I knew there had to be a way to take care of encoding and decoding crashes _and_ remove model-affecting and potentially long-running code from executing on the main thread (which felt icky to me to begin with). 

DTS tipped me off to a way to create my own NSThread, adjust its stack size, and use it. (What are the implementation differences between NSThread and GCD queues, you might ask? Well, that might be the subject of your own DTS inquiry.) Given that information, you might think that you could refactor the `save()` method (and `load()` in a similar fashion)  to look something like this:

```swift
let processingThread: Thread = {
    let newThread: Thread = Thread()
    newThread.stackSize = 8192 * 64 * 2
    newThread.qualityOfService = .userInitiated
    return newThread
}()

@objc private static func threadedSave(_ combinedParams: [String: Any]) {
    let completion: ((Bool, Error?) -> ())? = combinedParams["completion"] as? ((Bool, Error?) -> ())
    let content: NSCoding = combinedParams["content"] as! NSCoding
    let fileName: String = combinedParams["fileName"] as! String
    do {
        let data: Data = try NSKeyedArchiver.archivedData(withRootObject: content, requiringSecureCoding: false)
        try data.write(to: targetDirectory!.appendingPathComponent(fileName), options: .atomicWrite)
        processingThread.cancel()
        if (completion != nil) {
            completion!(true, nil)
        }
    } catch {
        processingThread.cancel()
        if (completion != nil) {
            completion!(false, error)
        }
    }
}

@objc static func save(_ content: NSCoding, at fileName: String, completion: ((Bool, Error?) -> ())?) {
    CFCSerialQueue.processingQueue.async {
        measureTime(operation: "[LocalService Save] Saving") {
            var combinedParams: [String: Any] = [
                "fileName" : fileName,
                "content" : content,
            ]
            if (completion != nil) {
                combinedParams["completion"] = completion!
            }
            perform(#selector(threadedSave(_:)), on: processingThread, with: combinedParams, waitUntilDone: true)
            processingThread.start()
        }
    }
}
```

This looks all well and good...until you try to run it. The thread starts, but never executes the given selector. Why? Frankly, I have no idea, but my best guess (based on [this StackOverflow answer](https://stackoverflow.com/a/49853417/13457626)) is that the thread doesn't have a run-loop mode[^3] properly set.

Let's try a different approach: what if we spawned a new thread ad-hoc? That might look something like this:

```swift
fileprivate static func splitToThread(logic: @escaping (Thread)->(), stackSize: Int = 8192 * 64 * 2) {
    let newThread: Thread = Thread {
        logic(Thread.current) // pass the thread we're executing on to any logic to handle cancelling properly
    }
    newThread.name = "me.akeaswaran.example.io-expanded"
    newThread.stackSize = stackSize //8192 * 64 * 2 // 1 MB
    newThread.qualityOfService = .userInitiated
    newThread.start()
}

@objc static func save(_ content: NSCoding, at fileName: String, completion: ((Bool, Error?) -> ())?) {
    CFCSerialQueue.processingQueue.async {
        measureTime(operation: "[LocalService Save] Saving") {
            splitToThread(logic: { (thr) in
                do {
                    let data: Data = try NSKeyedArchiver.archivedData(withRootObject: content, requiringSecureCoding: false)
                    try data.write(to: targetDirectory!.appendingPathComponent(fileName), options: .atomicWrite)
                    thr.cancel()
                    if (completion != nil) {
                        completion!(true, nil)
                    }
                } catch {
                    thr.cancel()
                    if (completion != nil) {
                        completion!(false, error)
                    }
                }
            })
        }
    }
}
```

Now, we're spawning a new thread with the expanded stack size every time our code executes. This works (finally, yay!), but there are some caveats:

1. Spinning up a new thread every time we want to run this block of code could get memory intensive, especially if we don't/can't clean up threads.

2. There's a _slight_ performance penalty -- saving/encoding takes about 0.5 seconds longer.

Can we do better, or at the very least, can we be better platform citizens and clean up our own messes?

## Solution #3

Let's try to simplify our design so that we only spawn one thread. I have no prior experience with NSThreads, but thanks to [this example built by DarkDust on StackOverflow](https://stackoverflow.com/questions/22091696/how-to-dispatch-code-blocks-to-the-same-thread-in-ios/22091859#22091859), we can use an NSThread just like a GCD serial queue, but with the added bonus of a modifiable stack size. I added some more OOP chrome to DarkDust's implementation, but all you'll need to add is a line to adjust the stack size of `workerThread` (EX: `workerThread?.stackSize = 8192 * 64 * 2`).

With this in place, we can adjust our `splitToThread()` and `save()` methods like so:

```swift
@objc static func save(_ content: NSCoding, at fileName: String, completion: ((Bool, Error?) -> ())?) {
    CFCSerialQueue.processingQueue.async {
        CFCSerialQueue.processingThread.enqueue {
            measureTime(operation: "[LocalService Save] Saving") {
                do {
                    let data: Data = try NSKeyedArchiver.archivedData(withRootObject: content, requiringSecureCoding: false)
                    try data.write(to: targetDirectory!.appendingPathComponent(fileName), options: .atomicWrite)
                    if (completion != nil) {
                        completion!(true, nil)
                    }
                } catch {
                    if (completion != nil) {
                        completion!(false, error)
                    }
                }
            }
        }
    }
}
```

I co-opted CFCSerialQueue to add some OOP flair to DarkDust's code, and `processingThread` is a CFCSerialQueue singleton availably globally. We can enqueue our operation here, and it'll get sent to the underlying worker NSThread. This thread has a 1 MB stack size, just like the main thread.

Now, not only does our logic exeute properly, it also runs on a single underlying NSThread. We're not spawning new threads on a whim and potentially leaking memory. **Huzzah!**

However, there's again a caveat: this results in a performance penalty similar to spawning multiple threads. There seems to be no winning when it comes to performance here. Additionally, a quirk of my specific implementation means that we now have two levels of enforcing serial execution: the GCD serial DispatchQueue `processingQueue` and DarkDust's `processingQueue`. Could these be better named? Yes. Could there be performance and concurrency implications for calling an NSThread from inside a GCD serial queue? Potentially. I'll have to do some more testing to figure out what the effect is and if it would be worth it to move all relevant operations over to `processingThread`. 

## Conclusion

What did we learn today?

1. On iOS, the main thread has a stack size of 1 MB, but secondary threads (including the various QoS threads provided by DispatchQueue by default) are only allowed 512 kB[^2].

2. There are not that many ways to get around this restriction if you also want the API simplicity that GCD/DispatchQueues provide.

3. Using a lot of stack memory is bad, [mkay](https://giphy.com/gifs/southparkgifs-3o6ZsZdNs3yE5l6hWM)?

4. Should we mix NSThreads and DispatchQueues? Maybe -- maybe not.

5. StackOverflow giveth, and StackOverflow taketh away (and sometimes, not in that order).

6. There is no item 6.

Other resources you might find fun to read:

* [Apple docs on run loops](https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/Multithreading/RunLoopManagement/RunLoopManagement.html#//apple_ref/doc/uid/10000057i-CH16)
* [Apple docs on threads](https://developer.apple.com/documentation/foundation/thread/)
* [O'Reilly chapter on concurrent programming on iOS](https://www.oreilly.com/library/view/high-performance-ios/9781491910993/ch04.html)
* [Another plug for DarkDust's saving grace of an answer from StackOverflow](https://stackoverflow.com/questions/22091696/how-to-dispatch-code-blocks-to-the-same-thread-in-ios/22091859#22091859)

---

#### Footnotes
[^1]: The other foot drops soon -- don't worry.

[^2]: [via Apple docs](https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/Multithreading/CreatingThreads/CreatingThreads.html)

[^3]: [Apple docs on run loops](https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/Multithreading/RunLoopManagement/RunLoopManagement.html#//apple_ref/doc/uid/10000057i-CH16)