## Internet Explorer 6 (only) Bug-Fix and Limitation ##
![http://andhrayouth.com/uploads/1265218253-Google.jpg](http://andhrayouth.com/uploads/1265218253-Google.jpg)

When we released CB reCAPTCHA 1.0, we had tested it with Firefox, Opera, Google Chrome Beta and IE7.
Later, we found 2 major/stupid bugs for IE6:

  * **Operation Aborted** - We have fixed this bug in our up-coming release RC2 or FINAL 1.0.

  * **reCAPTCHA block not displayed** - This was tricky. This is actually reCAPTCHA API's very own bug. We have solved the problem with a tiny limitation -
If a user visits any registration/forgot password page with IE6 and if the reCAPTCHA doesn't loads in the first time, it will automatically reloads the same page again so that it can reproduce the page and call the API again _without any wait_. In our test, we achieved 100% success-rate of loading the reCAPTCHA in the 2nd attempt, if it fails to load in the first attempt.