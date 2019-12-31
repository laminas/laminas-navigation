# Introduction to Laminas\\Navigation

`Laminas\Navigation` is a component for managing trees of pointers to web pages. Simply put: It can be
used for creating menus, breadcrumbs, links, and sitemaps, or serve as a model for other navigation
related purposes.

## Pages and Containers

There are two main concepts in `Laminas\Navigation`:

### Pages

A page (`Laminas\Navigation\AbstractPage`) in `Laminas\Navigation` – in its most basic form – is an
object that holds a pointer to a web page. In addition to the pointer itself, the page object
contains a number of other properties that are typically relevant for navigation, such as `label`,
`title`, etc.

Read more about pages in the \[pages\](laminas.navigation.pages) section.

### Containers

A navigation container (`Laminas\Navigation\AbstractContainer`) is a container class for pages. It has
methods for adding, retrieving, deleting and iterating pages. It implements the
[SPL](http://php.net/spl) interfaces `RecursiveIterator` and `Countable`, and can thus be iterated
with SPL iterators such as `RecursiveIteratorIterator`.

Read more about containers in the \[containers\](laminas.navigation.containers) section.

> ## Note
`Laminas\Navigation\AbstractPage` extends `Laminas\Navigation\AbstractContainer`, which means that a page
can have sub pages.

## View Helpers

### Separation of data (model) and rendering (view)

Classes in the `Laminas\Navigation` namespace do not deal with rendering of navigational elements.
Rendering is done with navigational view helpers. However, pages contain information that is used by
view helpers when rendering, such as; `label`, `class` (*CSS*), `title`, `lastmod` and `priority`
properties for sitemaps, etc.

Read more about rendering navigational elements in the \[view
helpers\](laminas.navigation.view.helpers) section.
