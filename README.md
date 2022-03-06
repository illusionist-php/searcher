<h1 align="center">Illusionist Searcher</h1>
<div align="center">
Generates database queries based on search syntax.
<br /><br />

![packagist](https://img.shields.io/packagist/v/illusionist/searcher?style=flat-square)
![php](https://img.shields.io/packagist/php-v/illusionist/searcher?style=flat-square)
![downloads](https://img.shields.io/packagist/dm/illusionist/searcher?style=flat-square)
![license](https://img.shields.io/packagist/l/illusionist/searcher?style=flat-square)
[![Build Status](https://app.travis-ci.com/illusionist-php/searcher.svg?branch=1.0)](https://app.travis-ci.com/illusionist-php/searcher)
<br /><br />
English | [中文](README-zh_CN.md) 
</div>

## 🏗 Scene

- Mid background system
- Complex front-end query conditions

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    public function index(Request $request)
    {
        return Post::search($request->all())->get();
    }
}
```

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

#### Laravel/Lumen

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;
}
```

#### ThinkPHP

Your ThinkPHP version must be `>= 5.x`

```php
<?php

namnespace app\model;

use think\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;
}
```

Now you can create a database query using the [search syntax](#syntax)

```php
Post::search('title:"Hello world" sort:-created_at,published')->get();
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
['not' => 'published']    // published = false
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

The term `NULL` is not case sensitive.

```php
'body:NULL'         // body is null
'not body:null'     // body is not null
```

**Array syntax**

```php
['body' => null]               // body is null
['not' => ['body' => null]]    // body is not null
```

### Searchable

The queried term must not match a `boolean` or `date` column, otherwise it will be handled as a `boolean` or `date` query.

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

## ⚔️ Advanced

### Searchable

If a query term is not `boolean` or `date` column, it call `getQueryPhraseColumns` to get searchable columns. 

If no operator is specified in the return value, `like` is used by default. 

**For example:**

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    /**
     * Get the columns of the query phrase.
     *
     * @param  string  $phrase
     * @return array
     */
    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }
}

'lonely' // Equivalent to:
$query->where('title', '%lonely%');

'3000' // Equivalent to:
$query->where(function ($query) {
    $query->where('stars', '>=', '3000', 'or')
        ->whereHas('comments', function ($query) {
            $query->where('stars', '>=', '3000')
        });
});
```

### Relationship

If you define a relation method, it will be used to query relationships.

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

// Querying Relationship Existence
'comments'                          // $query->has('comments');

// Counting Related Models
'select:comments_count'            // $query->withCount('comments');

// Eager Loading
'select:comments'                  // $query->select('id')->with('comments');
'select:comments.title'            // $query->select('id')->with('comments:id,title')
```

### Configuring searchable columns

Query terms that are not in the `searchable` property will be discarded, the default value is the `real columns` of the model table and the `relation method name`.

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $searchable = ['author', 'created_at'];
}

'author:kayson title:hello'  // Equivalent to:
$query->where('author', '=', 'kayson');
```

### Configuring boolean and date column

#### Laravel/Lumen

Use the `casts` attribute to specify boolean and date columns.

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $casts = [
        'published' => 'boolean',
        'created_at' => 'datetime',
    ];
}
```

#### ThinkPHP

Use the `type` attribute to specify boolean and date columns.

```php
<?php

namnespace app\model;

use think\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $type = [
        'published' => 'boolean',
        'created_at' => 'datetime',
    ];
}
```

### Configuring special keywords

Implement custom keywords and symbiotic columns by overriding the `getRelaSearchName` function.

`selec`, `order_by`, `offset` is a reserved keywords, please do not conflict with the query terms.

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    /**
     * Get the real name of the given search column.
     *
     * @param  string  $key
     * @return string|array
     */
    public function getRelaSearchName($key)
    {
       switch ($key) {
            case 'field':
                return 'select';
            case 'sort':
                return 'order_by';
            case 'from':
                return 'offset';
            case 'stars':
                return ['stars', 'comments.stars'];
            default:
                return $key;
        }
    }
}

'field:id,name' // Equivalent to:
$query->select(['id', 'name']);

'stars:3000' // Equivalent to:
$query->where(function ($query) {
    $query->where('stars', '>=', '3000', 'or')
        ->whereHas('comments', function ($query) {
            $query->where('stars', '>=', '3000')
        });
});
```

