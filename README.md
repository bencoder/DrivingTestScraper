# DrivingTestScraper
Gives you the upcoming dates for a driving test at a particular test center

## Usage
```bash
$ php app.php check [options] [--] <licence> <center>     #To check for dates for a new test
$ php app.php change [options] [--] <licence> <reference> #To see if there are sooner dates for an existing test

Arguments:
  licence                driving licence to check
  center                 Test Center/postcode (will take first match)
  reference              Your application reference (given on original booking email)

Options:
  -f, --filter[=FILTER]  A date to compare against, will return only dates less than this
  -m, --mail[=MAIL]      If given, will email the results (if any)

```

The app will choose the first test center from the list after searching for the given test center from the second argument

###Example

```bash
php app.php check SIMPS704019B99LU Morden -m bart@simpson.com -f "30th March 2016"
```

Will check using Bart's driving licence number against Morden test center, emailing him dates earlier than 30th March 2016 whenever they are found.


```bash
php app.php change SIMPS704019B99LU 1234567 -m bart@simpson.com -f "30th March 2016"
```

Will check any sooner dates (at the same test center) for Bart's booking
