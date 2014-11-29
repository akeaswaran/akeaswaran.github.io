<?php
$projectsNames = ["KinderPrep", "ConferenceMate", "Milton GameDay", "Milton ClassConnect"];
$projectDescriptions = ["Want to help your kids get started with their schooling a little bit earlier than everyone else? This app is the way to go. Included are math and spelling problems that will help your child exceed when they start school. But this app isn't just for the kids; it's endless fun for the whole family. Try your hand at some simple spelling or challenge yourself with some tough addition problems. It's all possible with KinderPrep!",
    "ConferenceMate uses your iPhone's location services to determine your location and automatically select the conferencing number for the country you are currently in; the app saves you and your company money on roaming charges by doing this. To get the most out of the app experience, you must add the local phone numbers that your company has provided you for international conference calls. This allows the app to detect your location and pull the number for the country you are currently in.",
    "",
    ""];

echo '<!DOCTYPE html>
    <html>
    <head>
        <link rel="stylesheet" href="projects.css">
        <link href="http://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">
    <title>Projects - Akshay Easwaran</title>
    <style type="text/css">
    </style>
    </head>

    <body>
        <div id="headerTitle">
            <h1>Akshay Easwaran</h1>
        </div>

        <div id="navContainer">
            <a href="index.html" id="homeTab">Home</a>
            <a href="background.html" id="backgroundTab">Background</a>
            <a href="projects.html" id="projectsTab">Projects</a>
        </div>

        <div id="pageTitle">
            <h3>Projects</h3>
        </div>

        <div id="projectsContainer" class="section group">
            <div class="section group">
				<div class="col span_1_of_2">
				    <div id="kinderPrepQuad">
				        <h4>' + $projectsNames[0]+ '</h4>
                        <a href="http://appstore.com/kinderprep">
                            <img src="images/kp-icon.png">
                        </a>
                        <div id="kpInfo">
                            <p>' + $projectDescriptions[0] + '</p>
                        </div>
                    </div>
                    <div id="mgdQuad">
                        <h4>' + $projectsNames[2] + '</h4>
                        <a href="https://itunes.apple.com/us/app/milton-gameday/id558342843?mt=8">
                            <img src="images/mgd-icon.png">
                        </a>
                        <div id="mgdInfo">
                                <p><b>All of Milton’s Sports. In One Convenient Place.</b></p>

                                <p>Want to keep up with Milton High School (GA) sports? Then this app is definitely for you! Featuring push notifications and updated schedules and score, this app has everything you need to be ready for Friday nights in Milton, Georgia! With live game updates and scores, this app is the perfect fit for any fan of the Milton Eagles!</p>

                                <p>
                                    <b>Important Device Information:</b>
                                    To install Milton GameDay, you must be on iOS 6.0 or higher. To update to this firmware, please go to your device\'s settings and tap General &gt; Software Update to see if there is an update available, or plug your device into a computer to update via iTunes.
                                </p>

                            <p>
                                <b>Latest Changelog - Fall 2013 Update:</b>
                                </p><li>Redesigned UI</li>
                                <li>Updated sports info</li>
                                <li>Full Support for iOS 6.0+</li>
                                <li>Graphical improvements and bug fixes</li>
                            <p></p>
                        </div>
                    </div>
				</div>
				<div class="col span_1_of_2">
                    <div id="cmateQuad">
                        <h4>' + $projectsNames[1] + '</h4>
                        <a href="http://appstore.com/conferencemate">
                            <img src="images/cm-icon.png">
                        </a>
                        <div id="cmInfo">
                            <p>' + $projectDescriptions[1] +'</p>
                        </div>
                    </div>
                    <div id="mccQuad">
                        <h4>' + $projectsNames[3] + '</h4>
                        <a href="https://itunes.apple.com/us/app/classconnect/id675906273?mt=8">
                            <img src="images/mcc-icon.png">
                        </a>
                        <div id="mccInfo">
                            <p>Milton ClassConnect was developed by Milton Dev Crew to help Milton High School students in their studies. This app is meant to help students collaborate on schoolwork and help students keep apprised on their daily tasks, even when they are absent. This app is, by no means, for the purpose of cheating or plagiarism. Always do your own work. </p>

                            <p>Milton Dev Crew is not responsible for any content posted in Milton ClassConnect, nor will we share any data that users submit. We do, however, reserve the right to remove any content we find harmful or offensive from the app and ban the offending user. This app is to be a forum for open communication and collaboration, not another Facebook clone where you can post all of those pictures of yourself and your friends at last night\'s crazy party. Use ClassConnect responsibly.</p>

                            <p>Visit this app’s home on the web at
                                <a href="http://miltonclassconnect.webs.com/.">http://miltonclassconnect.webs.com/.</a>
                            </p>

                            <p>
                                <b>Main Features:</b>
                                </p><li>Sleek and modern UI</li>
                                <li>Friendly interface</li>
                                <li>Collaborative environment</li>
                                <li>Compatible with iOS 6.0+</li>
                            <p></p>

                            <p>
                                <b>Latest Changelog:</b>
                                </p><li>Fixes calendar issues</li>
                                <li>Full iPhone 5s and 64-bit compatibility</li>
                                <li>Graphical improvements</li>
                            <p></p>
                        </div>
                    </div>
                </div>
        </body>
        </html>';
