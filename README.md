# Calendar
### A simple PHP calendar for Kindle that parses an ICAL file
 
## <a name="config.php"></a>config.php
* $remote_urls: To change the URLs for the calendar, simply edit config.php and add your URLs to this array.
* $offline: Controls whether offline mode. If false, the calendar directly loads the URLs specifies every time the webpage is loaded. If true, it fetches all the calendars in ./calendars and loads it. Personally I set up a cron job (not a bj, it's a background job that runs by a set interval of time automatically) every 2 hours that runs update.php (see [below](#update.php)) and use the "offline mode".

## <a name="update.php"></a>update.php
Updates a locally cached version of the calendars. Simply open update.php and it places the urls from config.php into ./calendars

## Why did you make this?
My Matron locks all laptops into the Prep Room during sports time so when the sport session starts early or ends late I would not be able to check my schedule for the day, which is stored on my NextCloud server. It was very annoying and I have to fetch the matron every time it happens. So I built a web app to show my schedule on a Kindle and started this project.