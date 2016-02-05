# DrivingTestScraper
Gives you the upcoming dates for a driving test at a particular test center

## Usage
```bash
$ php app.php check [options] [--] <licence> <center>

Arguments:
  licence                driving licence to check
  center                 Test Center/postcode (will take first match)

Options:
  -f, --filter[=FILTER]  A date to compare against, will return only dates less than this
  -m, --mail[=MAIL]      If given, will email the results (if any)

```

The app will choose the first test center from the list after searching for the given test center from the second argument
