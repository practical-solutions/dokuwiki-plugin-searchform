# DokuWiki-Plugin: Searchform II

Fork of the original [searchform-plugin](https://www.dokuwiki.org/plugin:searchform) by Gerrit Uitslag.

Modifications:
* Shows fulltext search results (thus a lot slower than the original plugin)
* Changed the basic styling
* Option to add an external search result page

## External search

In the configuration setting you can state a url, which is used for the quicksearch-results, e.g.

```
http://yoururl.de/search.php?s=
```

The searching-term the user has enterend is appended to the given url and the result is show in the quicksearch results.

**But**: Pressing enter will still direct to dokuwiki search results page showing the local results.

## Compatbility

Tested with

  * PHP **7.3**
  * DokuWiki/**Hogfather**
