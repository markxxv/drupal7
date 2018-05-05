# Import a Drupal7 website into Knowfox

I have a blog at [olav.net](https://olav.net), running since 1999, implemented in Drupal7.
I don't want to convert it into a static website. However, I also want the contents in it to be available in [my personal knowledge base](https://knowfox.com/). So, I implement a two staged process:

* Import all the contents from Drupal into Knowfox
* Use Knowfox to generate a static site

This package does the _import_ part, accessing the Drupal7 database directly.