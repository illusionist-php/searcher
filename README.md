<h1 align="center">Illusionist Searcher</h1>

<p align="center">
<img src="https://img.shields.io/badge/tests-developing-green?logo=github" alt="Build Status">
<img src="https://img.shields.io/badge/license-MIT-green" alt="License" />
</p>

## ✨ Features

- Zero configuration
- Compatible with [laravel/scout](https://github.com/laravel/scout) and [lorisleiva/laravel-search-string](https://github.com/lorisleiva/laravel-search-string)
- Support string and array [syntax](#syntax)
- Support [laravel](https://github.com/laravel/framework) framework
- Support [thinkphp](https://github.com/top-think/think) framework

## 📦 Install

install via composer

```bash
composer require illusionist/searcher
```

## 🔨 Usage

Add the `Searchable` trait to your model's

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Eloquent\Searchable;

class Article extends Model
{
    use Searchable;
}
```

Now you can create a database query using the [search syntax](#syntax)

```php
Article::search('title:"Hello world" sort:-created_at,published')->get();
```

## <a id="syntax"></a> 💡 Syntax

Note that the spaces between operators don't matter for the string syntax

### Exact matches

**String syntax**

```php
'rating: 0'
'rating = 0'
'title: Hello'               // Strings without spaces do not need quotes
'title: "Hello World"'       // Strings with spaces require quotes
"title: 'Hello World'"       // Single quotes can be used too
'rating = 99.99'
'created_at: "2018-07-06 00:00:00"'
```

**Array syntax**

```php
['rating' => 0]
['title' => 'Hello World']
['rating' => 99.99]
['created_at' => '2018-07-06 00:00:00']
```

### Comparisons

**String syntax**

```php
'title < B'
'rating > 3'
'created_at >= "2018-07-06 00:00:00"'
```

**Array syntax**

```php
['title' => ['<', 'B']]
['rating' => ['>', 3]]
['created_at' => ['>=', '2018-07-06 00:00:00']]
```

### Booleans

**String syntax**

```php
'published'         // published = true
'not published'     // published = false
```

**Array syntax**

```php
['published']              // published = true
['not' => 'created_at']    // published = false
```

### Dates

**String syntax**

```php
'created_at'                            // created_at is not null
'not created_at'                        // created_at is null

// Year precision
'created_at >= 2020'                    // 2020-01-01 00:00:00 <= created_at
'created_at > 2020'                     // 2020-12-31 23:59:59 < created_at
'created_at = 2020'                     // 2020-01-01 00:00:00 <= created_at <= 2020-12-31 23:59:59
'not created_at = 2020'                 // created_at < 2020-01-01 00:00:00 and created_at > 2020-12-31 23:59:59

// Month precision
'created_at = 01/2020'                  // 2020-01-01 00:00:00 <= created_at <= 2020-01-31 23:59:59
'created_at <= "Jan 2020"'              // created_at <= 2020-01-31 23:59:59
'created_at < 2020-1'                   // created_at < 2020-01-01 00:00:00

// Day precision
'created_at = 2020-12-31'               // 2020-12-31 00:00:00 <= created_at <= 2020-12-31 23:59:59
'created_at >= 12/31/2020"'             // 2020-12-31 23:59:59 <= created_at
'created_at > "Dec 31 2020"'            // 2020-12-31 23:59:59 < created_at

// Hour and minute precisions
'created_at = "2020-12-31 16"'          // 2020-12-31 16:00:00 <= created_at <= 2020-12-31 16:59:59
'created_at = "2020-12-31 16:30"'       // 2020-12-31 16:30:00 <= created_at <= 2020-12-31 16:30:59
'created_at = "Dec 31 2020 5pm"'        // 2020-12-31 17:00:00 <= created_at <= 2020-12-31 17:59:59
'created_at = "Dec 31 2020 5:15pm"'     // 2020-12-31 17:15:00 <= created_at <= 2020-12-31 17:15:59

// Exact precision
'created_at = "2020-12-31 16:30:00"'    // created_at = 2020-12-31 16:30:00
'created_at = "Dec 31 2020 5:15:10pm"'  // created_at = 2020-12-31 17:15:10

// Relative dates
'created_at = today'                    // today between 00:00 and 23:59
'not created_at = today'                // any time before today 00:00 and after today 23:59
'created_at >= tomorrow'                // from tomorrow at 00:00
'created_at <= tomorrow'                // until tomorrow at 23:59
'created_at > tomorrow'                 // from the day after tomorrow at 00:00
'created_at < tomorrow'                 // until today at 23:59
```

**Array syntax**

```php
['created_at']                                      // created_at is not null
['not' => 'created_at']                             // created_at is null

// Year precision
['created_at' => ['>=', '2020']]                    // 2020-01-01 00:00:00 <= created_at
['created_at' => ['>', '2020']]                     // 2020-12-31 23:59:59 < created_at
['created_at' => '2020']                            // 2020-01-01 00:00:00 <= created_at <= 2020-12-31 23:59:59
['not' => ['created_at' => '2020']]                 // created_at < 2020-01-01 00:00:00 and created_at > 2020-12-31 23:59:59

// Month precision
['created_at' => '01/2020']                         // 2020-01-01 00:00:00 <= created_at <= 2020-01-31 23:59:59
['created_at' => ['<=', 'Jan 2020']                 // created_at <= 2020-01-31 23:59:59
['created_at' => ['<', '2020-1']]                   // created_at < 2020-01-01 00:00:00

// Day precision
['created_at' => '2020-12-31']                      // 2020-12-31 00:00:00 <= created_at <= 2020-12-31 23:59:59
['created_at' => ['>=', '12/31/2020']               // 2020-12-31 23:59:59 <= created_at
['created_at' => ['>', 'Dec 31 2020']]              // 2020-12-31 23:59:59 < created_at

// Hour and minute precisions
['created_at' => '2020-12-31 16']                   // 2020-12-31 16:00:00 <= created_at <= 2020-12-31 16:59:59
['created_at' => '2020-12-31 16:30']                // 2020-12-31 16:30:00 <= created_at <= 2020-12-31 16:30:59
['created_at' => 'Dec 31 2020 5pm']                 // 2020-12-31 17:00:00 <= created_at <= 2020-12-31 17:59:59
['created_at' => 'Dec 31 2020 5:15pm']              // 2020-12-31 17:15:00 <= created_at <= 2020-12-31 17:15:59

// Exact precision
['created_at' => '2020-12-31 16:30:00']             // created_at = 2020-12-31 16:30:00
['created_at' => 'Dec 31 2020 5:15:10pm']           // created_at = 2020-12-31 17:15:10

// Relative dates
['created_at' => 'today']                           // today between 00:00 and 23:59
['not' => ['created_at' => 'today']]                // any time before today 00:00 and after today 23:59
['created_at' => ['>=', 'tomorrow']]                // from tomorrow at 00:00
['created_at' => ['<=', 'tomorrow']]                // until tomorrow at 23:59
['created_at' => ['>', 'tomorrow']]                 // from the day after tomorrow at 00:00
['created_at' => ['<', 'tomorrow']]                 // until today at 23:59
```

### Lists

**String syntax**

```php
'status:Finished,Archived'
'status in(Finished,Archived)'
'title in (Hello, Hi, "My super article")'
```

**Array syntax**

```php
['status' => ['Finished', 'Archived']]
['status' => ['in', 'Finished', 'Archived']]
['title' => ['in', 'Hello', 'Hi', 'My super article']]
```

### Between

**String syntax**

```php
'created_at:2021-1-1~2021-12-31'
'created_at between(2021-1-1, 2021-12-31)'
```

**Array syntax**

```php
['created_at' => ['between', ['2021-1-1', '2021-12-31']]]
['created_at' => ['between', '2021-1-1', '2021-12-31']]
```

### Negations

**String syntax**

```php
'not title:Hello'
'not title="My super article"'
'not rating:0'
'not rating>4'
'not status in (Finished,Archived)'
'not published'                         // published = false
'not created_at'                        // created_at is null
```

**Array syntax**

```php
['not' => ['title' => 'Hello']]
['not' => ['rating' => 0]]
['not' => ['rating' => ['>', 4]]]
['not' => ['status' => ['in', 'Finished', 'Archived']]]
['not' => ['published']]                                   // published = false
['not' => ['created_at']]                                  // created_at is null
```

### Null values

**String syntax**

The term `NULL` is case sensitive.

```php
'body:NULL'         // body is null
'not body:NULL'     // body is not null
```

**Array syntax**

```php
['body' => null]               // body is null
['not' => ['body' => null]]    // body is not null
```

### Searchable

The queried term must not match a boolean column, otherwise it will be handled as a boolean query.

**String syntax**

```php
'Apple'             // %Apple% like at least one of the searchable columns
'"John Doe"'        // %John Doe% like at least one of the searchable columns
'not "John Doe"'    // %John Doe% not like any of the searchable columns
```

**Array syntax**

```php
['Apple']                  // %Apple% like at least one of the searchable columns
['not' => 'John Doe']      // %John Doe% not like any of the searchable columns
```

### And/Or

**String syntax**

```php
'title:Hello body:World'        // Implicit and
'title:Hello and body:World'    // Explicit and
'title:Hello or body:World'     // Explicit or
'A B or C D'                    // Equivalent to '(A and B) or (C and D)'
'A or B and C or D'             // Equivalent to 'A or (B and C) or D'
'(A or B) and (C or D)'         // Explicit nested priority
'not (A and B)'                 // Equivalent to 'not A or not B'
'not (A or B)'                  // Equivalent to 'not A and not B'
```

**Array syntax**

Keyword use `studly-caps` format, e.g. `andOr` can be written as `and_or` or `and-or` or `and or` or `AndOr`;

```php
['title' => 'Hello', 'body' => 'World']                // Implicit and
['and' => ['title' => 'Hello', 'body' => 'World']]     // Explicit and
['or' => ['title' => 'Hello', 'body' => 'World']]      // Explicit or
['or' => [['A', 'B'], ['C', 'D']]]                     // Equivalent to '(A and B) or (C and D)'
['or' => ['A', ['B', 'C'], 'D']]                       // Equivalent to 'A or (B and C) or D'
['andOr' => [['A', 'B'], ['C', 'D']]]                  // Equivalent to '(A or B) and (C or D)'
['not' => ['A', 'B']]                                  // Equivalent to 'not A or not B'
['notOr' => ['A', 'B']]                                // Equivalent to 'not A and not B'
```

### Relationships

**String syntax**

```php
// Simple "has" check
'comments'                              // Has comments
'not comments'                          // Doesn't have comments
'comments = 3'                          // Has 3 comments
'not comments = 3'                      // Doesn't have 3 comments
'comments > 10'                         // Has more than 10 comments
'not comments <= 10'                    // Same as before
'comments <= 5'                         // Has 5 or less comments
'not comments > 5'                      // Same as before

// "WhereHas" check
'comments: (title: Superbe)'            // Has comments with the title "Superbe"
'comments: (not title: Superbe)'        // Has comments whose titles are different than "Superbe"
'not comments: (title: Superbe)'        // Doesn't have comments with the title "Superbe"
'comments: (quality)'                   // Has comments whose searchable columns match "%quality%"
'not comments: (spam)'                  // Doesn't have comments marked as spam
'comments: (spam) >= 3'                 // Has at least 3 spam comments
'not comments: (spam) >= 3'             // Has at most 2 spam comments
'comments: (not spam) >= 3'             // Has at least 3 comments that are not spam
'comments: (likes < 5)'                 // Has comments with less than 5 likes
'comments: (likes < 5) <= 10'           // Has at most 10 comments with less than 5 likes
'not comments: (likes < 5)'             // Doesn't have comments with less than 5 likes
'comments: (likes > 10 and not spam)'   // Has non-spam comments with more than 10 likes

// "WhereHas" shortcuts
'comments.title: Superbe'               // Same as 'comments: (title: Superbe)'
'not comments.title: Superbe'           // Same as 'not comments: (title: Superbe)'
'comments.spam'                         // Same as 'comments: (spam)'
'not comments.spam'                     // Same as 'not comments: (spam)'
'comments.likes < 5'                    // Same as 'comments: (likes < 5)'
'not comments.likes < 5'                // Same as 'not comments: (likes < 5)'

// Nested relationships
'comments: (author: (name: John))'      // Has comments from the author named John
'comments.author: (name: John)'         // Same as before
'comments.author.name: John'            // Same as before

// Nested relationships are optimised
'comments.author.name: John and comments.author.age > 21'   // Same as: 'comments: (author: (name: John and age > 21))
'comments.likes > 10 or comments.author.age > 21'           // Same as: 'comments: (likes > 10 or author: (age > 21))
```

**Array syntax**

```php
// Simple "has" check
['comments']                                               // Has comments
['not' => ['comments']]                                    // Doesn't have comments
['comments' => 3]                                          // Has 3 comments
['not' => ['comments' => 3]]                               // Doesn't have 3 comments
['comments' => ['>', 10]]                                  // Has more than 10 comments
['not' => ['comments' => ['<=', 10]]]                      // Same as before
['comments' => ['<=', 5]]                                  // Has 5 or less comments
['not' => ['comments' => ['>', 5]]]                        // Same as before

// "WhereHas" check
['comments' => ['title' => 'Superbe']]                     // Has comments with the title "Superbe"
['comments' => ['not' => ['title' => 'Superbe']]]          // Has comments whose titles are different than "Superbe"        
['not' => ['comments' => ['title' => 'Superbe']]]          // Doesn't have comments with the title "Superbe"
['comments' => 'quality']                                  // Has comments whose searchable columns match "%quality%"
['not' => ['comments' => 'spam']]                          // Doesn't have comments marked as spam
['comments' => ['spam', ['>=', 3]]]                        // Has at least 3 spam comments
['not' => ['comments' => ['spam', ['>=', 3]]]]             // Has at most 2 spam comments
['comments' => ['not' => 'spam', ['>=', 3]]]               // Has at least 3 comments that are not spam
['comments' => ['likes' => ['<', 5]]]                      // Has comments with less than 5 likes
['comments' => ['likes' => ['<', 5], ['<=', 10]]]          // Has at most 10 comments with less than 5 likes
['not' => ['comments' => ['likes' => ['<', 5]]]]           // Doesn't have comments with less than 5 likes
['comments' => ['likes' => ['<', 5], 'not' => 'spam']]     // Has non-spam comments with more than 10 likes

// Nested relationships
['comments' => ['author' => ['name' => 'John']]]           // Has comments from the author named John
```
