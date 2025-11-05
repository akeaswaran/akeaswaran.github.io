# /// script
# requires-python = ">=3.10"
# dependencies = [
#     "bs4",
#     "feedparser",
#     "python-dateutil",
# ]
# ///

# FTRS Importer
# Author: Akshay Easwaran <akeaswaran@me.com>
# Description: I run this script with 'python refresh_ftrs.py' to add my recently written articles from From the Rumble Seat to my blog here.

import feedparser
import re
from os import listdir
from os.path import isfile, join
import time
from bs4 import BeautifulSoup
from dateutil.parser import parse
import html

print('Starting update of blog with recent FTRS posts.')
print('Checking date of last published blog post...')
onlyfiles = [f for f in listdir('./_posts') if isfile(join('./_posts', f))]
onlyfiles.sort()
last_title = onlyfiles[-1]
m = re.search('^([0-9]+\-[0-9]+\-[0-9]+)', last_title)
if m:
    found = m.group(1)
    last_date = time.strptime(found, '%Y-%m-%d')
    print('Last blog post date: ' + found)

    print('Retrieving latest FTRS articles written by self...')
    d = feedparser.parse('https://www.fromtherumbleseat.com/authors/akeaswaran/rss') # ?paged= for pagination
    self_articles = d.entries # filter(lambda x: {'name' : 'Akshay Easwaran'} in x.authors, d.entries)

    print('Parsing FTRS articles...')
    for a in self_articles:
        # print(a.published_parsed)
        if a.published_parsed > last_date:
            print('Formatting and writing \"' + a.title + '\" to _posts...')
            title = html.unescape(a.title)
            if len(title) > 56:
                title = title[:56].rstrip() + "..."
            link = a.link

            # The dek/subtitle for the article is always the first <p> tag, so use BeautifulSoup to retreive this.
            soup = BeautifulSoup(html.unescape(a.summary), "html.parser")
            deks = [p.get_text() for p in soup.find_all("summary", string=True)]
            if len(deks) > 0:
                dek = deks[0]
            else:
                dek = html.unescape(a.summary)

            if len(dek) > 54:
                dek = dek[:54].rstrip() + "..."

            # Parse and format the date (ISO format) of the article to use in the file name.
            post_date = parse(a.published)
            new_file_path = './_posts/' + post_date.strftime("%Y") + '-' + post_date.strftime("%m") + '-' + post_date.strftime("%d") + '-'+title.lower().replace(' ', '-').replace('!', '').replace(':','').replace('?','').replace('/','-').replace(',','').replace('&','and')+'.md'

            md_pieces = [
                '---',
                'layout: post',
                f'title: "{title}"'
            ]
            
            if dek is not None:
                md_pieces.append(f'description: "{dek}"')

            md_pieces += [
                f'permalink: "{link}"',
                '---'
            ]

            # Write the article's metadata to a Markdown file for Jekyll publishing.
            f = open(new_file_path, 'w')
            markdown_format = "\n".join(md_pieces)
            f.write(markdown_format)
            print('Wrote \"' + a.title + '\" to _posts.')

    print('Done updating blog with new FTRS articles.')
else:
    print('ERROR: unable to determine last post date.')
